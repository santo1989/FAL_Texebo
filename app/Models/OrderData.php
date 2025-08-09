<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderData extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $casts = [
        'order_quantities' => 'array'
    ];

    public function style()
    {
        return $this->belongsTo(Style::class);
    }

    public function color()
    {
        return $this->belongsTo(Color::class);
    }

  

    public function productCombination()
    {
        return $this->belongsTo(ProductCombination::class);
    }
}
