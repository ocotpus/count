<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCouponsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->increments('id');
            $table->string('discount_code');
            $table->tinyInteger('status')->defaule('1')->comment('1 未被抽中；2 已抽中');
            $table->string('get_coupon_id')->default(null)->nullable()->comment('抽中优惠券的设备id');
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
        Schema::dropIfExists('coupons', function (Blueprint $table) {
            //
        });
    }
}
