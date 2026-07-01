<?php

namespace Modules\TreatmentPlan\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Models\User;
use Modules\Clinic\Models\Clinic;
use Modules\Lead\Models\Lead;
use Modules\Visit\Models\Visit;

class TreatmentPlan extends Model
{
    use HasFactory;

        protected $fillable = [
        'lead_id',
        'user_id',
        'clinic_id',
        'diagnosis',
        'notes',
        'type',
        'total_visits',
        'status',
    ];

    protected $casts = [
        'total_visits' => 'integer',
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

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }
}
