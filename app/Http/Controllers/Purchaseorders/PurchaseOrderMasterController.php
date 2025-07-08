<?php
namespace App\Http\Controllers\Purchaseorders;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use App\Models\Prefix;

use App\Models\Items\Item;
use App\Traits\FormatNumber;
use App\Traits\FormatsDateInputs;
use App\Enums\App;
// use App\Services\PaymentTypeService;
// use App\Services\GeneralDataService;
// use App\Services\PaymentTransactionService;
use App\Http\Requests\SaleOrderRequest;
use App\Http\Requests\SaleOrder;
// use App\Services\AccountTransactionService;
// use App\Services\ItemTransactionService;
use Carbon\Carbon;
use App\Services\CacheService;
use App\Services\StatusHistoryService;
use App\Services\Communication\Email\SaleOrderEmailNotificationService;
use App\Services\Communication\Sms\SaleOrderSmsNotificationService;
use App\Enums\ItemTransactionUniqueCode;
use App\Models\PurchaseOrders\PurchaseOrderMaster;
use App\Http\Requests\PurchaseOrdersRequest;
use Mpdf\Mpdf;
use App\Models\PurchaseOrders\PurchaseOrderItem;
use App\Models\Items\ProductionItemMaster;
use App\Models\Dispatch\Dispatch;
use App\Models\Items\ItemGeneralQuantity;
use App\Models\Items\ItemTransaction;
class PurchaseOrderMasterController extends Controller
{
    use FormatNumber;

    use FormatsDateInputs;

    protected $companyId;

    private $paymentTypeService;

    private $paymentTransactionService;

    private $accountTransactionService;

    private $itemTransactionService;

    public $saleOrderEmailNotificationService;

    public $saleOrderSmsNotificationService;

    public $generalDataService;

    public $statusHistoryService;



    /**
     * Create a new order.
     *
     * @return \Illuminate\View\View
     */
    public function index(): View  {
        // $prefix = Prefix::findOrNew($this->companyId);
        // $lastCountId = $this->getLastCountId();
        // $selectedPaymentTypesArray = json_encode($this->paymentTypeService->selectedPaymentTypesArray());
        // $data = [
        //     'prefix_code' => $prefix->sale_order,
        //     'count_id' => ($lastCountId+1),
        // ];
        // return view('sale.order.create',compact('data', 'selectedPaymentTypesArray'));
        // dd("here");
        return view('purchaseorder.list');
    }
    /**
     * Create a new order.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View  {
        $prefix = Prefix::findOrNew($this->companyId);
        $lastCountId = $this->getLastCountId();
        // $selectedPaymentTypesArray = json_encode($this->paymentTypeService->selectedPaymentTypesArray());
        $data = [
            'prefix_code' => 'PO',
            'count_id' => ($lastCountId+1),
        ];
        return view('purchaseorder.create',compact('data'));
    }

    /**
     * Get last count ID
     * */
    public function getLastCountId(){
        return PurchaseOrderMaster::select('id')->orderBy('id', 'desc')->first()?->id ?? 0;
    }

    /**
     * List the orders
     *
     * @return \Illuminate\View\View
     */
    public function list() : View {
        return view('sale.order.list');
    }


     /**
     * Edit a Sale Order.
     *
     * @param int $id The ID of the expense to edit.
     * @return \Illuminate\View\View
     */
    public function edit($id) : View {
      $order = PurchaseOrderMaster::with(['party', 'items.product'])->findOrFail($id);

    // $order = PurchaseOrderMaster::with([
    //     'party',
    //     'items.product',
    //     'items.brand',
    //     'items.category'
    // ])->findOrFail($id);

    //   dd($order);
        return view('purchaseorder.edit', compact('order'));
    }

    /**
     * View Sale Order details
     *
     * @param int $id, the ID of the order
     * @return \Illuminate\View\View
     */
    public function details($id) : View {
        $order = SaleOrder::with(['party',
                                        'itemTransaction' => [
                                            'item',
                                            'tax',
                                            'batch.itemBatchMaster',
                                            'itemSerialTransaction.itemSerialMaster'
                                        ]])->find($id);

        //Payment Details
        $selectedPaymentTypesArray = json_encode($this->paymentTransactionService->getPaymentRecordsArray($order));

        //Batch Tracking Row count for invoice columns setting
        $batchTrackingRowCount = (new GeneralDataService())->getBatchTranckingRowCount();

        return view('sale.order.details', compact('order','selectedPaymentTypesArray', 'batchTrackingRowCount'));
    }

