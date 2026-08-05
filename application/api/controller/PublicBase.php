<?php

namespace app\api\controller;

use think\Controller;
use think\Lang;
use think\Request;

class PublicBase extends Controller
{
    public function __construct(Request $request = null)
    {
        parent::__construct($request);

        $lang = request()->header('lang');
        if (!empty($lang)){
            // 直接设置翻译文件
            Lang::load(APP_PATH . $this->request->module() . '/lang/' .$lang . '.php');
        }

    }

}