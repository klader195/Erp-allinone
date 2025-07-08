<?php
namespace App\Models\Items;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\User;
use App\Models\Items\Item;
use App\Models\PackingList;
use App\Models\ProductionList;
use App\Models\Items\ItemCategory;
use App\Models\Items\ProductionItem;
use Illuminate\Database\Eloquent\Model;
use App\Models\Items\ItemGeneralQuantity;
use App\Models\PurchaseOrders\PurchaseOrderMaster;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductionItemMaster extends Model
{
    use HasFactory;

    protected $fillable = [
        'requested_by',
        'approved_by',
        'remarks',
        'production_remarks',
        'dispatch_remarks',
        'packing_remarks',
        'status',
        'production_status',
        'packing_status',
        'production_type',
        'sale_id',
        'requested_qty',
        'item_id',
        'entered_qty',
        'remaining_qty',
        'purchase_order_id',
        'machine_id',
        'real_number',
        'packed_by',
    ];

    /**
     * Get the production items associated with this production master.
     */
    public function productionItems(): HasMany
    {
        return $this->hasMany(ProductionItem::class, 'production_id', 'id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public static function getAll()
    {
        return self::all();
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderMaster::class, 'purchase_order_id', 'id');
    }

    public function productionLists()
    {
        return $this->hasMany(ProductionList::class);
    }

    public function packingLists()
    {
        return $this->hasMany(PackingList::class);
    }



}
