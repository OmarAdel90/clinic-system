<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    protected $fillable = ['source', 'event_type', 'payload', 'headers', 'processed_at', 'error'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'headers' => 'array', 'processed_at' => 'datetime'];
    }
}
