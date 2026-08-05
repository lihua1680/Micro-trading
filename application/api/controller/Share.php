<?php

namespace app\api\controller;

use think\Controller;
use think\Db;

class Share extends Controller
{

    private function isToday($timeString) {
        // 将时间字符串转换为时间戳
        $timestamp = strtotime($timeString);

        // 检查时间戳是否有效
        if ($timestamp === false) {
            return false; // 时间字符串无效
        }

        // 获取今天的开始和结束时间戳
        $todayStart = strtotime('today midnight');
        $todayEnd = strtotime('tomorrow midnight') - 1; // 减去一秒，以确保今天的最后一秒也被包含

        // 检查时间戳是否在今天的范围内
        return ($timestamp >= $todayStart) && ($timestamp <= $todayEnd);
    }

    public function shouyi()
    {
        // 收益百分比
        // 获取所有用户
        $users = Db::name('user')->where('yue_start_money', '<>', '0.00')->select();
        foreach ($users as $user){
            $shouyi_q = bcmul($user['yue_start_money'],0.01,2);

            if (empty($user['yue_last_time']) || !$this->isToday($user['yue_last_time'])){
                // 更新用户收益的总和
                Db::name('user')->where('id',$user['id'])->setInc('yue_shouyi',$shouyi_q);
                Db::name('user')->where('id',$user['id'])->setInc('yue_start_money',$shouyi_q);
                // 记录时间
                Db::name('user')->where('id',$user['id'])->update([
                    'yue_last_time' => date('Y-m-d H:i:s'),
                ]);
                // 写消息 消息类型 1 余额宝收益 2下单 3结算 4提现，根据这个和是否已读做图标显示
                Db::name('message')->insert([
                    'money' => $shouyi_q,
                    'type' => 1,
                    'is_read' => 2,
                    'user_id' => $user['id'],
                    'time' => date('Y-m-d H:i:s', time()),
                    'order_sn' => "YEB" . generateRandomString()
                ]);
                echo "用户id:{$user['id']},发送收益，金额：{$shouyi_q}" . PHP_EOL;
            }else{
                echo "今天已经发过收益，不重复发送！" . PHP_EOL;
            }

        }
        echo "执行完毕！" . PHP_EOL;
    }


    // 确认份额
    // 确定今天是否是周末， 是否是节假日
    public function confirm_share()
    {
        $isnotwork = $this->check_day();
        if (!$isnotwork) {
            // 查询全部金额做确定份额
            $yu = Db::name('yu')->where('type',2)->where('status', 2)->select();
            foreach ($yu as $key => $value) {
                // 增加用户的已确认份额
                Db::name('user')->where('id',$value['user_id'])->setInc('yue_start_money',$value['amount']);
                // 修改当前的状态为已确认份额
                Db::name('yu')->where('status', 2)->where('share_id', $value['share_id'])->update([
                    'status' => 1
                ]);
                echo "用户id:{$value['user_id']},确认份额成功，金额：{$value['amount']}" . PHP_EOL;
            }
        }else{
            echo "今日不上班，不确认份额！" . PHP_EOL;
        }
        echo "执行完毕！" . PHP_EOL;

    }

    private function check_day()
    {
        try {
            $data = array('key' => '81f7ffb5f8953924498a6e1cf0490ef8', 'date' => date('Y-m-d'), 'type' => '0');
            $url = 'https://apis.tianapi.com/jiejiari/index';
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            $output = curl_exec($curl);
            curl_close($curl);

            $json = json_decode($output, true);//将json解析成数组


            if ($json['code'] == 200) { //判断状态码

                return $json['result']['list'][0]['isnotwork'];
            } else {
                die('错误提示：' . $json['msg']);
            }
        } catch (\Exception $exception) {
            die('请求发生错误：' . $exception->getMessage());
        }


    }

    public function set_hui_lv()
    {
        cache('day_hui_lv',7.24);
        try {
            $url = 'https://api.coingecko.com/api/v3/simple/price?ids=tether&vs_currencies=cny';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            $response = curl_exec($ch);

            if (curl_errno($ch) || curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200) {
                curl_close($ch);
                cache('day_hui_lv',7.24);
                echo "获取汇率失败，默认设置汇率为:7.24" . PHP_EOL;
                die();
            }

            curl_close($ch);
            $data = json_decode($response, true);

            $huilv = isset($data['tether']['cny']) ? (float)$data['tether']['cny'] : null;
            if ($huilv){
                cache('day_hui_lv',$huilv);
                echo "设置汇率成功 ,今日汇率：{$huilv}" . PHP_EOL;
            }
        }catch (\Exception $e){
            cache('day_hui_lv',7.24);
            echo "获取汇率失败，默认设置汇率为:7.24" . PHP_EOL;
        }
        echo "执行结束 ！" . PHP_EOL;


    }

}