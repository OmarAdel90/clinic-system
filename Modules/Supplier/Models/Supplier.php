<?php

namespace Modules\Supplier\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;
use Modules\Warehouse\Models\Warehouse;
use Modules\Transaction\Models\WarehouseSupplierTransaction;

class Supplier extends Model
{
    use HasFactory;

        protected $fillable = [
        'name',
        'phone_number'
    ];

    public function warehouses()
    {
        return $this->belongsToMany(Warehouse::class);
    }

    public function transactions()
    {
        return $this->hasMany(WarehouseSupplierTransaction::class);
    }

    public function paymentHistories()
    {
        return $this->hasMany(SupplierPaymentHistory::class);
    }

    public function totalOwed(): float
    {
        return (float) $this->paymentHistories()->sum('total_amount');
    }

    public function totalPaid(): float
    {
        return (float) $this->paymentHistories()->sum('total_paid');
    }

    public function balanceOwed(): float
    {
        return $this->totalOwed() - $this->totalPaid();
    }
}
