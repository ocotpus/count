<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    public $table = 'coupons';
    public $timestamps = true;
    public $fillable = [
        'discount_code',
        'status',
        'get_coupon_id',
    ];
}
