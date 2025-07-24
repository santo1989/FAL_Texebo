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
        'cut_quantities',
        'total_cut_quantity',
    ];

    protected $casts = [
        'cut_quantities' => 'array', // Cast to array to easily work with JSON
        'date' => 'date',
    ];

    public function productCombination()
    {
        return $this->belongsTo(ProductCombination::class);
    }
}
