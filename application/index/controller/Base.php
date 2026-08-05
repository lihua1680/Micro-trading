<?php

namespace app\index\controller;
use think\Controller;
use think\Request;
use think\Db;
use think\Config;
use think\Session;

class Base extends Controller
{
    public  function __construct(Request $request)
    {
        if($request->isMobile())
        {
            config('template.view_path','template/default/mobile/'.$request->module()."/");
        }
        else{
            config('template.view_path','template/default/web/'.$request->module()."/");
        }
        parent::__construct($request);
    }
    
}
