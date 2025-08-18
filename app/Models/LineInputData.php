<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LineInputData extends Model
{
    use HasFactory;
    protected $guarded = [];


    protected $casts = [
        'input_quantities' => 'array',
        'input_waste_quantities' => 'array'
    ];

    public function productCombination()
    {
        return $this->belongsTo(ProductCombination::class);
    }

    public function orderData()
    {
        return $this->belongsTo(OrderData::class, 'po_number', 'po_number');
    }
}
