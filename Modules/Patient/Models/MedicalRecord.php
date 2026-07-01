<?php

namespace Modules\Patient\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Models\User;
use Modules\Lead\Models\Lead;

class MedicalRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'type',
        'file_path',
        'original_name',
        'mime_type',
        'notes',
        'uploaded_by',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
