<?php

namespace App\Http\Controllers\Items;

use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use App\Http\Controllers\Controller;
use Yajra\DataTables\Facades\DataTables;

use App\Traits\FormatNumber; 
use App\Traits\FormatsDateInputs;
use App\Models\Items\Item;
use App\Models\Items\ItemTransaction;
use App\Models\Items\ItemGeneralQuantity;
use App\Services\StockImpact;

class ItemTransactionController extends Controller
{
    use FormatsDateInputs;

    use FormatNumber;

    private $stockImpact;

    function __construct(StockImpact $stockImpact)
    {
        $this->stockImpact = $stockImpact;
    }

    public function list($id) : View {
        $item = Item::with('baseUnit', 'category')->find($id);
        return view('items.transaction.list', compact('item'));
    }
    
    public function edit($id)
    {

        $item = ItemTransaction::with(['item'])->findOrFail($id);
        return view('items.transaction.edit', compact('item'));
 
    }
    
    public function update(Request $request,$id)
    {
        $validated = $request->validate([
            'quantity' => 'required|numeric',
            'item_id' => 'required|integer'
        ]);
    
        // Update ItemTransaction where item_id = $request->item_id and id = $id
        $transaction = ItemTransaction::where('id', $id)
                        ->where('item_id', $request->item_id)
                        ->firstOrFail();
    
        $transaction->quantity = $validated['quantity'];
         $transaction->avaquantity=$validated['quantity'];
        $transaction->save();
    
        // Update ItemGeneralQuantity where item_id = $request->item_id and warehouse_id = 1
        $generalQty = ItemGeneralQuantity::where('item_id', $request->item_id)
                        ->where('warehouse_id', 1)
                        ->first();
    
        if ($generalQty) {
            $generalQty->quantity = $validated['quantity'];
            $generalQty->avaquantity=$validated['quantity'];
            $generalQty->save();
        }
    
         return redirect()->route('item.transaction.stocklist')->with('success', 'Quantity updated successfully.');
    }


   public function stockList()
    {
        // Fetch all item transactions with the related 'item'
        $itemTransactions = ItemTransaction::with('item')->get();
        
        
        // Return the view with the data
        return view('items.transaction.stocklist', compact('itemTransactions'));
    }


    public function datatableList(Request $request){
        $data = ItemTransaction::with('unit')->where('item_id', $request->item_id);

        return DataTables::of($data)
                    ->addIndexColumn()
                    ->addColumn('transaction_date', function ($row) {
                        return $row->formatted_transaction_date;
                    })
                    ->addColumn('reference_no', function ($row) {
                        return $row->transaction->getTableCode()??'';
                    })
                    ->addColumn('price', function ($row) {
                        return $this->formatWithPrecision($row->unit_price);
                    })
                    ->addColumn('quantity', function ($row) {
                        return $this->formatQuantity($row->quantity);
                    })
                    ->addColumn('stock_impact', function ($row) {
                        return $this->stockImpact->returnStockImpact($row->unique_code, $row->quantity);
                    })
                    ->addColumn('unit_name', function ($row) {
                        return $row->unit->name;
                    })
                    ->rawColumns(['action'])
                    ->make(true);
    }

}
