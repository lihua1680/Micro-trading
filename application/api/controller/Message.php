<?php

namespace app\api\controller;

use think\Db;

class Message extends ApiBase
{

    function generateCode($numbers) {

        // 生成 md5 哈希值
        $hash = md5($numbers);

        // 取哈希值的前 10 位字符
        $code = substr($hash, -15);

        // 将字符转换为大写
        $code = strtoupper($code);

        return $code;
    }

    public function data_log()
    {

        $message_lists = Db::name('message')->order('id desc')->where('user_id',getUid())->select();
        $data = [];
        foreach ($message_lists as $key => $message_list){
            $data[$key] = $message_list;
            // 1 余额宝收益 2下单 3结算 4提现
            if ($message_list['type'] == 1){
                if ($message_list["order_sn"]) {
                    $message_list['id'] = $message_list["order_sn"];
                } else {
                    $message_list['id'] = $this->generateCode($message_list['time']);
                }
                $cont = lang('message_yuebao_shouyi {:money}',$message_list);
            }elseif ($message_list['type'] == 2){
                if ($message_list["order_sn"]) {
                    $message_list['id'] = $message_list["order_sn"];
                } else {
                    $message_list['id'] = $this->generateCode($message_list['time']);
                }
                $cont = lang('message_xiadan {:money}{:id}',$message_list);
            }elseif ($message_list['type'] == 3){
                if ($message_list["order_sn"]) {
                    $message_list['id'] = $message_list["order_sn"];
                } else {
                    $message_list['id'] = $this->generateCode($message_list['time']);
                }
                $cont = lang('message_jiesuan {:money}{:id}',$message_list);
            }elseif ($message_list['type'] == 4){
                if ($message_list["order_sn"]) {
                    $message_list['id'] = $message_list["order_sn"];
                } else {
                    $message_list['id'] = $this->generateCode($message_list['time']);
                }
                $cont = lang('message_tixian  {:money}',$message_list);
            }elseif($message_list['type'] == 5){
                if ($message_list["order_sn"]) {
                    $message_list['id'] = $message_list["order_sn"];
                } else {
                    $message_list['id'] = $this->generateCode($message_list['time']);
                }
                $cont = lang('message_cz  {:money}',$message_list);
            } else {
                $cont = '';
            }
            $data[$key]['cont'] = $cont;
        }
        // 查看后将所有未读设置已读。
        Db::name('message')->where('user_id',getUid())->update(['is_read'=>1]);
        return ApiSuccess('',$data);

    }

    public function get_count()
    {
        return ApiSuccess('',[
            'count' => Db::name('message')->where('user_id',getUid())->where('is_read',2)->count(),
        ]);

    }

}