<?php

namespace app\api\controller;

use think\Cache;
use think\Controller;
use think\Db;
use think\Session;

class Login extends PublicBase
{
    public function login()
    {
        $param = $this->request->param();

        $user = getUserByCondition(array('account'=>$param['account']));

        if ($user) {
            if (empty($param['type'])){
                $param['type'] = 1;
            }

            $type = $param['type'];
            if($type=='phone'){

                $vcode = Cache::get('login_code_phone_'.$param['account']);
                if(empty($vcode)){
                    return ApiError(lang('qingxianhuoquyanzhenma'));
                }

                if($vcode != $param['code']){
                    return ApiError(lang("yanzhengmabuzhengque"));
                }

            }else{

                if (empty($param['passwd'])) {
                    return ApiError(lang('qingshurumima'));
                }
                if (fh_encoding($param['passwd']) != $user['passwd']) {
                    return ApiError(lang('zhanghaomimacuowu'));
                }
            }


            if ($user['status']) {
                $save['last_login_time'] = date('Y-m-d H:i:s');
                $save['last_login_ip'] = $this->request->ip();
                $save['login_times'] = $user['login_times'] + 1;
                Db::name('user')->where(array('id'=>$user['id']))->update($save);
                if (session_status() !== PHP_SESSION_ACTIVE) {
                    session_start();
                }
                session_regenerate_id(true);
                Session::set('user', $user);
                return ApiSuccess(lang('dengluchenggong'),[
                    'token' => base64_encode(session_id()),
                ]);
            } else {
                return ApiError(lang('gaiyonghubeijinyong'));
            }


        } else {
            return ApiError(lang('yonghumingbucunzai'));
        }

    }

    function checkRegister()
    {
        $param = $this->request->param();
        $user = getUserByCondition(array('account' => $param['account']));
        if (!empty($user)){
            return ApiError(lang('gaiyonghumyicunzai'));
        }
        if ($param['invitecode']) {
            $param['invitecode'] = str_replace('YH','',$param['invitecode']);
            $param['invitecode'] = str_replace('VF','',$param['invitecode']);
            $agent = getUserByCondition(array('id' => $param['invitecode']));
            if (!$agent) {
                return ApiError(lang('kaihumabucunzaiqinghedui'));
            }
        } else {
            return ApiError(lang('qingtianxiekaihma'));
        }
        if (empty($param['phone']) || !preg_match("/^1[3-9]\d{9}$/", $param['phone'])){
            return ApiError(lang('qingtianxieshoujihao'));
        }

        if (empty($param['real_name'])){
            return ApiError(lang('qingtianxiexingm'));
        }
        return ApiSuccess();
    }


    public function register()
    {
        $param = $this->request->param();
        $result = array('status' => 0);

        // if ($param['invitecode']) {
        //     if ($param['invitecode'] == 'F6979'){
        //         $param['invitecode'] = 46;
        //     }
        //     $param['invitecode'] = str_replace('YH','',$param['invitecode']);
        //     $param['invitecode'] = str_replace('VF','',$param['invitecode']);
        //     $agent = getUserByCondition(array('id' => $param['invitecode']));
        //     if (!$agent) {
        //         return ApiError(lang("kaihumabucunzaiqinghedui"));
        //     }
        // } else {
        //     return ApiError(lang("qingtianxiekaihma！"));
        // }
        // if (empty($param['phone']) || !preg_match("/^1[3-9]\d{9}$/", $param['phone'])){
        //     return ApiError(lang('qingtianxieshoujihao'));
        // }

        if (empty($param['passwd']) || empty($param['o_passwd'])){
            return ApiError(lang('qingshurumima'));
        }

        if ($param['passwd'] != $param['o_passwd']){
            return ApiError(lang('querenmimacuocuowu'));
        }

        if (empty($param['mpasswd'])){
            return ApiError(lang('qingtianxiejiaoyimima'));
        }

        // if ($param['mpasswd'] != $param['o_mpasswd']){
        //     return ApiError(lang('jiaoyimimacuowu'));
        // }

        if (empty($param['realname'])){
            return ApiError(lang('qingtianxiexingming'));
        }

        $user = getUserByCondition(array('account' => $param['account']));
        if (!empty($user)){
            return ApiError(lang('gaiyonghumyicunzai'));
        }
        $user['account'] = $param['account'];
        $user['phone'] = $param['phone'];
        $user['username'] = $param['account'];
        $user['real_name'] = $param['realname'];
        $user['passwd'] = fh_encoding($param['passwd']);
        $user['mpasswd'] = fh_encoding($param['mpasswd']);
        $rand = rand(1, 3);
        $user['user_avatar'] = '/assets/img/' . $rand . '.png';
        $user['reg_time'] = date('Y-m-d H:i:s');
        $user['reg_ip'] = $this->request->ip();
        $user['reg_place'] = '';
        $user['last_login_time'] = date('Y-m-d H:i:s');
        $user['last_login_ip'] = $this->request->ip();

        if ($param['agent'])
            $user['agent'] = $param['agent'];
        $res = Db::name('user')->insert($user);
        return ApiSuccess(lang('zhucechegongqingdenglu'));
    }
}