    /**
     * Print Sale Order
     *
     * @param int $id, the ID of the order
     * @return \Illuminate\View\View
     */
    public function print($id, $isPdf = false) : View {
        $order = SaleOrder::with(['party',
                                        'itemTransaction' => [
                                            'item',
                                            'tax',
                                            'batch.itemBatchMaster',
                                            'itemSerialTransaction.itemSerialMaster'
                                        ]])->find($id);

        //Payment Details
        $selectedPaymentTypesArray = json_encode($this->paymentTransactionService->getPaymentRecordsArray($order));

        //Batch Tracking Row count for invoice columns setting
        $batchTrackingRowCount = (new GeneralDataService())->getBatchTranckingRowCount();

        $invoiceData = [
            'name' => __('sale.order.order'),
        ];

        return view('print.sale-order.print', compact('isPdf', 'invoiceData', 'order','selectedPaymentTypesArray','batchTrackingRowCount'));
        //return view('sale.order.unused-print', compact('order','selectedPaymentTypesArray','batchTrackingRowCount'));
    }


    /**
     * Generate PDF using View: print() method
     * */
    public function generatePdf($id){
        $html = $this->print($id, isPdf:true);

        $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 2,
                'margin_right' => 2,
                'margin_top' => 2,
                'margin_bottom' => 2,
                'default_font' => 'dejavusans',
                //'direction' => 'rtl',
            ]);
        $mpdf->showImageErrors = true;
        $mpdf->WriteHTML($html);
        /**
         * Display in browser
         * 'I'
         * Downloadn PDF
         * 'D'
         * */
        $mpdf->Output('Sale-Order-'.$id.'.pdf', 'D');
    }

    /**
     * Store Records
     * */
    public function store(PurchaseOrdersRequest $request)
    {

        $validated = $request->validated();
    
        DB::beginTransaction();
    
        try {
            // $purchase_order_id = 'PO-' . strtoupper(uniqid());
            $microtime = microtime(true);
            $milliseconds = sprintf("%03d", ($microtime - floor($microtime)) * 1000);
            $purchase_order_id = 'WO-' . date('dmY-His') . $milliseconds;
            $dispatch_order = 'DIS-' . date('dmY-His') . $milliseconds;
            $purchaseOrder = PurchaseOrderMaster::create([
                'purchase_order_id'     => $purchase_order_id,
                'customer_id'           => $validated['party_id'],
                'representative_id'     => $validated['representative_id'],
                'po_date'               => $validated['order_date'],
                'due_date'              => $validated['due_date'],
                'purchase_order_remarks'=> $validated['note'] ?? null,
                'purchase_order_status' => $validated['order_status'],
                'mode_of_dispatch'      => $validated['mode_of_dispatch'] ?? 'Self Pickup',
                'is_active'             => $validated['is_active'] ?? true,
                'created_by'            => auth()->id(),
                'updated_by'            => auth()->id(),
                'purchase_order_status' => 'Production',
            ]);
            $lastInsertId = $purchaseOrder->id;
              
        

    
            if (!empty($validated['item_id']) && is_array($validated['item_id'])) {
                $allStatuses = []; // collect all item statuses

                foreach ($validated['item_id'] as $index => $product_id) {
                    $status = $validated['status'][$index] ?? 'Push To Production';
                    $allStatuses[] = $status;
                
                    $purchaseOrder->items()->create([
                        'purchase_order_id' => $purchaseOrder->id,
                        'product_id'        => $product_id,
                        'quantity'          => $validated['quantity'][$index] ?? 0,
                        'product_remarks'   => $validated['remarks'][$index] ?? null,
                        'paking_remarks'    => $validated['pakingremarks'][$index] ?? null,
                        'dispatch_remarks'  => $validated['dispatchremarks'][$index] ?? null,
                        'status'            => $status
                    ]);
                
                    // Conditionally create production item only if status is 'Push To Production'
                    if ($status === 'Push To Production') {
                         $stock = $request['available_stock'][$index] ?? 0;
                         $orderqty = $validated['quantity'][$index] ?? 0;
                         $productionqty = $orderqty - $stock;
                        ProductionItemMaster::create([
                            'remarks'         => $validated['remarks'][$index] ?? null,
                            'production_type' => 'Purchaseorder',
                            'purchase_order_id' => $lastInsertId,
                            'status'          => 'Pending',
                            'requested_by'    => auth()->id(),
                            'item_id'         => $product_id,
                            'requested_qty'   => $productionqty,
                            'remaining_qty'   => $productionqty,
                        ]);
                        
                        // Decrease item quantity in ItemTransaction
                        ItemTransaction::where('item_id', $product_id)
                            ->decrement('avaquantity', $stock);
                    
                        // Update item_general_quantities
                        ItemGeneralQuantity::updateOrCreate(
                            [
                                'item_id' => $product_id,
                                'warehouse_id' => 1,
                            ],
                            [
                                'avaquantity' => DB::raw("avaquantity - {$stock}"),
                            ]
                        );
                    }
                    else {
                        // Decrease item quantity in ItemTransaction
                        ItemTransaction::where('item_id', $product_id)
                            ->decrement('avaquantity', $validated['quantity'][$index]);
                    
                        // Update item_general_quantities
                        ItemGeneralQuantity::updateOrCreate(
                            [
                                'item_id' => $product_id,
                                'warehouse_id' => 1,
                            ],
                            [
                                'avaquantity' => DB::raw("avaquantity - {$validated['quantity'][$index]}"),
                            ]
                        );
                    }
                                    }
                
                if (!in_array('Push To Production', $allStatuses)) {
                    try {
                        Dispatch::create([
                            'purchase_order_id' => $purchaseOrder->id,
                            'purchase_order_identifier'  => $purchase_order_id,
                            'customer_id'        => $validated['party_id'],
                            // 'mode_of_delivery'   => "Direct Customer",
                            // 'remarks'            => 'remarks',
                            'mode_of_delivery'   => $validated['mode_of_delivery'],
                            'remarks'            => $validated['dispatch_remarks'],
                            'status'             => $validated['dispatch_status'],
                            'dispatch_order'     => $dispatch_order
                            
                        ]);
                       
                        $purchaseOrder->update([
                            'purchase_order_status' => $validated['dispatch_status'],
                            'updated_by'            => auth()->id(),
                        ]);
                    } catch (\Exception $e) {
                        
                    }
                }

            }
    
            DB::commit();


            if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Purchase order created successfully.',
                'redirect_url' => route('purchaseorder.index'),
            ]);
        }

        return redirect()->route('purchaseorder.index')
            ->with('success', 'Purchase order created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
    //          \Log::error('Purchase Order Creation Failed', [
    //     'message' => $e->getMessage(),
    //     'line' => $e->getLine(),
    //     'file' => $e->getFile(),
    //     'trace' => $e->getTraceAsString(),
    // ]);
            return back()->with('error', 'Error creating purchase order: ' . $e->getMessage());
        }
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

    /**
     * Datatabale
     * */
