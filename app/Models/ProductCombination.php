<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCombination extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $casts = [
        'size_ids' => 'array',
        'is_active' => 'boolean',
    ];

    public function buyer()
    {
        return $this->belongsTo(Buyer::class);
    }

    public function style()
    {
        return $this->belongsTo(Style::class);
    }

    public function color()
    {
        return $this->belongsTo(Color::class);
    }

    public function size()
    {
        return $this->belongsTo(Size::class);
    }


    /**
     * Accessor: Get the size models based on size_ids array
     */
    public function getSizesAttribute()
    {
        return Size::whereIn('id', $this->size_ids ?? [])->get();
    }

    /**
     * Optional: Get just size IDs
     */
    public function getSizeIdListAttribute()
    {
        return $this->size_ids ?? [];
    }

    

    public function cuttingData()
    {
        return $this->hasMany(CuttingData::class);
    }

    public function printSends()
    {
        return $this->hasMany(PrintSendData::class);
    }

    public function printReceives()
    {
        return $this->hasMany(PrintReceiveData::class);
    }

    public function lineInputData()
    {
        return $this->hasMany(LineInputData::class);
    }

    public function finishPackingData()
    {
        return $this->hasMany(FinishPackingData::class);
    }
}
