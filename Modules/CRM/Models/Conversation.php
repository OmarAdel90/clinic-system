<?php

namespace Modules\CRM\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Models\User;
use Modules\Lead\Models\Lead;
use Modules\Lead\Models\LeadStatusHistory;
use Modules\Visit\Models\Visit;

class Conversation extends Model
{
    use HasFactory;

        protected $fillable = [
        'lead_id',
        'assigned_user_id',
        'first_message_time',
        'last_message_time',
        'platform',
        'status',
        'lead_status',
        'unread_amount',
        'converted_at',
        'visit_id',
    ];

    protected $casts = [
        'first_message_time' => 'datetime',
        'last_message_time'  => 'datetime',
        'unread_amount'      => 'integer',
        'converted_at'       => 'datetime',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function followUps()
    {
        return $this->hasMany(FollowUp::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(LeadStatusHistory::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class, 'visit_id');
    }
}
