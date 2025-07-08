<?php

namespace App\Models;

use App\Models\Machines\Machine;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\PurchaseOrders\PurchaseOrderMaster;

class ProductionList extends Model
{
    use SoftDeletes;

    protected $table = 'production_list';

    protected $fillable = [
        'production_item_master_id',
        'machine_id',
        'produced_by',
        'quantity',
        'real_number',
    ];

    // Relationships

    public function purchaseOrderMaster()
    {
        return $this->belongsTo(PurchaseOrderMaster::class);
    }

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function producedBy()
    {
        return $this->belongsTo(User::class, 'produced_by');
    }
}
