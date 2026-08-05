<?php

namespace app\admin\controller;

use app\admin\model\AdminLog;
use app\common\controller\Backend;
use think\Config;
use think\Hook;
use think\Db;
use think\Validate;

/**
 * 后台首页
 * @internal
 */
class Index extends Backend
{

    protected $noNeedLogin = ['login', 'ajaxmsg', 'ajaxnum', 'ajaxdomain'];
    protected $noNeedRight = ['index', 'logout', 'ajaxmsg', 'ajaxdomain'];
    protected $layout = '';

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 后台首页
     */
    public function index()
    {
        //左侧菜单
        list($menulist, $navlist, $fixedmenu, $referermenu) = $this->auth->getSidebar([
            'dashboard' => '',
            'addon'     => ['new'],
            'auth/rule' => __('Menu'),
            'general'   => [],
            'message' => '<span id="message">0</span>',
            'upmark' => '<span id="upmark">0</span>',
            'downmark' => '<span id="downmark">0</span>',
        ], 'dashboard');
        $action = $this->request->request('action');
        if ($this->request->isPost()) {
            if ($action == 'refreshmenu') {
                $this->success('', null, ['menulist' => $menulist, 'navlist' => $navlist]);
            }
        }
        AdminLog::setTitle('后台首页');
        $this->view->assign('menulist', $menulist);
        $this->view->assign('navlist', $navlist);
        $this->view->assign('fixedmenu', $fixedmenu);
        $this->view->assign('referermenu', $referermenu);
        $this->view->assign('title', __('Home'));
        return $this->view->fetch();
    }

    
    public function ajaxmsg() {
        $param = $this->request->param();
        $result = array();
        $now = time();
        $time = Db::name('config')->where(array('name'=>'freshtime'))->value('value');
        Db::name('config')->where(array('name'=>'freshtime'))->setField('value', $now);
        $mark['room_id'] = getAroomid();
        $mark['view_status'] = 0;
        $mark['rtime'] = array('between', array($time, $now));
        
        $result['data'] = Db::name('upmark')->where($mark)->select();
        $mark['type'] = '上分';
        $result['up'] = Db::name('upmark')->where($mark)->count();
        $w_voice = Db::name('config')->where(array('name'=>'withdraw_voice'))->value('value');
        if($w_voice == 1) {
            $mark['type'] = '下分';
            $result['down'] = Db::name('upmark')->where($mark)->count();
        }
        if (isset($mark['time'])) {
            $mark['addtime'] = $mark['time'];
            unset($mark['time']);
        }
        unset($mark['type']);

        $voice = Db::name('config')->where(array('name'=>'order_voice'))->value('value');
        if ($voice == 1) {
            $order = array('buy_time'=>array('between', array(date('Y-m-d H:i:s', $time), date('Y-m-d H:i:s', $now))));
            $result['order'] = Db::name('order')->where($order)->count();
        }
        return json($result);
    }
    
    
    public function ajaxnum() {
        $up = Db::name('upmark')->where(array('type'=>'上分','status'=>0))->count();
        $down = Db::name('upmark')->where(array('type'=>'下分','status'=>0))->count();
        return json(array('up'=>$up, 'down'=>$down, 'chat'=>0));
    }
    
    public function ajaxdomain() {
        $tips = array();
        $wxDomain = Db::name('config')->where(array('name'=>'wx_domain'))->find();
        $wxqr1Domain = Db::name('config')->where(array('name'=>'wx_qrdomain1'))->find();
        $wxqr2Domain = Db::name('config')->where(array('name'=>'wx_qrdomain1'))->find();
        if ($wxDomain['prompt']) {
            $tips[] = '公众号域名'.$wxDomain['value'].'经检测被微信端屏蔽，请及时检查更换!';
        }
        if ($wxqr1Domain['prompt']) {
            $tips[] = '二维码域名'.$wxqr2Domain['value'].'经检测被微信端屏蔽，请及时检查更换!';
        }
        if ($wxqr2Domain['prompt']) {
            $tips[] = '二维码域名'.$wxqr2Domain['value'].'经检测被微信端屏蔽，请及时检查更换!';
        }

        return json($tips);
    }
    
    
    /**
     * 管理员登录
     */
    public function login()
    {       
        $url = $this->request->get('url', 'index/index');
        if ($this->auth->isLogin()) {
            $this->success(__("You've logged in, do not login again"), $url);
        }
        if ($this->request->isPost()) {
            $username = $this->request->post('username');
            $password = $this->request->post('password');
            $keeplogin = $this->request->post('keeplogin');
            $token = $this->request->post('__token__');
            $rule = [
                'username'  => 'require|length:3,30',
                'password'  => 'require|length:3,30',
                '__token__' => 'token',
            ];
            $data = [
                'username'  => $username,
                'password'  => $password,
                '__token__' => $token,
            ];
            if (Config::get('fastadmin.login_captcha')) {
                $rule['captcha'] = 'require|captcha';
                $data['captcha'] = $this->request->post('captcha');
            }
            $validate = new Validate($rule, [], ['username' => __('Username'), 'password' => __('Password'), 'captcha' => __('Captcha')]);
            $result = $validate->check($data);
            if (!$result) {
                $this->error($validate->getError(), $url, ['token' => $this->request->token()]);
            }
            AdminLog::setTitle(__('Login'));  
            $result = $this->auth->login($username, $password, $keeplogin ? 86400 : 0);
            if ($result === true) {
                Hook::listen("admin_login_after", $this->request);
                $this->success(__('Login successful'), $url, ['url' => $url, 'id' => $this->auth->id, 'username' => $username, 'avatar' => $this->auth->avatar]);
            } else {
                $msg = $this->auth->getError();
                $msg = $msg ? $msg : __('Username or password is incorrect');
                $this->error($msg, $url, ['token' => $this->request->token()]);
            }
        }

        // 根据客户端的cookie,判断是否可以自动登录
        if ($this->auth->autologin()) {
            $this->redirect($url);
        }
        $background = Config::get('fastadmin.login_background');
        $background = stripos($background, 'http') === 0 ? $background : config('site.cdnurl') . $background;
        $this->view->assign('background', $background);
        $this->view->assign('title', __('Login'));
        Hook::listen("admin_login_init", $this->request);
        return $this->view->fetch();
    }

    /**
     * 注销登录
     */
    public function logout()
    {
        $this->auth->logout();
        Hook::listen("admin_logout_after", $this->request);
        $this->success(__('Logout successful'), 'index/login');
    }

}
