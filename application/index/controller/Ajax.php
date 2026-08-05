<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use think\Lang;

/**
 * Ajax异步请求接口
 * @internal
 */
class Ajax extends Frontend
{

    protected $noNeedLogin = ['lang'];
    protected $noNeedRight = ['*'];
    protected $layout = '';

    /**
     * 加载语言包
     */
    public function lang()
    {
        header('Content-Type: application/javascript');
        $callback = $this->request->get('callback');
        $controllername = input("controllername");
        $this->loadlang($controllername);
        //强制输出JSON Object
        $result = jsonp(Lang::get(), 200, [], ['json_encode_param' => JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE]);
        return $result;
    }
    
    public function clang()
    {
        $lang = input('?get.lang') ?  input('get.lang') : 'cn';
        switch ($lang) {
            //中文
            case 'cn':
                cookie('think_var', 'zh-cn');
                break;
                //英文
            case 'en':
                cookie('think_var', 'en-us');
                break;
            case 'hk':
                cookie('think_var', 'hk');
                break;
            default:
                cookie('think_var', 'zh-cn');
                break;
        }
    }
    
    /**
     * 上传文件
     */
    public function upload()
    {
        return action('api/common/upload');
    }
    
    public function data() {}

}
