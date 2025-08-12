<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShipmentDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shipment_data', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->unsignedBigInteger('product_combination_id');
            $table->string('po_number'); // Purchase Order number
            $table->string('old_order')->nullable(); // Optional: to store old order number if work start form there
            $table->json('shipment_quantities'); // Stores quantities per size
            $table->integer('total_shipment_quantity')->default(0);
            // Optional: to store waste quantities for each size
            $table->json('shipment_waste_quantities')->nullable(); // Optional: to store waste quantities for each size
            $table->integer('total_shipment_waste_quantity')->nullable(); // Optional: to store total waste quantity
            $table->timestamps();

            $table->foreign('product_combination_id')
                ->references('id')
                ->on('product_combinations')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shipment_data');
    }
}
