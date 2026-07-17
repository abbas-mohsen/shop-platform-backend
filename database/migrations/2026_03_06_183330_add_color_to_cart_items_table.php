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

            // Extend the uniqueness constraint to include color.
            // Add the wider index BEFORE dropping the old one: it also starts
            // with cart_id, so it can back the cart_id foreign key — MySQL 8
            // refuses to drop the only index supporting an FK (error 1553).
            $table->unique(['cart_id', 'product_id', 'size', 'color']);
            $table->dropUnique('cart_items_cart_id_product_id_size_unique');
        });
    }

    public function down()
    {
        Schema::table('cart_items', function (Blueprint $table) {
            // Same ordering rule as up(): add the replacement index before
            // dropping the one that backs the cart_id foreign key.
            $table->unique(['cart_id', 'product_id', 'size']);
            $table->dropUnique(['cart_id', 'product_id', 'size', 'color']);
            $table->dropColumn('color');
        });
    }
}
