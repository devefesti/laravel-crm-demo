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
        Schema::table('order_prods', function (Blueprint $table) {
            //
            /* $table->id();
            $table->string('order_id')->nullable()->change();
            $table->string('sku')->nullable()->change();
            $table->integer('quantity')->nullable()->change();
            $table->string('product_name')->nullable()->change();
            $table->timestamps(); */
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_prods', function (Blueprint $table) {
            //
        });
    }
};
