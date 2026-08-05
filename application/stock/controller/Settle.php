<?php
namespace app\stock\controller;
use app\common\RedisLock;
use think\Console;
use think\Controller;
use think\Request;
use think\Db;
use think\Config;
use think\Session;

class Settle extends Controller
{
    private $redisLock;
    public function __construct(){
		parent::__construct();
        $this->redisLock = new RedisLock;
	}

    public function run() {
        
        $lock_key = 'do_settle';
	    $doOrder = $this->redisLock->lock($lock_key);
	    if(!$doOrder){
	        echo '重复do Settle'.PHP_EOL;
	        return ;
	    }
	    echo "正常执行settle".PHP_EOL;
        $time = date('Y-m-d H:i:s');
        $stocks = Db::name('product')->where(array('status'=>1))->column('price', 'code');
        $stocksTime = Db::name('product')->where(array('status'=>1))->column('time_control', 'code');
        $orders = Db::name('order')->where(array('status'=>1, 'sell_time'=> array('elt', $time)))->select();
        
        foreach ($orders as $order) {
            
            $nowPrice = $stocks[$order['p_code']];

            $uorder = array('id'=>$order['id'], 'sell_price' => $nowPrice, 'status'=>3, 'user_id'=>$order['user_id']);

            if ($nowPrice > 0) {
                
                if (($order['o_style'] == 1 && $nowPrice > $order['buy_price']) || ($order['o_style'] == 2 && $nowPrice < $order['buy_price'])) {
                    $win = true;
                } else {
                    $win = false;
                }

                $all_kong = Config::get('site.play_type');
                if($all_kong == 1) {
                    if (!$win){
                        $uorder['sell_price'] = $this->getPrice($order['buy_price'], $nowPrice, $order['o_style'], 1);
                    }
                    $win = true;
                } else if ($all_kong == 2) {
                    if ($win){
                        $uorder['sell_price'] = $this->getPrice($order['buy_price'], $nowPrice, $order['o_style'], 0);
                    }
                    $win = false;
                }
                $timeR = $this->vaTime($stocksTime[$order['p_code']], strtotime($order["buy_time"]), $order["o_style"]);
                if ($timeR == 101) {
                    if (!$win){
                        $uorder['sell_price'] = $this->getPrice($order['buy_price'], $nowPrice, $order['o_style'], 1);
                    }
                    $win = true;
                } else if ($timeR == 102) {
                    if ($win){
                        $uorder['sell_price'] = $this->getPrice($order['buy_price'], $nowPrice, $order['o_style'], 0);
                    }
                    $win = false;
                }

                $ukong = get_user_byid($order['user_id'], 'kong_style');
                if ($order['kong_type'] > 0 || $ukong > 0) {
                    $kong = $order['kong_type'] > 0 ? $order['kong_type'] : $ukong;
                    if ($kong == 1) {//赢
                        if (!$win){
                            $uorder['sell_price'] = $this->getPrice($order['buy_price'], $nowPrice, $order['o_style'], 1);
                        }
                        $win = true;
                    } elseif ($kong == 2) {//亏
                        if ($win){
                            $uorder['sell_price'] = $this->getPrice($order['buy_price'], $nowPrice, $order['o_style'], 0);
                        }
                        $win = false;
                    }
                }
                
                if ($win) {
                    if($order['win_profit'] <= 100){
                         $uorder['ploss'] = round($order['buy_money'] * (100 + $order['win_profit'])/100, 2);
                    }else{
                         $uorder['ploss'] = round($order['buy_money'] * ($order['win_profit'])/100, 2);
                    }
                    // $uorder['ploss'] = round($order['buy_money'] * (100 + $order['win_profit'])/100, 2);
                    $uorder['end_loss'] = $order['win_profit'];
                    // $uorder['end_profit'] = round($order['buy_money'] *  $order['win_profit']/100, 2);
                    $uorder['end_profit'] = $uorder['ploss'];
                    $uorder['is_win'] = 1;
                } else {
                    $uorder['ploss'] = $order['buy_money'] - round($order['buy_money'] *  $order['lose_profit']/100, 2);
                    $uorder['end_loss'] = 0 - $order['lose_profit'];
                    $uorder['end_profit'] = 0 - round($order['buy_money'] *  $order['lose_profit']/100, 2);
                    $uorder['is_win'] = 0;
                }
                
                updateOrder($uorder, $uorder['ploss'], $uorder['is_win'], $order['id']);
                
                // 写消息 消息类型 1 余额宝收益 2下单 3结算 4提现，根据这个和是否已读做图标显示
                Db::name('message')->insert([
                    'money' => $uorder['end_profit'],
                    'type' => 3,
                    'is_read' => 2,
                    'user_id' => $order['user_id'],
                    'time' => date('Y-m-d H:i:s', time()),
                    'order_sn' => $order["order_sn"]
                ]);
                
                echo $order['id'].PHP_EOL;
            }
        }
        $this->redisLock->unlock($lock_key);
	    return ;
        
    }
    
    private function getPrice($buy, $now, $style, $type) {
        $cha = abs($now - $buy);
        $FloatLength = getFloatLength((float)$now);
        $_s_rand = rand(1, 10) / pow(10, $FloatLength - 1);
        //赢
        if ($type == 1) {
            //买涨
            if ($style == 1) {
                $price = $buy + $_s_rand;
            } elseif ($style == 2) {
                $price = $buy - $_s_rand;
            }
        } else {
            //买涨
            if ($style == 1) {
                $price = $buy - $_s_rand;
            } elseif ($style == 2) {
                $price = $buy + $_s_rand;
            }
        }
        return $price;
    }
    
    private function vaTime($jsonString, $currentFormattedTime, $type) {
        if (empty($jsonString)) {
            return false;
        }
        if (strlen($jsonString) < 10) {
             return false;
        }
        // 将 JSON 字符串解析为 PHP 数组
        $timeIntervals = json_decode($jsonString, true);
        // 获取当前时间
        $currDate = date("Y-m-d", $currentFormattedTime);
        // 检查当前时间是否在任一时间段内
        $isInTimeInterval = false;
        foreach ($timeIntervals as $interval) {
            if ($currentFormattedTime >= strtotime("$currDate {$interval['start_time']}:00")
                && $currentFormattedTime <= strtotime("$currDate {$interval['end_time']}:00")) {
                if(empty($interval["type"])) {
                    $interval["type"] = 1;
                }    
                if ($interval["type"] * 1 == $type * 1) {
                    $isInTimeInterval = 101;
                } else {
                    $isInTimeInterval = 102;
                }    
                break;
            }
        }
        return $isInTimeInterval;
    }
    
}