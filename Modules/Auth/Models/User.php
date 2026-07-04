<?php

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Modules\CRM\Models\CallCenterPerformanceMetrics;
use Modules\CRM\Models\CallCenterQueueEntry;
use Modules\CRM\Models\Conversation;
use Modules\CRM\Models\Message;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles;

    protected $table = 'users';

    protected $primaryKey = 'id';

    protected $guard_name = 'web';

    protected $fillable = [
        'name',
        'arabic_name',
        'email',
        'password',
        'SSN',
        'phone_number',
        'role_id',
        'location',
        'salary',
        'title',
        'specialization',
        'hired_at',
        'whatsapp_agent_number',
        'is_active',
        'last_active_at',
        'work_start',
        'work_end',
    ];

    protected $casts = [
        'salary' => 'float',
        'commission' => 'float',
        'hired_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'last_active_at' => 'datetime',
        'work_start' => 'datetime',
        'work_end' => 'datetime',
    ];

    protected $hidden = [
        'password',
    ];

    public function isOnline(): bool
    {
        return $this->last_active_at && $this->last_active_at->gt(now()->subMinutes(5));
    }

    public function isAway(): bool
    {
        return $this->last_active_at
            && $this->last_active_at->gt(now()->subMinutes(30))
            && !$this->isOnline();
    }

    public function assignedConversations()
    {
        return $this->hasMany(Conversation::class, 'assigned_user_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function metrics()
    {
        return $this->hasOne(CallCenterPerformanceMetrics::class, 'user_id');
    }

    public function queueEntry()
    {
        return $this->hasOne(CallCenterQueueEntry::class, 'user_id');
    }
}
