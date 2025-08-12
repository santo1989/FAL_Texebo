<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinishPackingDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('finish_packing_data', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->unsignedBigInteger('product_combination_id');
            $table->string('po_number'); // Purchase Order number
            $table->string('old_order')->nullable(); // Optional: to store old order number if work start form there
            $table->json('packing_quantities'); // Stores quantities per size
            $table->integer('total_packing_quantity')->nullable();
            // Optional: to store waste quantities for each size
            $table->json('packing_waste_quantities')->nullable(); // Optional: to store waste quantities for each size
            $table->integer('total_packing_waste_quantity')->nullable(); // Optional: to store total waste quantity
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
        Schema::dropIfExists('finish_packing_data');
    }
}
