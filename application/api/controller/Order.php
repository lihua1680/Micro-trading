<?php

namespace app\api\controller;

use think\Db;

class Order extends ApiBase
{
    public function orderlist()
    {
        $param = $this->request->param();
        $condition = array('user_id'=>getUid(), 'status'=>$param['status']);

        $lists = Db::name('order')->where($condition)->order('id desc')->paginate(100);
        $olist = array();
        foreach ($lists->items() as $key=>$order) {
            $timer= (strtotime($order['sell_time']) - time())*1000;
            $olist[] = array(
                'id' => $order['id'],
                'order_sn' => $order['order_sn'],
                'code' => $order['p_code'].'/'.$order['p_title'],
                'title'=>$order['p_title'],
                'open_price' => number_format($order['buy_price'] * 1,6), // 保存六位小数点
                'number' => (float)$order['buy_money'],
                'type' => $order['o_style'],
                'ploss' => $order['ploss'],
                'seconds' => $order['seconds'],
                'profit_ratio' => $order['win_profit'],
                'endTime' => strtotime($order['sell_time'])*1000,
                'remain_milli_seconds' =>$timer<=0?0:$timer,
                'status' => $order['status'],
                'buy_time' => $order['buy_time'],
                'sell_time' => $order['sell_time'],
                'end_price' => number_format($order['sell_price'] * 1, 6),
                'end_profit' => number_format($order['ploss']-$order['buy_money'] * 1, 2) //$order['end_profit']
            );
        }
        return ApiSuccess('',[
            'lists' => $olist,
            'page' => input("page",1),
            'currentPage' => $lists->currentPage(),
            'lastPage' => $lists->lastPage(),
        ]);
    }
    
    public function ordercount() {
        $condition = array('user_id'=>getUid(), 'status'=>1);
        $count = Db::name('order')->where($condition)->count();
         return ApiSuccess('',[
            'count' =>$count
        ]);
    }

}