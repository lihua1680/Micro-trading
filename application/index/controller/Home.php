<?php

namespace app\index\controller;
use think\Controller;
use think\Request;
use think\Db;
use think\Cache;
use think\Config;
use think\Session;
use app\common\model\User;


class Home extends Controller
{
    public function _initialize() {
        header('Location: /index.html');
        //jumpDomain();
        if (Session::get('user') && $this->request->action() != 'kf')
           $this->redirect('index/index'); 
    }
    public function login_p(){
        $this->view->assign('kefu_url', Config::get('site.kefu_url'));
        $this->view->config('view_path', '../application/index/view/'.Config::get('site.template_theme').DS);
        return $this->view->fetch();
    }
    
 
    public function sendPhoneCodeForLogin() {
        $param = $this->request->param();
        $username= $param['account'];
        if(empty($username)){
            return json(['code'=>400,'msg'=>'请输入手机号']);
        }
        if (!preg_match('/^1\d{10}$/', $username)) {
            return json(['code'=>400,'msg'=>'请输入正确的手机号']);
        }
        $user = getUserByCondition(array('account'=>$username));
        if(empty($user)){
            return json(['code'=>400,'msg'=>'该用户不存在']);
        }
        if (empty($user['phone'])) {
            return json(['code'=>400,'msg'=>'该用户不是通过手机号注册，请密码登录']);
        }
        $send_time = Cache::get('login_code_phone_send_'.$username);
        if(!empty($send_time)){
            return json(['code'=>400,'msg'=>'验证码已发送，请'.(60-(time()-$send_time)).'秒后再试']);
        }
      
        $code = mt_rand(100000,999999);
        $codeStr = str_replace('{code}',$code,config('dxb_sms.code_template'));
        dxb_sms_send($username,$codeStr);
        Cache::set('login_code_phone_'.$username,$code,60*3);
        Cache::set('login_code_phone_send_'.$username,time(),60*1);
        return json(['code'=>200,'msg'=>'验证码发送成功']);
        
    }
    public function sendPhoneCode() {
        $param = $this->request->param();
        $username= $param['account'];
        if(empty($username)){
            return json(['code'=>400,'msg'=>'请输入手机号']);
        }
        if (!preg_match('/^1\d{10}$/', $username)) {
            return json(['code'=>400,'msg'=>'请输入正确的手机号']);
        }
        $user = getUserByCondition(array('account'=>$username));
        if(!empty($user)){
            return json(['code'=>400,'msg'=>'用户已存在']);
        }

        $send_time = Cache::get('code_phone_send_'.$username);
        if(!empty($send_time)){
            return json(['code'=>400,'msg'=>'验证码已发送，请'.(60-(time()-$send_time)).'秒后再试']);
        }
      
        $code = mt_rand(100000,999999);
        $codeStr = str_replace('{code}',$code,config('dxb_sms.code_template'));
        dxb_sms_send($username,$codeStr);
        Cache::set('code_phone_'.$username,$code,60*3);
        Cache::set('code_phone_send_'.$username,time(),60*1);
        return json(['code'=>200,'msg'=>'验证码发送成功']);
        
    }
    public function login() {
        $this->redirect('/index/home/login_p');
        $this->view->assign('kefu_url', Config::get('site.kefu_url'));
        $this->view->assign('web_icon', Config::get('site.web_icon'));

        $this->view->config('view_path', '../application/index/view/'.Config::get('site.template_theme').DS);

        return $this->view->fetch(); 
    }
    
    public function reg() {
        $this->view->assign('web_icon', Config::get('site.web_icon'));
        $this->view->config('view_path', '../application/index/view/'.Config::get('site.template_theme').DS);
        return $this->view->fetch();
    }
    
    public function forget() {
        $this->view->assign('web_icon', Config::get('site.web_icon'));
        $this->view->config('view_path', '../application/index/view/'.Config::get('site.template_theme').DS);
        return $this->view->fetch();
    }
    
