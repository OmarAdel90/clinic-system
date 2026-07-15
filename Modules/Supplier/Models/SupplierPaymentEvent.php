<?php

namespace Modules\Supplier\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Models\User;

class SupplierPaymentEvent extends Model
{
    use HasFactory;

    protected $table = 'supplier_payment_events';

    protected $fillable = [
        'supplier_payment_history_id',
        'amount',
        'paid_at',
        'recorded_by',
        'notes',
    ];

    protected $casts = [
        'amount' => 'float',
        'paid_at' => 'datetime',
    ];

    public function paymentHistory()
    {
        return $this->belongsTo(SupplierPaymentHistory::class, 'supplier_payment_history_id');
    }

    public function recordedByUser()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
