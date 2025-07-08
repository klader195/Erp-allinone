<?php

namespace App\Http\Controllers\Dispatch;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Dispatch\Dispatch;
use App\Models\PurchaseOrders\PurchaseOrderMaster;
use App\Models\PurchaseOrders\PurchaseOrderItem;
use App\Models\Items\ItemTransaction;
use App\Models\Items\ItemGeneralQuantity;
use Carbon\Carbon;

class DispatchController extends Controller
{
    public function index(): View
    {
        return view('dispatches.list');
    }
     // Get unique customers for filter
    public function uniqueCustomers()
    {
        $customers = PurchaseOrderMaster::with('party')
            ->select('customer_id')
            
            ->distinct()
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->customer_id,
                    'name' => $item->party ? $item->party->first_name . ' ' . $item->party->last_name : 'Unknown'
                ];
            });
        
        return response()->json($customers);
    }

public function datatableList(Request $request)
{
    $query = Dispatch::with(['purchaseOrder', 'customer']);


    if ($request->filled('customer_id')) {

         $query->whereHas('customer', function ($q) use ($request) {
            $q->where('id', $request->customer_id);
        });
    }


 if ($request->filled('mode_name')) {
        $query->where('mode_of_delivery','like', "%{$request->mode_name}%"  );
    }

       if ($request->filled('duedate')) {
        //echo "1";
        // $request->duedate;

      


           $query->whereHas('purchaseOrder', function ($q) use ($request) {
                     $duedate1= date("Y-m-d", strtotime($request->duedate));

                $q->whereRaw("DATE_FORMAT(created_at, '%Y-%m-%d') LIKE ?", ["%{$duedate1}%"]);
            });
    }
    

    if ($request->filled('gapdate')) {
        //echo "1";
        //echo $request->podate;
        $query->whereHas('purchaseOrder', function ($q) use ($request) {
                    $gapdate= $request->gapdate;
         $days = (int) preg_replace('/[^0-9]/', '', $gapdate);
                $date = today()->subDays($days);
             $q->whereDate('created_at', $date->format('Y-m-d'));



        });


    }

if ($request->filled('dispatch_order_status')) {
        $query->where('status', $request->dispatch_order_status);
    }
    


    // Conditional Filters
    if ($request->filled('party_id')) {
        $query->whereHas('customer', function ($q) use ($request) {
            $q->where('first_name', 'like', '%' . $request->party_id . '%')
              ->orWhere('last_name', 'like', '%' . $request->party_id . '%');
        });
    }

    if ($request->filled('user_id')) {
        $query->where('user_id', $request->user_id);
    }

    if ($request->filled('from_date') && $request->filled('to_date')) {
        $query->whereBetween('created_at', [
            Carbon::parse($request->from_date)->startOfDay(),
            Carbon::parse($request->to_date)->endOfDay()
        ]);
    }

    // Order by latest
    $query->orderByDesc('id');

    return DataTables::of($query)
        ->addIndexColumn()

        // Searchable Columns
        ->filterColumn('purchase_order_identifier', function ($query, $keyword) {
            $query->where(function($q) use ($keyword) {
                $q->where('purchase_order_identifier', 'like', "%{$keyword}%")
                  ->orWhere('purchase_order_id', 'like', "%{$keyword}%");
            });
        })

        ->filterColumn('mode_of_delivery', function ($query, $keyword) {
            $query->where('mode_of_delivery', 'like', "%{$keyword}%");
        })

        ->filterColumn('customer_id', function ($query, $keyword) {
            $query->whereHas('customer', function ($q) use ($keyword) {
                $q->where('first_name', 'like', "%{$keyword}%")
                  ->orWhere('last_name', 'like', "%{$keyword}%");
            });
        })

        ->filterColumn('created_at', function ($query, $keyword) {
            $query->whereHas('purchaseOrder', function ($q) use ($keyword) {
                $q->whereRaw("DATE_FORMAT(created_at, '%Y-%m-%d') LIKE ?", ["%{$keyword}%"]);
            });
        })

        ->filterColumn('CreatedGap', function ($query, $keyword) {
            if (preg_match('/\d+/', $keyword, $matches)) {
                $days = (int) $matches[0];
                $targetDate = now()->subDays($days)->toDateString();

                $query->whereHas('purchaseOrder', function ($q) use ($targetDate) {
                    $q->whereDate('created_at', $targetDate);
                });
            }
        })

        // Display Columns
        ->editColumn('purchase_order_identifier', fn($row) => $row->purchase_order_identifier)
        ->editColumn('mode_of_delivery', fn($row) => $row->mode_of_delivery)
        ->editColumn('remarks', fn($row) => $row->remarks)

        ->editColumn('customer_id', function ($row) {
            $customer = $row->customer;
            return $customer ? "{$customer->first_name} {$customer->last_name}" : 'No customer';
        })

        ->editColumn('created_at', function ($row) {
            return optional($row->purchaseOrder)?->created_at?->format('d-m-Y') ?? 'N/A';
        })

        ->editColumn('CreatedGap', function ($row) {
            $createdAt = optional($row->purchaseOrder)->created_at;
            if ($createdAt) {
                $gap = now()->diffInDays($createdAt) . ' days';
                $class = in_array($row->status, ['Completed', 'Dispatched']) ? 'text-success' : 'text-danger';
                if($row->status=="Completed")
                {
            $createdAt = optional($row->purchaseOrder)->created_at;
            $updated_at = optional($row->purchaseOrder)->updated_at;

                                    $gap = $updated_at->diffInDays($createdAt) . ' days';

                  return "<span class=''>{$gap}</span>";

                }
                else
                {


                    return "<span class='{$class}'>{$gap}</span>";

                }
            }
            return 'N/A';
        })

        ->editColumn('action', function ($row) {
            $editUrl = route('dispatch.edit', ['id' => $row->id]);
            return '<a class="dropdown-item1" href="' . $editUrl . '"><i class="bi bi-trash"></i><i class="bx bx-edit"></i> ' . __('app.edit') . '</a>';
        })

        ->rawColumns(['action', 'CreatedGap'])
        ->make(true);
}

    
     public function edit($id): View
    {
        $dispatch = Dispatch::with(['purchaseOrder.items.product', 'customer'])->findOrFail($id);
        return view('dispatches.edit', compact('dispatch'));
    }

    
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
    
            // Validate the request
            $request->validate([
                'dispatch_id' => 'required|exists:dispatches,id',
                'dispatch_status' => 'required|string',
                'purchase_order_id' => 'required',
                'mode_of_delivery' => 'required',
                'remarks' => 'required',
            ]);
    
            // Find the existing dispatch record
            $dispatch = Dispatch::findOrFail($request->dispatch_id);
    
            // Update the dispatch record
            $dispatch->update([
                'status' => $request->dispatch_status,
                'remarks' => $request->remarks,
                'mode_of_delivery' => $request->mode_of_delivery,
                'updated_by' => auth()->id(),
            ]);
    
            // Check if the status was updated to 'Completed' (optional based on your business logic)
            // if ($request->dispatch_status == 'Completed') {
                PurchaseOrderMaster::where('id', $request->purchase_order_id)
                    ->update([
                        'purchase_order_status' => $request->dispatch_status,
                        'updated_by' => auth()->id(),
                    ]);
            // }

    
