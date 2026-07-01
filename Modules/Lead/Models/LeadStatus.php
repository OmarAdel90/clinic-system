<?php

namespace Modules\Lead\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;
class LeadStatus extends Model
{
    use HasFactory;

        protected $table = 'lead_status';
    protected $fillable = [
        'label',
        'key',
        'color',
        'is_qualified',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_qualified' => 'boolean',
        'is_active'    => 'boolean',
        'sort_order'   => 'integer',
    ];

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function scopeActiveOrdered($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function scopeAllKeyed($query)
    {
        return $query->get()->keyBy('key');
    }
}
