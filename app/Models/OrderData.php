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

    public function productCombination()
    {
        return $this->belongsTo(ProductCombination::class);
    }

    public function cuttingData()
    {
        return $this->hasMany(CuttingData::class);
    }

    public function sublimationPrintSends()
    {
        return $this->hasMany(SublimationPrintSend::class);
    }

    public function sublimationPrintReceives()
    {
        return $this->hasMany(SublimationPrintReceive::class);
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

    public function outputFinishingData()
    {
        return $this->hasMany(OutputFinishingData::class);
    }


    public function finishPackingData()
    {
        return $this->hasMany(FinishPackingData::class);
    }

    public function packedData()
    {
        return $this->hasMany(FinishPackingData::class);
    }

    public function shipmentData()
    {
        return $this->hasMany(ShipmentData::class);
    }



}
