<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutputFinishingData extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $casts = [
        'output_quantities' => 'array',
        'output_waste_quantities' => 'array'
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
