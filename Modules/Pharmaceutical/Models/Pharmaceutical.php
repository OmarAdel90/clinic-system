<?php

namespace Modules\Pharmaceutical\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class Pharmaceutical extends Model
{
    use HasFactory;

        public $incrementing = false;
    protected $primaryKey = 'SKU';
    protected $keyType = 'string';
    protected $fillable = [
        'SKU',
        'name',
        'arabic_name',
        'photo',
        'sale_price',
        'description',
        'attribute',
    ];
    protected $casts = [
        'sale_price' => 'float',
        'attribute' => 'array'
    ];

}
