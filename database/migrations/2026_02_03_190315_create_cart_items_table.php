<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCartItemsTable extends Migration
{
    public function up()
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cart_id')
                ->constrained('carts')
                ->onDelete('cascade');

            $table->foreignId('product_id')
                ->constrained('products')
                ->onDelete('cascade');

            $table->string('size', 20)->nullable();   // selected size (S, M, L, 42, etc.)
            $table->integer('quantity');              // number of items
            $table->decimal('unit_price', 10, 2);     // snapshot of product price

            $table->timestamps();

            // prevent duplicates of same (product, size) in same cart
            $table->unique(['cart_id', 'product_id', 'size']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('cart_items');
    }
}
