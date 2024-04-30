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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status')->nullable()->change();
            $table->string('email')->nullable()->change();
            $table->string('firstname')->nullable()->change();
            $table->string('lastname')->nullable()->change();
            $table->decimal('totale')->nullable()->change();
            $table->string('street')->nullable()->change();
            $table->string('city')->nullable()->change();
            $table->string('post_code')->nullable()->change();
            $table->string('state')->nullable()->change();
            $table->string('country')->nullable()->change();
            $table->string('order_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            //
        });
    }
};
