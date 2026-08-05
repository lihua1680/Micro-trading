<?php

namespace app\index\controller;

use app\index\controller\Common;
use think\Request;
use think\Db;
use think\Config;
use think\Session;

class Kefu extends Common
{
    
    public function _initialize()
    {
        parent::_initialize();
    }
    
    public function index()
    {
        $user = getUserById(getUid());
        $domain = 'http://www.#.cn';
        $url = $domain.'/index/index/kefu?u=5c6cbcb7d55ca&uid='.$user['id'].'&name='.$user['username'].'&avatar=http://'.$_SERVER['HTTP_HOST'].$user['user_avatar'];
        header('Location:'.$url);
    }
    
    
}
