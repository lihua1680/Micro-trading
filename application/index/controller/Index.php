<?php

namespace app\index\controller;

use app\index\controller\Common;
use think\Request;
use think\Db;
use think\Config;
use think\Session;
use think\Cache;

class Index extends Common
{
    
    public function _initialize()
    {
        parent::_initialize();
        $current = $this->request->action();
        $this->view->assign('current', $current);
    }
    
   
    
    public function index()
    {
        $cuser = get_user_byid(getUid());
        $this->view->assign('cuser', $cuser);
        $banner = Db::name('category')->where(array('type'=>'banner', 'status'=>'normal'))->order('weigh desc')->select();
        $notice = Db::name('notice')->where(array('status'=>1, 'type'=>1))->order('rank desc,ctime desc')->select();
        $currency = Db::name('product')->where(array('status'=>1, 'is_open'=>1))->order('rank desc,ctime desc')->select();
        $this->view->assign('banner', $banner)->assign('notice', $notice)->assign('currency', $currency);
        $this->view->config('view_path', '../application/index/view/'.Config::get('site.template_theme').DS);
        return $this->view->fetch();
    }
    
    public function trades()
    {
        $symbol = $this->request->param('symbol');
        
        $currency = Db::name('product')->where(array('status'=>1, 'is_open'=>1))->order('rank desc,ctime desc')->select();
        foreach ($currency as $key=>&$val) {
            if (!$symbol) {
                if($val['cid'] == 1){
                    $url = url('index/trades').'?symbol='.$val['code'].'/USDT';
                }else{
                    $url = url('index/trades').'?symbol='.$val['code'];
                }
                
                $this->redirect($url);
            }
            if($val['cid'] == 1){
                $val['codename'] = $val['code'].'/USDT';
            }else{
                $val['codename'] = $val['code'];
            }
        }
        $this->view->assign('currency', $currency);
        $this->view->config('view_path', '../application/index/view/'.Config::get('site.template_theme').DS);
        return $this->view->fetch();
    }
    
    public function market()
    {
        $currency = Db::name('product')->where(array('status'=>1, 'is_open'=>1))->order('rank desc,ctime desc')->select();
        foreach ($currency as $key=>&$val) {
            if($val['cid'] == 1){
                $val['codename'] = $val['code'].'/USDT';
            }else{
                $val['codename'] = $val['code'];
            }
        }
        $this->view->assign('currency', $currency);
        $this->view->config('view_path', '../application/index/view/'.Config::get('site.template_theme').DS);
        return $this->view->fetch();
    }
    
    public function user()
    {
        $currency = Db::name('product')->where(array('status'=>1, 'is_open'=>1))->order('rank desc,ctime desc')->select();
        $this->view->assign('currency', $currency);
        $this->view->config('view_path', '../application/index/view/'.Config::get('site.template_theme').DS);
        return $this->view->fetch();
    }
    
    public function wallet()
    {
        $currency = Db::name('product')->where(array('status'=>1, 'is_open'=>1))->order('rank desc,ctime desc')->select();
        $this->view->assign('currency', $currency);
        $this->view->config('view_path', '../application/index/view/'.Config::get('site.template_theme').DS);
        return $this->view->fetch();
    }
    
    public function help()
    {
        $news = Db::name('notice')->where(array('status'=>1, 'type'=>2))->order('rank desc')->select();
        $this->view->assign('news', $news);
        $this->view->config('view_path', '../application/index/view/'.Config::get('site.template_theme').DS);
        return $this->view->fetch();
    }
    
    public function page_detail()
    {
        $sid = $this->request->param('sid');
        $news = Db::name('notice')->where(array('status'=>1, 'id'=>$sid))->find();
        $this->view->assign('news', $news);
        $this->view->config('view_path', '../application/index/view/'.Config::get('site.template_theme').DS);
        return $this->view->fetch();
    }
    
    public function logout() {
        Session::set('user', null);
        $this->success('退出登录成功', 'home/login');
    }
    
    
    public function notice() {
        $this->view->assign('content', Config::get('site.alert_notice'));
        if (Config::get('site.template_theme') == 'default')
            $this->view->config('view_path', '../application/index/view/'.Config::get('site.template_theme').DS);
            else $this->view->config('view_path', '../application/index/view/tmp1'.DS);
        return $this->view->fetch();
    }
    public function getArticleList(){
        $list=Db::name('article')->order('id desc')->paginate(10)->each(function($item, $key){
            //$item['date'] = date('Y-m-d H:i:s',$item['cteate_time']);
            return $item;
        });
        return json($list);
    }
    public function article(){
        $id=input('id');
        $article=Db::name('article')->find($id);
        $this->view->assign('article', $article);
        $this->view->config('view_path', '../application/index/view/'.Config::get('site.template_theme').DS);
        return $this->view->fetch();
    }
    public function company(){
        $this->view->assign('company', Config::get('site.company_desc'));
        $this->view->config('view_path', '../application/index/view/'.Config::get('site.template_theme').DS);
        return $this->view->fetch();
    }
}
