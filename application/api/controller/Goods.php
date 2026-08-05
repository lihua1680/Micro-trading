<?php

namespace app\api\controller;

use think\Config;
use think\Db;

class Goods extends ApiBase
{

    // 下单 购买
    public function microtrade()
    {
        $param = $this->request->param();

        // if (getUserById(getUid(), 'is_auth') != 2) {
        //     return ApiError(lang('qingshimrenzhen'));
        // }

        $site = Config::get('site');
        if (getUserById(getUid(), 'freeze') == 1) {
            return ApiError(lang('zhanghaobeidunjie'));
        }
        // 判断是否在交易时间内
        if(!empty($site['trade_time'])){
            $trade_timeStr = $site['trade_time'];
            $trade_time = explode("-",$trade_timeStr);
            $startTimeStr = $trade_time[0];
            $startTime = strtotime(date("Y-m-d {$startTimeStr}:00"));
            $endTimeStr = $trade_time[1];
            $endTime = strtotime(date("Y-m-d {$endTimeStr}:00"));
            $nowTime = time();
            if(!($nowTime >= $startTime && $nowTime <= $endTime)){
                return ApiError(lang('shichangxiuxhi {:trade_timeStr} shijianduanneijiaoyi',['trade_timeStr'=>$trade_timeStr]));
            }
        }


        if ($param['number'] > $site['bet_max'] || $param['number'] < $site['bet_min']) {
            return ApiError(lang('zuixiaogoumai {:bet_min} zuidagoumai {:bet_max}',$site));
        }

            $stock = getStockByCode($param['currency_id']);
        if (!$stock) {
            return ApiError(lang('jiaoyipinglbucunzai'));
        }

        if ($param['number'] <= get_user_byid(getUid(), 'money')) {

            $timeList = array();
            $play_rule = json_decode($stock['play_rule'], true);
             $wm = json_decode($stock['wm_time_control'], true);
            if($param['type']!=3){
                 foreach ($play_rule as $key=>$val) {
                     $timeList[$val['time']] = $val;
                 }
            }else{
                 foreach ($wm as $key=>$val) {
                     $timeList[$val['time']] = $val;
                 }
            }
           
            if (!isset($timeList[$param['seconds']])) {
                return ApiError(lang('jiaoyicanshusuowu'));
            }

            $mini = $param['seconds'];

            $now = time();

            $order['buy_money'] = $param['number'];
            $order['user_id'] = getUid();
            $order['p_id'] = $stock['id'];
            $order['p_code'] = $stock['code'];
            $order['order_sn'] = $stock['code'] . generateRandomString(15 - strlen($stock['code']));
            $order['seconds'] = $mini;
            $order['p_title'] = $stock['title'];
            $order['o_style'] = $param['type'];
            $order['buy_time'] = date('Y-m-d H:i:s', $now);
            $order['sell_time'] = date('Y-m-d H:i:s', $now + $mini);
            $order['buy_price'] = $stock['price'];
            $order['win_profit'] = $timeList[$param['seconds']]['win'];
            $order['lose_profit'] = $timeList[$param['seconds']]['lose'];

            $order['balance_buy_before'] = get_user_byid(getUid(), 'money');
            $order['balance_buy_after'] = $order['balance_buy_before'] - $order['buy_money'];
            
            // 在有效时间内则 赢
//            $order['kong_type'] = $this->_get_kong_type($stock['time_control']);
           

            $res = DB::name('order')->insertGetId($order);
            if ($res) {
                $mark['user_id'] = $order['user_id'];
                $mark['room_id'] = 1;
                $mark['type'] = '下分';
                $mark['money'] = $order['buy_money'];
                $mark['balance_before'] = $order['balance_buy_before'];
                $mark['balance_later'] = $order['balance_buy_after'];
                $mark['real'] = $param['real'];
                $mark['content'] = '购买'.$param['currency_id'];
                $mark['order_sn'] = $order['order_sn'];

                addMark($mark);//写入流水
                setBalnce(array('user_id'=>getUid(), 'money'=>$order['buy_money']), 0);//下分

                // 写消息 消息类型 1 余额宝收益 2下单 3结算 4提现，根据这个和是否已读做图标显示
                Db::name('message')->insert([
                    'money' => $order['buy_money'],
                    'type' => 2,
                    'is_read' => 2,
                    'user_id' => getUid(),
                    'seconds' => $mini,
                    'time' => date('Y-m-d H:i:s', time()),
                    'order_sn' => $order['order_sn']
                ]);

                $result['balance'] = $mark['balance_later'];
                $result['buy_price'] = $order['buy_price'];
                return ApiSuccess('',$result);
            }

        } else {
            return ApiError(lang('yuebuzu'));
        }
    }
    
    public function _get_kong_type($times){
        $kong_type = 0;
        $now = time();
        if(empty($times)){
            return $kong_type;
        }else{
            $times = json_decode($times,true);
        }
        foreach ($times as $stock){
            $start_time = $stock['start_time'];
            $end_time = $stock['end_time'];
            if (!empty($start_time) && !empty($end_time)){
                $start_time = strtotime(date("Y-m-d {$start_time}:00"));
                $end_time = strtotime(date("Y-m-d {$end_time}:00"));
                if(!($nowTime >= $start_time && $nowTime <= $end_time)){
                    return 1;
                }
            }
        }
        return $kong_type;
    }
    
    
    
    
    
    
    
    
    
}