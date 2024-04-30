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
        Schema::table('order_packs', function (Blueprint $table) {
            /* $table->foreign('order_id')
                ->references('order_id')->on('orders')->onDelete('cascade');
            $table->foreign('pack_id')
                ->references('sku')->on('products')->onDelete('cascade');*/
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
