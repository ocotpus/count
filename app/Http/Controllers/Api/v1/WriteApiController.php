<?php

namespace App\Http\Controllers\Api\v1;


use App\GetCouponRecord;
use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class WriteApiController
{
    use Helpers;

    /**
     * 抽奖
     */
    public function lottery(Request $request)
    {
        $redis = Redis::connection('coupon');
        try {
            $id = $request->input('id');
//            $id = '555555555';
            //查看是否已经抽过奖
            $isDrawThePrize = $this->checkPrize($id);
            if ($isDrawThePrize === false) {
                $couponInfo = DB::table('coupons')->select('id', 'discount_code')->where('status',2)->where('get_coupon_id', $id)->first();
                if ($couponInfo) {
                    return $this->response->array(['code' => '0', 'coupon' => $couponInfo->discount_code]);
                }
                return $this->response->array(['code' => '1']);
            } else {
                $this->addGetCouponRecord($id);
            }
            //抽奖，得到本次抽奖概率
            $probability = $this->getProbability();
            if ($probability)  {
                //检查这个奖品数量是否被抽完
                $couponNum = $this->checkCouponNum();
                if(false === $couponNum) {
                    return $this->response->array(['code' => '1']);
                } else {
                    //获取第一条未抽中的优惠券字段
                    $couponId = DB::table('coupons')->select('id', 'discount_code')->where('status',1)->first();
                    //更新coupon表中奖ID字段
                    DB::table('coupons')->where('id', $couponId->id)->update(['get_coupon_id' => $id, 'status'=> 2, 'updated_at' => date('Y-m-d H:i:s')]);
                    $info = DB::table('coupons')->select('discount_code')->where('id', $couponId->id)->first();
                    //删除redis中该id的缓存
                    $redis->del($couponId->discount_code);
                    //写抽奖记录
                    return $this->response->array(['code' => '0', 'coupon:' => $info->discount_code]);
                }
            } else {
                return $this->response->array(['code' => '1']);
            }
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return $this->response->array(['code' => '2']);
        }
    }

    /**
     *  抽奖实现，获取奖品概率
     */
    private function getProbability()
    {
        $probability = 0.1;
        //生成一个随机数
        $random = mt_rand(1, 1000);
        return $random / 1000 <= $probability;
    }

    /**
     * 检查奖品是否抽完
     */
    private function checkCouponNum()
    {
        $redis = Redis::connection('coupon');
        if (empty($redis->keys('*'))) {
            $list = DB::table('coupons')->where('status',1)->get()->toArray();
            if (!empty($list)) {
                foreach ($list as $key => $l) {
                    $redis->set($l->discount_code, $key);
                }
                return true;
            }
            return false;
        }
        return true;
    }

    /**
     * 写入抽奖信息记录到get_coupon_records表并加入redis缓存
     */
    private function addGetCouponRecord($id)
    {
        $redis = Redis::connection('get_coupon_record');
        $data = GetCouponRecord::query()->create(['device_id' => $id]);
        $redis->set($id, $data->id);
        return $data;
    }

    /**
     * 检查是否已经抽过奖
     */
    public function checkPrize($id)
    {
        $redis = Redis::connection('get_coupon_record');
        return $redis->get($id) === null ? true : false;
    }
}