<?php

namespace Modules\Warehouse\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Clinic\Models\Clinic;
use Modules\Supplier\Models\Supplier;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'name',
    ];

    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class);
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class, 'clinic_id');
    }

    public function inventories()
    {
        return $this->hasMany(WarehouseInventory::class);
    }
}
