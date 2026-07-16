<?php

namespace Modules\CRM\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;
use Modules\Lead\Models\Lead;
class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'name',
        'ad_account_id',
        'platform',
        'description',
        'start_date',
        'end_date',
        'budget',
        'currency',
        'status',
        'objective',
        'meta_source',
    ];

    protected $casts = [
        'id'         => 'string',
        'start_date' => 'date',
        'end_date'   => 'date',
        'budget'     => 'float',
    ];

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function costs()
    {
        return $this->hasMany(CampaignCost::class);
    }
}
