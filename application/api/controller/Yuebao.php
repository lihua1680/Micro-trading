<?php

namespace app\api\controller;

use think\Db;

class Yuebao extends ApiBase
{

    // 类型 1已确定 2待确定 3收益 4转出
    /*
     *
    CREATE TABLE `fh_yu` (
  `share_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `type` int(11) NOT NULL DEFAULT '2' COMMENT '类型 1 已确定 2待确定 3收益 4转出',
  `create_time` datetime NOT NULL,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`share_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

  `yue_start_money` decimal(10,2) DEFAULT '0.00',
  `yue_stop_money` decimal(10,2) DEFAULT '0.00',
  `yue_shouyi` decimal(10,2) DEFAULT '0.00',

     */

    public function getdata()
    {
        // 获取今天的开始和结束时间（假设create_time是日期时间类型）
        $todayStart = date('Y-m-d 00:00:00', time());
        $todayEnd = date('Y-m-d 23:59:59', time());

        $user = Db::name('user')->where(array('id' => getUid()))->find();
        $user['yue_stop_money'] = Db::name('yu')->where('user_id',getUid())
            ->where('type',2)
            ->where('status',2)
            ->sum('amount');
        $all_money = $user['yue_start_money'] + $user['yue_stop_money'];
        $ru_count = Db::name('yu')->where('type',2)
            ->where('create_time', '>=', $todayStart)
            ->where('create_time', '<=', $todayEnd)
            ->count();
        $chu_count = Db::name('yu')->where('type',4)
            ->where('create_time', '>=', $todayStart)
            ->where('create_time', '<=', $todayEnd)
            ->count();

//        $user['yue_start_money'] = 80.00;
//        $all_money = 100;
        if (!empty($user['yue_start_money']) && $all_money){
            $zb = ($user['yue_start_money'] / $all_money) ;
        }else{
            $zb = 0;
        }
        return ApiSuccess('',[
           'yield' => '1%',
           'zb' => $zb,
           'sy' => '0.00',
           'all_money' => $all_money,
           'yue_start_money' => $user['yue_start_money'], //已确认份额
           'yue_stop_money' => $user['yue_stop_money'],// 待确认份额
           'ru_count' => (int)(60 - $ru_count),
           'chu_count' => (int)(60 - $chu_count),
        ]);
    }
//
//`share_id` int(11) NOT NULL AUTO_INCREMENT,
//`user_id` int(11) NOT NULL,
//`amount` decimal(10,2) NOT NULL,
//`type` int(11) NOT NULL DEFAULT '2' COMMENT '类型 1 已确定 2待确定 3收益 4转出',
//`create_time` datetime NOT NULL,
//`update_time` datetime DEFAULT NULL,
    // 购买
    public function buy()
    {
        $amount = input('amount');
        if (empty($amount)){
            return ApiError('缺少参数！');
        }
        // 最低限制
        if ($amount > get_user_byid(getUid(), 'money')) {
            return ApiError(lang('yuebuzu'));
        }
        try {
            setBalnce(array('user_id'=>getUid(), 'money'=>$amount), 0);
            Db::name('yu')->insert([
                'user_id' => getUid(),
                'amount' => $amount,
                'type' => 2,
                'create_time' => date('Y-m-d H:i:s'),
            ]);
        }catch (\Exception $exception){
            return ApiError(lang('cunrushibai'));
        }

        return ApiSuccess(lang('cunruchenggong'));

    }

    // 卖出
    public function sell()
    {
        $amount = input('amount');
        if (empty($amount)){
            return ApiError('缺少参数！');
        }

        $yue_start_money =  Db::name('user')->where('id',getUid())->value('yue_start_money');
        if ($amount > $yue_start_money) {
            return ApiError(lang('yuebuzu'));
        }

        try {
            // 减少已确认的金额
            Db::name('user')->where('id',getUid())->setDec('yue_start_money',$amount);

            setBalnce(array('user_id'=>getUid(), 'money'=>$amount), 1);
            // 减少 已确认
            Db::name('yu')->insert([
                'user_id' => getUid(),
                'amount' => $amount,
                'type' => 4,
                'create_time' => date('Y-m-d H:i:s'),
            ]);
        }catch (\Exception $exception){
            return ApiError(lang('cunrushibai'));
        }

        return ApiSuccess(lang('cunruchenggong'));
    }

    // 明细
    public function log()
    {
        $field = 'share_id,amount,type,create_time';
        $where['user_id'] =getUid();
        if (!empty(input('type','')) && input('type','') != 1){
            $where['type'] = input('type');
        }
        $lists = Db::name('yu')->where($where)->order('share_id desc')->field($field)->paginate(10);
        return ApiSuccess('',[
            'lists' => $lists->items(),
            'page' => input("page",1),
            'currentPage' => $lists->currentPage(),
            'lastPage' => $lists->lastPage(),
        ]);

    }




}