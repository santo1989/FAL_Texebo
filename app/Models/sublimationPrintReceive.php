<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class sublimationPrintReceive extends Model
{
    use HasFactory;
    protected $fillable = [
        'date',
        'product_combination_id',
        'po_number',
        'old_order',
        'sublimation_print_receive_quantities',
        'total_sublimation_print_receive_quantity',
        'sublimation_print_receive_waste_quantities',
        'total_sublimation_print_receive_waste_quantity'
    ];

    protected $casts = [
        'sublimation_print_receive_quantities' => 'array',
        'sublimation_print_receive_waste_quantities' => 'array',
    ];

    public function productCombination()
    {
        return $this->belongsTo(ProductCombination::class);
    }
}
