<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
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

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Cash or Card (from your proposal)
            $table->enum('payment_method', ['cash', 'card'])->default('cash');

            // pending -> approved -> delivered OR rejected / cancelled.
            // 'cancelled' is listed here so fresh installs (and the SQLite
            // test DB, where the later MySQL-only enum migration is skipped)
            // accept it; on MySQL the later migration is an idempotent no-op.
            $table->enum('status', ['pending', 'approved', 'rejected', 'delivered', 'cancelled'])
                  ->default('pending');

            $table->decimal('total', 10, 2)->default(0);

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
}
