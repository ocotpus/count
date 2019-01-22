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

    protected $list;
    protected $getCouponRecordList;

    public function __construct()
    {
        $this->list = DB::table('coupons')->where('status',1)->get();
        $this->getCouponRecordList = DB::table('get_coupon_records')->get();
    }

    /**
     * 抽奖
     */
    public function lottery(Request $request)
    {
        $redis = Redis::connection('coupon');
        try {
//            $id = $request->input('id');
            $id = '555555555';
            //抽奖，得到本次抽奖概率

            $isDrawThePrize = $this->checkPrize($id);
            if ($isDrawThePrize === false) {
                return $this->response->array(['status' => '200', 'msg' => '您已经抽过奖了']);
            } else {
                $this->addGetCouponRecord($id);
            }
            $probability = $this->getProbability();
            if ($probability)  {
                //检查这个奖品数量是否被抽完，如果抽完，重新抽奖返回奖品信息，直到抽到有存量奖品;所有奖品都抽完，返回false
                $couponNum = $this->checkCouponNum();
                if(false === $couponNum) {
                    return $this->response->array(['status' => '200','msg' => '来晚了！优惠券已被洗劫一空！']);
                }
                DB::begintransAction();
                //获取第一条未抽中的优惠券字段
                $couponId = DB::table('coupons')->select('id', 'discount_code')->where('status',1)->first();
                //更新coupon表中奖ID字段
                $info = DB::table('coupons')->where('id', $couponId->id)->update(['get_coupon_id' => $id, 'status'=> 2]);
                //删除redis中该id的缓存
                $redis->del($couponId->discount_code);
                //写抽奖记录
                DB::commit();
                return $this->response->array(['status' => '200', 'msg'=> '成功', 'data' => $info]);
            } else {
                return $this->response->array(['status' => '200', 'msg' => '未中奖']);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error($e->getMessage());
            return $this->response->array(['status' => '400', 'msg' => '未中奖']);
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
        if (!empty($this->list)) {
            foreach ($this->list as $key => $l) {
                $redis->set($l->discount_code, $key);
            }
            return true;
        }
        return false;
    }

    /**
     * 写入抽奖信息记录到get_coupon_records表
     */
    private function addGetCouponRecord($id)
    {
        return GetCouponRecord::query()->create(['device_id' => $id]);
    }

    /**
     * 检查是否已经抽过奖
     */
    public function checkPrize($id)
    {
        $redis = Redis::connection('get_coupon_record');
        if (!empty($this->getCouponRecordList)) {
            foreach ($this->getCouponRecordList as $key => $l) {
                $redis->set($l->device_id, $key);
            }
        }
        if ($redis->get($id) === null) {
            return true;
        };
        return false;
    }
}