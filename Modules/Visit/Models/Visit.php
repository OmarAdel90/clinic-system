<?php

namespace Modules\Visit\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Models\User;
use Modules\Clinic\Models\Clinic;
use Modules\Lead\Models\Lead;
use Modules\TreatmentPlan\Models\TreatmentPlan;

class Visit extends Model
{
    use HasFactory;

        protected $fillable = [
        'lead_id',
        'user_id',
        'clinic_id',
        'treatment_plan_id',
        'conversation_id',
        'visit_number',
        'scheduled_date',
        'confirmed_at',
        'actual_date',
        'status',
        'supplies_reserved',
        'services_cost',
        'supplies_cost',
        'total_cost',
        'report_id',
    ];

    protected $casts = [
        'scheduled_date'    => 'datetime',
        'confirmed_at'      => 'datetime',
        'actual_date'       => 'datetime',
        'supplies_reserved' => 'array',
        'services_cost'     => 'float',
        'supplies_cost'     => 'float',
        'total_cost'        => 'float',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class, 'clinic_id');
    }

    public function treatmentPlan()
    {
        return $this->belongsTo(TreatmentPlan::class);
    }

    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    public function conversation()
    {
        return $this->belongsTo(\Modules\CRM\Models\Conversation::class, 'conversation_id');
    }
}
