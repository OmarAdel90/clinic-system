<?php

namespace Modules\Transaction\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;
use Modules\Warehouse\Models\Warehouse;
use Modules\Supplier\Models\Supplier;

class WarehouseSupplierTransaction extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $primaryKey = 'transaction_id';
    protected $keyType = 'string';
    protected $fillable = [
        'warehouse_id',
        'supplier_id',
        'items_bought',
        'transaction_date'
    ];
    protected $casts = [
        'transaction_date' => 'datetime',
        'items_bought' => 'array'
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->transaction_id)) {
                $model->transaction_id = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

}
