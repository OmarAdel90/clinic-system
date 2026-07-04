<?php

namespace Modules\Invoice\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Clinic\Models\Clinic;
use Modules\Lead\Models\Lead;
use Modules\TreatmentPlan\Models\TreatmentPlan;
use Modules\Visit\Models\Report;

class Invoice extends Model
{
    use HasFactory;

        protected $fillable = [
        'lead_id',
        'clinic_id',
        'report_id',
        'treatment_plan_id',
        'invoice_number',
        'services_cost',
        'supplies_cost',
        'total_cost',
        'amount_paid',
        'status',
        'issued_at',
        'due_date',
    ];

    protected $casts = [
        'services_cost' => 'float',
        'supplies_cost' => 'float',
        'total_cost'    => 'float',
        'amount_paid'   => 'float',
        'issued_at'     => 'datetime',
        'due_date'      => 'datetime',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    public function treatmentPlan()
    {
        return $this->belongsTo(TreatmentPlan::class);
    }
}
