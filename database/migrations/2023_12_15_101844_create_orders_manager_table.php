<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders_operators', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique();
            $table->string('order_state');
            $table->string('operator');
            $table->string('date');
            //segnalzione in caso di problemi nell'evadere l'ordine
            $table->text('report');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders_manager');
    }
};
