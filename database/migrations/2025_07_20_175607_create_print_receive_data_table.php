<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrintReceiveDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('print_receive_data', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->unsignedBigInteger('product_combination_id');
            $table->string('po_number'); // Purchase Order number
            $table->string('old_order')->nullable(); // Optional: to store old order number if work start form there
            $table->json('receive_quantities'); // Stores quantities for each size
            $table->integer('total_receive_quantity')->nullable();
            //stores waste quantities for each size, e.g., {'1': 500, '2': 500}
            $table->json('receive_waste_quantities')->nullable(); // Optional: to store waste quantities for each size
            $table->integer('total_receive_waste_quantity')->nullable(); // Optional: to store total waste quantity
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
        Schema::dropIfExists('print_receive_data');
    }
}
