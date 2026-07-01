<?php

namespace Modules\Supplier\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;
use Modules\Transaction\Models\WarehouseSupplierTransaction;

class SupplierPaymentHistory extends Model
{
    use HasFactory;

        public $table = 'supplier_payment_history';
    protected $fillable = [
        'transaction_id',
        'supplier_id',
        'batch_id',
        'total_amount',
        'total_paid',
        'payment_status'
    ];

    protected $casts = [
        'total_amount' => 'float',
        'total_paid'   => 'float',
    ];

    public function transaction()
    {
        return $this->belongsTo(WarehouseSupplierTransaction::class, 'transaction_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
