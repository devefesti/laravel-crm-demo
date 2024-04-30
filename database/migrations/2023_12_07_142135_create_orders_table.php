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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id');
            $table->string('status');
            $table->string('email');
            $table->string('firstname');
            $table->string('lastname');
            $table->decimal('totale');
            $table->string('street');
            $table->string('city');
            $table->string('post_code');
            $table->string('state');
            $table->string('country');
            $table->string('comment');
            $table->string('order_date');
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
        Schema::dropIfExists('orders');
    }
};
