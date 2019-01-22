<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GetCouponRecord extends Model
{
    protected $table = 'get_coupon_records';

    public $timestamps = true;

    public $fillable = [
        'device_id',
    ];
}
