<?php

namespace Modules\CRM\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Models\User;

class FollowUp extends Model
{
    use HasFactory;

        protected $table = 'follow_up';
    protected $fillable = [
        'conversation_id',
        'user_id',
        'due_at',
        'completed_at',
        'body',
    ];

    protected $casts = [
        'due_at'        => 'datetime',
        'completed_at'  => 'datetime',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending($query)
    {
        return $query->whereNull('completed_at');
    }

    public function scopeDueOrOverdue($query)
    {
        return $query->whereNull('completed_at')->where('due_at', '<=', now());
    }
}
