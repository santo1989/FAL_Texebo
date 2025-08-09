<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_data', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('po_number');
            $table->unsignedBigInteger('style_id');
            $table->unsignedBigInteger('color_id');
            $table->unsignedBigInteger('product_combination_id');
            $table->json('order_quantities'); // Stores quantities per size
            $table->integer('total_order_quantity')->default(0);
            $table->string('po_status')->default('running'); // New field for PO status, running, completed, or cancelled
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
        Schema::dropIfExists('order_data');
    }
}