    public function kf() {
        session_start();
        ini_set('default_charset', "utf-8");
        $json = array(
            'UserName' => Session::get('user.id')
        );
        return json($json);
    }
    
    public function ajax() {
        
        if ($this->request->isAjax()) {
            $param = $this->request->param();
            $result = array('status'=> 0);
            $request = $param['request'];
            switch ($request) {
                case 'register':
                    
                  //  if ($param['invitecode']) {
                  //      $agent = getUserByCondition(array('id'=>$param['invitecode']));
                  //      if (!$agent) {
                 //           $result['msg'] = '邀请码不存在,请核对!';
                //            return json($result);
                 //       }  
                //    } else {
                //        $result['msg'] = 'inviteCode';
                //        return json($result);
                //    }
                    
                    $user = getUserByCondition(array('account'=>$param['account']));
                    $captcha = new \think\captcha\Captcha();
                    //$captcha->check($param['vcode'])
                    $vcode = Cache::get('code_phone_'.$param['account']);
                    if(empty($vcode)){
                        $result['msg'] = '请先获取验证码';
                        break;
                    }
                    if($vcode == $param['code']){
                        if (!$user) {
                            $user['account'] = $param['account'];
                            $user['phone'] = $param['account'];
                            $user['username'] = $param['account'];
                            $user['real_name'] = $param['real_name'];
                          //  $user['agent'] = $param['invitecode'] ? $param['invitecode'] : '';
                            $user['passwd'] = fh_encoding($param['passwd']);
                            $rand = rand(1, 3);
                            $user['user_avatar'] = '/assets/img/'.$rand.'.png'; 
                            $user['reg_time'] = date('Y-m-d H:i:s');
                            $user['reg_ip'] = $this->request->ip();
                            $user['reg_place'] = '';
                            $user['last_login_time'] = date('Y-m-d H:i:s');
                            $user['last_login_ip'] = $this->request->ip();

                            if ($param['agent'])
                               $user['agent'] = $param['agent'];
                            $res = Db::name('user')->insert($user);
                            if ($res) {   
                                $result['status'] = 1;
                                $result['msg'] = '注冊成功，是否立即登录？';
                                $result['url'] = url('home/login');
                            }
                        } else {
                            $result['msg'] = '该用户名已存在';
                        }
                    } else {
                        $result['msg'] = '验证码不正确';
                    }
                    break;
                case 'login':
                    $user = getUserByCondition(array('account'=>$param['account']));
                    
                    if ($user) {
                        
                        $type = $param['type'];
                        if($type=='phone'){
                            
                            $vcode = Cache::get('login_code_phone_'.$param['account']);
                            if(empty($vcode)){
                                $result['msg'] = "请先获取验证码";
                                break;
                            }
                            
                            if($vcode != $param['code']){
                                $result['msg'] = "验证码不正确";
                                break;
                            }
                            
                        }else{
                         
                            if (empty($param['passwd'])) {
                                $result['msg'] = '请输入密码';
                                break;
                            }
                            if (fh_encoding($param['passwd']) != $user['passwd']) {
                                $result['msg'] = '密码不正确';
                                break;
                            }
                            
                        }
                        
                        
                        if ($user['status']) {
                            $save['last_login_time'] = date('Y-m-d H:i:s');
                            $save['last_login_ip'] = $this->request->ip();
                            $save['login_times'] = $user['login_times'] + 1; 
                            Db::name('user')->where(array('id'=>$user['id']))->update($save);
                            Session::set('user', $user);
                            $result['status'] = 1; 
                            $result['msg'] = '登录成功';
                            $result['url'] = url('index/index');
                        } else {
                            $result['msg'] = '该用户被禁用，请联系管理员!';
                        }
                    
                        
                    } else {
                        $result['msg'] = '用户名不存在';
                    }
                    break;
            }
            return json($result);
        }
    }
    
}