//new code check dispatch status is completed
                     $dispatchqtystatus=$dispatch['dispatch_qty_updation'];

                     //if ($request->dispatch_status == 'Completed') {


                           $purchase_order_item = PurchaseOrderItem::where('purchase_order_id', $request->purchase_order_id)->select('quantity','product_id')->get();
                        $reqqunatity= $purchase_order_item[0]['quantity'];
                        $product_id=$purchase_order_item[0]['product_id'];

                       if (($request->dispatch_status == 'Completed')||($request->dispatch_status == 'Dispatched')) {

                          if($dispatchqtystatus==0)
                          {

                            Dispatch::where('id', $request->dispatch_id)
                    ->update([
                        'dispatch_qty_updation' => 1
                    ]);


                    $item_transaction = ItemTransaction::where('item_id', $product_id)->select('quantity')->get();
                    $transaction_real_qty=$item_transaction[0]['quantity'];
                    $transaction_real_qty= $transaction_real_qty-$reqqunatity;
                      

                       ItemTransaction::where('item_id', $product_id)
                    ->update([
                        'quantity' => $transaction_real_qty
                    ]);

                    $item_genqty = ItemGeneralQuantity::where('item_id', $product_id)->select('quantity')->get();
                    $itemgen_real_qty=$item_genqty[0]['quantity'];
                    $itemgen_real_qty=$itemgen_real_qty-$reqqunatity;
                      ItemGeneralQuantity::where('item_id', $product_id)
                    ->update([
                        'quantity' => $itemgen_real_qty
                    ]);






                          }
                          else
                          {
                                 //no action required

                          }



                       }
                       else
                       {


                                 //no action required


                       }

                //echo $purchase_order_item->quantity;

                   // }


            DB::commit();
    
            return response()->json([
                'success' => true,
                'message' => __('Dispatch updated successfully!'),
                'redirect' => route('dispatch.index')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
    
            return response()->json([
                'success' => false,
                'message' => __('Error updating dispatch: ') . $e->getMessage()
            ], 500);
        }
    }


}
