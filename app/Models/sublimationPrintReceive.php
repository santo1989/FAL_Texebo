<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class sublimationPrintReceive extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $casts = [
        'sublimation_print_receive_quantities' => 'array',
        'sublimation_print_receive_waste_quantities' => 'array'
    ];

    public function productCombination()
    {
        return $this->belongsTo(ProductCombination::class);
    }
}
