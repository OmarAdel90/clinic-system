<?php

namespace Modules\CRM\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Models\User;

class CallCenterQueueEntry extends Model
{
    use HasFactory;

        protected $table = 'call_center_queue_entries';

    protected $fillable = [
        'user_id',
        'position',
        'is_active',
    ];

    protected $casts = [
        'position'  => 'integer',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position', 'asc');
    }
}
