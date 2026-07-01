<?php

namespace Modules\Visit\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Models\User;
use Modules\Clinic\Models\Clinic;
use Modules\Invoice\Models\Invoice;
use Modules\Lead\Models\Lead;

class Report extends Model
{
    use HasFactory;

        protected $fillable = [
        'clinic_id',
        'user_id',
        'lead_id',
        'visit_id',
        'visit_date',
        'diagnosis',
        'treatment_notes',
        'supplies_used',
        'body',
        'status',
    ];

    protected $casts = [
        'visit_date'    => 'datetime',
        'supplies_used' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class, 'clinic_id');
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'report_id');
    }
}
