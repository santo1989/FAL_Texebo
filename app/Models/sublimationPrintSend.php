<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class sublimationPrintSend extends Model
{
    use HasFactory;
    protected $fillable = [
        'date',
        'product_combination_id',
        'po_number',
        'old_order',
        'sublimation_print_send_quantities',
        'total_sublimation_print_send_quantity',
        'sublimation_print_send_waste_quantities',
        'total_sublimation_print_send_waste_quantity'
    ];

    protected $casts = [
        'sublimation_print_send_quantities' => 'array',
        'sublimation_print_send_waste_quantities' => 'array',
    ];

    public function productCombination()
    {
        return $this->belongsTo(ProductCombination::class);
    }

}
