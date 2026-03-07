<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColorToCartItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->string('color', 50)->nullable()->after('size');

            // Extend the uniqueness constraint to include color
            $table->dropUnique('cart_items_cart_id_product_id_size_unique');
            $table->unique(['cart_id', 'product_id', 'size', 'color']);
        });
    }

    public function down()
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique(['cart_id', 'product_id', 'size', 'color']);
            $table->dropColumn('color');
            $table->unique(['cart_id', 'product_id', 'size']);
        });
    }
}
