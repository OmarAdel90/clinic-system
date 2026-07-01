<?php

namespace Modules\CRM\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Models\User;
use Modules\Lead\Models\Lead;

class AssignmentState extends Model
{
    use HasFactory;

        protected $table = 'assignment_state';
    protected $fillable = [
        'lead_id',
        'user_id',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
