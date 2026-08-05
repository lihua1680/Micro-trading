<?php

namespace app\index\controller;
use think\Controller;
use think\Request;
use think\Db;
use think\Config;
use think\Session;
use weixin\Wechat;
use weixin\WechatAuth;
class Wx extends Controller
{
    private $_appId = '';
    
    private $_crypt = '';
    
    private $_auth = '';
    
    public function _initialize() {
        $this->_appId = Config::get('site.appid');
        $this->_auth = new WechatAuth($this->_appId, Config::get('site.appsecret'));
    }
    
    public function index()
    {
        
        if (in_array('weixin', Config::get('site.web_access'))) {
            $this->oauth();
        } else {
            echo '微信登录已关闭';
        }
        
    }
    
    /***
     * 网页授权登录
     */
    public function oauth() {
        $state = '';
        if (Session::get('agent'))
            $state = Session::get('agent');
        $domain = Config::get('site.wx_domain');
        $domain = str_replace(array('http://', 'https://'), '', $domain);
        $redirect_uri = urlencode('http://'.$domain.url('Wx/info',array('state'=>$state)));
        $url ="https://open.weixin.qq.com/connect/oauth2/authorize?appid={$this->_appId}&redirect_uri=$redirect_uri&response_type=code&scope=snsapi_userinfo&state={$state}#wechat_redirect";
        header("Location:".$url);
    }
    
    public function info() {
        header("Content-Type: text/html;charset=utf-8");
        $token = $this->_auth->getAccessToken('code', $this->request->param('code'));
        $wxInfo = $this->_auth->getUserInfo($token['openid']);
        $state = $this->request->param('state');
        $domain = Db::name('domain')->where(array('using'=>1))->value('domain');
        if (!$domain)
            $domain = $_SERVER['HTTP_HOST'];
        else $domain = str_replace(array('http://', 'https://'), '', $domain);
        $url = 'http://'.$domain.url('Wx/access');
        $html = '<form name="myform" action="'.$url.'" method="post">
               <input name="nickname" type="hidden" value="'.$wxInfo['nickname'].'" />
               <input name="headimgurl" type="hidden" value="'.$wxInfo['headimgurl'].'" />
               <input name="openid" type="hidden" value="'.$wxInfo['openid'].'" />
               <input name="room" type="hidden" value="1" />
               <input name="state" type="hidden" value="'.$state.'" />
              </form>
              <script type="text/javascript">document.myform.submit();</script>';
        echo $html;
    }
    
    
    public function access() {
        $param = $this->request->param();
        $openid = $param['openid'];
        if ($openid) {
            $user = getUserByCondition(array('openid'=>$openid));
            if ($user) {
                if (!$user['status']) {
                    echo '对不起您的账号被禁用，请联系管理员处理!';
                } else {
                    $save['last_login_time'] = date('Y-m-d H:i:s');
                    $save['login_times'] = $user['login_times'] + 1;
                    $save['username'] = $param['nickname'];
                    $save['user_avatar'] = $param['headimgurl'];
                    Db::name('user')->where(array('id'=>$user['id']))->update($save);
                    $user = getUserByCondition(array('openid'=>$openid));
                    Session::set('user', $user);
                    $bind = Config::get('site.need_bind_account');
                    if ($bind && !$user['passwd']) {
                        $this->redirect('User/bind');
                    } else {
                        $this->redirect('index/index');
                    }
                    
                }
            } else {
                $user['account'] = '';
                $user['username'] = $param['nickname'];
                $user['openid'] = $param['openid'];
                $user['agent'] = $param['state']; 
                $user['user_avatar'] = $param['headimgurl'];
                $user['reg_time'] = date('Y-m-d H:i:s');
                $user['reg_ip'] = $this->request->ip();
                $user['reg_place'] = '';
                $user['last_login_time'] = date('Y-m-d H:i:s');
                $user['last_login_ip'] = $this->request->ip();
                $res = Db::name('user')->insert($user);
                Session::set('user', getUserByCondition(array('openid'=>$param['openid'])));
                $bind = Config::get('site.need_bind_account');
                if ($bind) {
                    $this->redirect('User/bind');
                } else {
                    $this->redirect('index/index');
                }
            }
        }
        //fh($param);
        //$user = getUserByCondition(array('openid'=>$wxInfo['openid']));
    }
    
  
}
