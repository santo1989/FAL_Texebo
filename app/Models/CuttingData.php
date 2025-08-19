<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuttingData extends Model
{
    use HasFactory;
    protected $fillable = [
        'date',
        'product_combination_id',
        'po_number',
        'old_order',
        'cut_quantities',
        'total_cut_quantity',
        'cut_waste_quantities',
        'total_cut_waste_quantity'
    ];

    protected $casts = [
        'cut_quantities' => 'array', // Cast to array to easily work with JSON
        'date' => 'date',
        'cut_waste_quantities' => 'array',
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
