<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCouponFieldsToOrdersTable extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('coupon_code')->nullable()->after('address');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('coupon_code');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['coupon_code', 'discount_amount']);
        });
    }
}
