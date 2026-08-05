<?php
namespace app\api\controller;

use think\Controller;
use think\Db;
use think\Request;
use think\Session;

class ApiBase extends PublicBase
{

    public function __construct(Request $request = null)
    {
        parent::__construct($request);

        $token = request()->header('token');
        if (empty($token)){
            die(ApiReturn('',-22));
        }else{

            $token = base64_decode($token, true);
            if ($token === false || !preg_match('/^[a-zA-Z0-9,-]{16,128}$/', $token)) {
                die(ApiReturn(lang('qingdenglu'),-22));
            }
            session_id($token);
            $user = Session::get('user');
            if (empty($user)){
                die(ApiReturn(lang('qingdenglu'),-22));
            }


            if ($user['status'] != 1) {
                die(ApiReturn(lang('gaiyonghubeijinyong'), -22));
            }
        }

    }

}