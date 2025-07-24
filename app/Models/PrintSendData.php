<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrintSendData extends Model
{
    use HasFactory;
    protected $guarded = [];

    // protected $fillable = [
    //     'date',
    //     'product_combination_id',
    //     'send_quantities',
    //     'total_send_quantity'
    // ];

    protected $casts = [
        'send_quantities' => 'array'
    ];

    public function productCombination()
    {
        return $this->belongsTo(ProductCombination::class);
    }
}
