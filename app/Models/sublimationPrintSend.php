<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class sublimationPrintSend extends Model
{
    use HasFactory;
    protected $guarded = [];


    protected $casts = [
        'sublimation_print_send_quantities' => 'array',
        'sublimation_print_send_waste_quantities' => 'array'
    ];

    public function productCombination()
    {
        return $this->belongsTo(ProductCombination::class);
    }
}