public function datatableList(Request $request)
{
    // Load the 'party' relationship eagerly by default
    $query = PurchaseOrderMaster::with('party');

    // Apply filters if provided
    if ($request->filled('party_id')) {
        $query->whereHas('party', function ($query) use ($request) {
            $query->where('first_name', 'like', '%' . $request->party_id . '%')
                ->orWhere('last_name', 'like', '%' . $request->party_id . '%');
        });
    }

    // Add this new filter for customer_id
    if ($request->filled('customer_id')) {
        $query->where('customer_id', $request->customer_id);
    }

    if ($request->filled('user_id')) {
        $query->where('user_id', $request->user_id);
    }

    if ($request->filled('from_date') && $request->filled('to_date')) {
        $query->whereBetween('po_date', [
            Carbon::parse($request->from_date)->startOfDay(),
            Carbon::parse($request->to_date)->endOfDay()
        ]);
    }

    if ($request->filled('purchase_order_status')) {
        $query->where('purchase_order_status', $request->purchase_order_status);
    }
    
    
    if ($request->filled('podate')) {
        //echo "1";
        //echo $request->podate;
        $podate= date("Y-m-d", strtotime($request->podate));
        $query->where('po_date', $podate);
    }
      if ($request->filled('duedate')) {
        //echo "1";
        //echo $request->podate;
        $duedate= date("Y-m-d", strtotime($request->duedate));
        $query->where('due_date', $duedate);
    }

  if ($request->filled('gapdate')) {
        //echo "1";
        //echo $request->podate;
        $gapdate= $request->gapdate;
         $days = (int) preg_replace('/[^0-9]/', '', $gapdate);
                $date = today()->subDays($days);
             $query->whereDate('po_date', $date->format('Y-m-d'));
    }


//SELECT * FROM purchase_order_masters where DATEDIFF(NOW(), po_date)=9;

    $query->orderBy('id', 'desc');
    
    return datatables()->of($query)
        ->addIndexColumn()
        ->editColumn('customer_id', function ($row) {
            $party = $row->party;
            return $party ? $party->first_name . ' ' . $party->last_name : 'No customer found';
        })
        ->editColumn('po_date', function ($row) {
            return optional($row->po_date)?->format('d-m-Y');
        })
        ->editColumn('due_date', function ($row) {
            return optional($row->due_date)?->format('d-m-Y');
        })
        ->editColumn('CreatedGap', function ($row) {
            $createdAt = $row->po_date ? \Carbon\Carbon::parse($row->po_date) : null;
        
            if ($createdAt) {
                $gap = today()->diffInDays($createdAt) . ' days';
        
                if (!in_array($row->purchase_order_status, ['Completed', 'Dispatched'])) {
                    return '<span class="text-danger">' . $gap . '</span>';
                } else {
                    return '<span class="text-success">' . $gap . '</span>';
                }
            }
        
            return 'N/A';
        })
        ->editColumn('action', function($row){
            $id = $row->id;
            $editUrl = route('purchaseorder.edit', ['id' => $id]);
            return '<a href="' . $editUrl . '"><i class="bx bx-eye"></i> View</a>';
        })
        ->filterColumn('purchase_order_status', function($query, $keyword) {
            $query->where('purchase_order_masters.purchase_order_status', 'like', "%{$keyword}%");
        })
        
        // Add this filter for po_date
        ->filterColumn('po_date', function($query, $keyword) {
            try {
                // Try to parse the search term in d-m-Y format
                $date = Carbon::createFromFormat('d-m-Y', $keyword);
                $query->whereDate('po_date', $date->format('Y-m-d'));
            } catch (\Exception $e) {
                // If parsing fails, do a simple like search
                $query->where('po_date', 'like', "%{$keyword}%");
            }
        })
        // Add this filter for due_date
        ->filterColumn('due_date', function($query, $keyword) {
            try {
                // Try to parse the search term in d-m-Y format
                $date = Carbon::createFromFormat('d-m-Y', $keyword);
                $query->whereDate('due_date', $date->format('Y-m-d'));
            } catch (\Exception $e) {
                // If parsing fails, do a simple like search
                $query->where('due_date', 'like', "%{$keyword}%");
            }
        })
        
        // Add custom filter for CreatedGap only when specifically searching this column
        ->filterColumn('CreatedGap', function($query, $keyword) {
            if (strpos($keyword, 'days') !== false) {
                $days = (int) preg_replace('/[^0-9]/', '', $keyword);
                $date = today()->subDays($days);
                $query->whereDate('po_date', $date->format('Y-m-d'));
            }
          
        })
        
      
        ->rawColumns(['action','CreatedGap']) 
        ->make(true);
}



 

    /**
     * Delete Sale Order Records
     * @return JsonResponse
     * */
    public function delete(Request $request) : JsonResponse{

        DB::beginTransaction();

        $selectedRecordIds = $request->input('record_ids');

        // Perform validation for each selected record ID
        foreach ($selectedRecordIds as $recordId) {
            $record = SaleOrder::find($recordId);
            if (!$record) {
                // Invalid record ID, handle the error (e.g., show a message, log, etc.)
                return response()->json([
                    'status'    => false,
                    'message' => __('app.invalid_record_id',['record_id' => $recordId]),
                ]);

            }
            // You can perform additional validation checks here if needed before deletion
        }

        /**
         * All selected record IDs are valid, proceed with the deletion
         * Delete all records with the selected IDs in one query
         * */


        try {
            // Attempt deletion (as in previous responses)
            // SaleOrder::whereIn('id', $selectedRecordIds)->chunk(100, function ($orders) {
            //     foreach ($orders as $order) {
            //         $order->accountTransaction()->delete();
            //         //Load Sale Order Payment Transactions
            //         $payments = $order->paymentTransaction;
            //         foreach ($payments as $payment) {
            //             //Delete Payment Account Transactions
            //             $payment->accountTransaction()->delete();

            //             //Delete Sale Order Payment Transactions
            //             $payment->delete();
            //         }
            //     }
            // });

            // //Delete Sale Order
            // $deletedCount = SaleOrder::whereIn('id', $selectedRecordIds)->delete();

            // Attempt deletion (as in previous responses)
            SaleOrder::whereIn('id', $selectedRecordIds)->chunk(100, function ($orders) {
                foreach ($orders as $order) {
                    //Sale Account Update
                    foreach($order->accountTransaction as $orderAccount){
                        //get account if of model with tax accounts
                        $orderAccountId = $orderAccount->account_id;

                        //Delete sale and tax account
                        $orderAccount->delete();

                        //Update  account
                        $this->accountTransactionService->calculateAccounts($orderAccountId);
                    }//sale account

                    // Check if paymentTransactions exist
                    $paymentTransactions = $order->paymentTransaction;
                    if ($paymentTransactions->isNotEmpty()) {
                        foreach ($paymentTransactions as $paymentTransaction) {
                            // $accountTransactions = $paymentTransaction->accountTransaction;
                            // if ($accountTransactions->isNotEmpty()) {
                            //     foreach ($accountTransactions as $accountTransaction) {
                            //         //Sale Account Update
                            //         $accountId = $accountTransaction->account_id;
                            //         // Do something with the individual accountTransaction
                            //         $accountTransaction->delete(); // Or any other operation

                            //         $this->accountTransactionService->calculateAccounts($accountId);
                            //     }
                            // }

                            //delete Payment now
                            $paymentTransaction->delete();
                        }
                    }//isNotEmpty

                    //delete item Transactions
                    $order->itemTransaction()->delete();

                    //Delete Status History data
                    $order->statusHistory()->delete();

                    //Delete order
                    $order->delete();


                }//sales
            });

            DB::commit();

            return response()->json([
                'status'    => true,
                'message' => __('app.record_deleted_successfully'),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollback();
            return response()->json([
                'status'    => false,
                'message' => __('app.cannot_delete_records'),
            ],409);
        }
    }

    /**
     * Prepare Email Content to view
     * */
    public function getEmailContent($id)
    {
        $model = SaleOrder::with('party')->find($id);

        $emailData = $this->saleOrderEmailNotificationService->saleOrderCreatedEmailNotification($id);

        $subject = ($emailData['status']) ? $emailData['data']['subject'] : '';
        $content = ($emailData['status']) ? $emailData['data']['content'] : '';

        $data = [
            'email'  => $model->party->email,
            'subject'  => $subject,
            'content'  => $content,
        ];
        return $data;
    }

    /**
     * Prepare SMS Content to view
     * */
    public function getSMSContent($id)
    {
        $model = SaleOrder::with('party')->find($id);

        $emailData = $this->saleOrderSmsNotificationService->saleOrderCreatedSmsNotification($id);

        $mobile = ($emailData['status']) ? $emailData['data']['mobile'] : '';
        $content = ($emailData['status']) ? $emailData['data']['content'] : '';

        $data = [
            'mobile'  => $mobile,
            'content'  => $content,
        ];
        return $data;
    }

    /***
     * View Status History
     *
     * */
    public function getStatusHistory($id) : JsonResponse{

        $data = $this->statusHistoryService->getStatusHistoryData(SaleOrder::find($id));

        return response()->json([
            'status' => true,
            'message' => '',
            'data'  => $data,
        ]);

    }
    



}
