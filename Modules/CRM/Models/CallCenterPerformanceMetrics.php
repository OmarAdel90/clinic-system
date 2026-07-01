<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Models\User;

class CallCenterPerformanceMetrics extends Model
{
    use HasFactory;

        protected $table = 'call_center_performance_metrics';

    protected $fillable = [
        'user_id',
        'average_response_time',
        'total_number_of_leads',
        'total_converted_leads',
        'total_reminders',
        'total_customer_attendance',
        'date',
    ];

    protected $casts = [
        'average_response_time'     => 'float',
        'total_number_of_leads'     => 'integer',
        'total_converted_leads'     => 'integer',
        'total_reminders'           => 'integer',
        'total_customer_attendance' => 'integer',
        'date'                      => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
