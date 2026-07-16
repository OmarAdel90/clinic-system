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
        'ad_account_name',
        'platform',
        'description',
        'start_date',
        'end_date',
        'budget',
        'currency',
        'status',
        'objective',
        'meta_source',
        'spend',
        'impressions',
        'clicks',
        'ctr',
        'cpc',
        'results',
        'result_label',
        'ad_sets',
        'metrics_synced_at',
    ];

    protected $casts = [
        'id'         => 'string',
        'start_date' => 'date',
        'end_date'   => 'date',
        'budget'     => 'float',
        'spend'      => 'float',
        'impressions' => 'integer',
        'clicks'     => 'integer',
        'ctr'        => 'float',
        'cpc'        => 'float',
        'results'    => 'float',
        'ad_sets'    => 'array',
        'metrics_synced_at' => 'datetime',
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
