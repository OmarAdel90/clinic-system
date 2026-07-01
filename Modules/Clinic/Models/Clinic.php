<?php

namespace Modules\Clinic\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Models\User;
use Modules\Warehouse\Models\Warehouse;

class Clinic extends Model
{
    use HasFactory;

        protected $fillable = [
        'name',
        'arabic_name',
        'provides_medication',
        'departments',
        'doctors',
        'services',
        'phone_number',
        'address',
    ];

    protected $casts = [
        'provides_medication' => 'boolean',
        'departments'         => 'array',
        'doctors'             => 'array',
        'services'            => 'array',
    ];

    public function warehouse()
    {
        return $this->hasOne(Warehouse::class, 'clinic_id');
    }
}
