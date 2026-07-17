<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCartsTable extends Migration
{
    public function up()
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade'); // delete cart if user is deleted
            $table->timestamps();

            $table->unique('user_id'); // one active cart per user
        });
    }

    public function down()
    {
        Schema::dropIfExists('carts');
    }
}
