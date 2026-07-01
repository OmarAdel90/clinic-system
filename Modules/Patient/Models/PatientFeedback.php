<?php

namespace Modules\Patient\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Models\User;
use Modules\Clinic\Models\Clinic;
use Modules\Lead\Models\Lead;

class PatientFeedback extends Model
{
    protected $table = 'patient_feedback';

    protected $fillable = [
        'lead_id',
        'user_id',
        'clinic_id',
        'feedback_body',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class, 'clinic_id');
    }
}
