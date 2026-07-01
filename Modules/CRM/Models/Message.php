<?php

namespace Modules\CRM\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Models\User;
use Modules\Lead\Models\Lead;

class Message extends Model
{
    use HasFactory;

        protected $fillable = [
        'conversation_id',
        'lead_id',
        'user_id',
        'reply_to_message_id',
        'wa_message_id',
        'direction',
        'type',
        'body',
        'media_url',
        'media_caption',
        'media_mime',
        'media_size',
        'payload',
        'status',
        'sent_at',
        'delivered_at',
        'read_at',
        'failed_at',
        'error_message',
    ];

    protected $casts = [
        'payload'       => 'array',
        'sent_at'       => 'datetime',
        'delivered_at'  => 'datetime',
        'read_at'       => 'datetime',
        'failed_at'     => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function contact()
    {
        return $this->belongsTo(Lead::class, 'Lead_id');
    }

    public function replyTo()
    {
        return $this->belongsTo(self::class, 'reply_to_message_id');
    }

    public function replies()
    {
        return $this->hasMany(self::class, 'reply_to_message_id');
    }
}
