<?php

namespace Modules\Warehouse\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Pharmaceutical\Models\Pharmaceutical;

class WarehouseInventory extends Model
{
    use HasFactory;

        protected $table = 'warehouse_inventories';
    protected $fillable = [
        'warehouse_id',
        'sku',
        'name',
        'arabic_name',
        'quantity',
        'reserved_quantity',
    ];
    protected $casts = [
        'quantity'          => 'integer',
        'reserved_quantity' => 'integer',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function pharmaceutical()
    {
        return $this->belongsTo(Pharmaceutical::class, 'sku', 'SKU');
    }

    public function getAvailableAttribute(): int
    {
        return $this->quantity - $this->reserved_quantity;
    }
}
