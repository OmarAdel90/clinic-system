<?php

namespace Modules\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Models\User;
use Modules\CRM\Models\Conversation;
class LeadStatusHistory extends Model
{
    protected $table = 'lead_status_history';
    protected $fillable = [
        'conversation_id',
        'user_id',
        'from_status',
        'to_status',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
