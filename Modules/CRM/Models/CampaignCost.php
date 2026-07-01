<?php

namespace Modules\CRM\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;
class CampaignCost extends Model
{
    use HasFactory;

        protected $table = 'campaign_cost';
    protected $fillable = [
        'campaign_id',
        'cost',
        'currency',
        'customer_cost',
        'converted_lead_count',
        'notes',
    ];

    protected $casts = [
        'cost'                 => 'float',
        'customer_cost'        => 'float',
        'converted_lead_count' => 'integer',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
