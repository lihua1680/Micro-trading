<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use think\Db;
use think\Config;
use think\Session;
use think\Request;

class Common extends Frontend
{
    public function _initialize()
    {
        //jumpDomain();
        $user = Session::get('user');
        if (!$user) {
            $agent = $this->request->param('agent');
            if ($agent)
                Session::set('agent', $agent);
            //            $way = $this->request->param('way');
            //            $access = Config::get("site.web_access");
            //            if (in_array('weixin', $access) && is_weixin()) {
            //                $this->redirect('wx/index');
            //            }
                
                $this->redirect('home/login');
        }
        
        if (!$user)
            $this->redirect('home/login');
        $this->getOnline();
        //$site = Config::get("site");
        $user = getUserByCondition(array('id'=>$user['id']));
        if ($user['freeze']) {
            //$this->redirect($site['freeze_script']);
        }
        
        if (!$user['status']) {
            $this->redirect($site['limit_script']);
        }
        $cuser = get_user_byid(getUid());
        $this->view->assign('cuser', $cuser);
        $this->view->assign('kefu', Config::get('site.kefu_url')."?name={$cuser['real_name']}_{$cuser['id']}&id={$cuser['id']}");
        
        $this->view->assign('site', Config::get("site"));
        
        $this->view->assign('version', time());
    }
    
    private function getOnline() {
        Db::name('user')->where(array('id'=>getUid()))->update(array('rtime'=>time()));
    }
    
}


