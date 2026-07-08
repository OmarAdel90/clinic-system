<?php

namespace Modules\Lead\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Models\User;
use Modules\Clinic\Models\Clinic;
use Modules\CRM\Models\Campaign;
use Modules\CRM\Models\Conversation;
use Modules\CRM\Models\AssignmentState;

class Lead extends Model
{
    use HasFactory;

        protected $fillable = [
        'campaign_id',
        'clinic_id',
        'clinic_assigned_by',
        'clinic_assigned_at',
        'platform',
        'whatsapp_id',
        'phone',
        'name',
        'profile_name',
        'metadata',
        'lead_status_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'clinic_assigned_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function leadStatus()
    {
        return $this->belongsTo(LeadStatus::class);
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function clinicAssignedBy()
    {
        return $this->belongsTo(User::class, 'clinic_assigned_by');
    }

    public function assignmentState()
    {
        return $this->hasOne(AssignmentState::class);
    }
}
