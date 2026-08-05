<?php

namespace app\api\controller;

use fast\Random;
use think\Config;
use think\Db;

class User extends ApiBase
{

    public function up_password()
    {
        $password = input('password');
        $check_password = input('check_password');

        $o_password = input('o_password');

        $user_password =  Db::name('user')->where('id',getUid())->value('passwd');

        if (empty($password) || empty($check_password) || empty($o_password)){
            return ApiError(lang('mimacuocuowu'));
        }

        if ($password != $check_password){
            return ApiError(lang('querenmimacuocuowu'));
        }

        if (fh_encoding($o_password) !== $user_password){
            return ApiError(lang('mimacuocuowu'));
        }




        if (!empty($password)){
            Db::name('user')->where('id',getUid())->update([
                'passwd' => fh_encoding($password),
            ]);
        }

        return ApiSuccess(lang('shezhengchengong'));
    }

    public function up_mpassword()
    {
        $password = input('mpassword');

        $check_password = input('check_password');

        $o_password = input('o_password');

        $user_password =  Db::name('user')->where('id',getUid())->value('mpasswd');

        if (empty($password) || empty($check_password) || empty($o_password)){
            return ApiError(lang('mimacuocuowu'));
        }

        if ($password != $check_password){
            return ApiError(lang('querenmimacuocuowu'));
        }

        if (fh_encoding($o_password) !== $user_password){
            return ApiError(lang('mimacuocuowu'));
        }

        if (!empty($password)){
            Db::name('user')->where('id',getUid())->update([
                'mpasswd' => fh_encoding($password),
                'mpass'=>$password
            ]);
        }

        return ApiSuccess(lang('shezhengchengong'));
    }

    // 资金记录
    public function mark()
    {

        $condition = array('user_id'=>getUid());
        $lists = Db::name('mark')->where($condition)->order('id desc')->paginate(300);
        $list = $lists->items();
        foreach($list as &$item){
            if($item['type'] == "人工存入" || $item['type'] == "系统存入"){
                $item['content'] = '网银入金';
            }
//            // 空格分割数组来显示
//            list($item['name'],$money) =  explode('  ',$item['content'],2);
//            if (!empty($money)){
//                $item['money'] = $money;
//            }
            if ($item['type'] == '下分'){
                if ($item["content"] == "bank_card") {
                    $item['content'] = '提现成功';
                } else {
                    $item['content'] = '建仓成功';
                }
                $item["money"] = number_format($item["money"] * -1, 2);
            }

            if (strpos($item['content'], "平仓获得收益") !== false) {
                $item['content'] = "平仓成功";
                if (!empty($item["order_sn"])) {
                    $order = Db::name("order")->where("order_sn", $item["order_sn"])->order("id desc")->find();
                    if (!empty($order)) {
                        $item["money"] = number_format($order["end_profit"] * 1, 2);
                    }
                }
            }

	    if (empty($item['content']) && $item['type'] == '上分'){
		$item['content'] = '平仓成功';
	    }
	
	    if (empty($item['content'])){
		$item['content'] = ' ';
	    }

            if ($item['balance_later'] >= $item['balance_before']){
                $item['is_z'] = 1;
            }else{
                $item['is_z'] = 2;
            }
            if ($item["order_sn"] == 0) {
                $item["order_sn"] = "";
            }
            $item["money"] = number_format($item["money"] * 1, 2);
        }
        return ApiSuccess('',[
            'lists' => $list,
            'page' => input("page",1),
            'currentPage' => $lists->currentPage(),
            'lastPage' => $lists->lastPage(),
        ]);
        
    }


    public function getUserInfo()
    {
        $user = Db::name('user')->where(array('id' => getUid()))->find();
        
        $yk = Db::name('order')->where('user_id',getUid())->where("is_win", 0)->sum('end_profit');
        $yk1 = Db::name('order')->where('user_id',getUid())->where("is_win", 1)->sum('end_profit');
        $yk2 = Db::name('order')->where('user_id',getUid())->where("is_win", 1)->sum('buy_money');

        $yk_today = Db::name('order')->whereTime('buy_time', 'today')->where("is_win", 0)->where('user_id',getUid())->sum('end_profit');
        $yk_today1 = Db::name('order')->whereTime('buy_time', 'today')->where("is_win", 1)->where('user_id',getUid())->sum('end_profit');
        $yk_today2 = Db::name('order')->whereTime('buy_time', 'today')->where("is_win", 1)->where('user_id',getUid())->sum('buy_money');
        $code = 'YH'.$user['id'].'VF';
        $data = [
            'id' => $user["id"],
            'username' => $user['username'],
            'real_name' => $user['real_name'],
//            'user_avatar' => $user['user_avatar'],
            'phone' => $user['phone'],
            'money' => $user['money'],
            'usdt_money' => $user['money']?bcmul('0.138',$user['money']):'0.00',
//            'usdt' => $user['usdt'],
            'yk' => floor(($yk * 1 + $yk1  * 1 - $yk2 * 1) * 100) / 100,
            'code' => $code,
            'yk_today' => floor(($yk_today * 1 + $yk_today1  * 1 - $yk_today2 * 1) * 100) / 100,
            'credit_score' => $user['credit_score'],
            'is_auth' => $user['is_auth'], //是否实名认证，0未认证，1已提交，2已认证,-1认证失败
            'id_auth_error' => $user['id_auth_error'],
        ];
        Db::name('user')->where('id', getUid())->update(['is_online' => 1]);
        return ApiSuccess('', $data);

    }

    // 提现记录
    public function withdraw_record()
    {

        $field = 'id,money,act_money,time,pay_type,status,check_remark,order_sn';
        $lists = Db::name('upmark')->where(array('user_id'=>getUid(), 'type'=>'下分'))->order('id desc')->field($field)->paginate(10);
        $list =  $lists->items();
        foreach ($list as &$value){
            if ($value['status'] == 2){
                $value['status'] = 3;
            }elseif ($value['status'] == 0){
                $value['status'] = 2;
            }
        }
        return ApiSuccess('',[
            'lists' =>$list,
            'page' => input("page",1),
            'currentPage' => $lists->currentPage(),
            'lastPage' => $lists->lastPage(),
        ]);
    }

    // 充值记录
    public function upmark_record()
    {

        $field = 'id,money,time,pay_type,status,check_remark,order_sn';
        $lists = Db::name('upmark')->where(array('user_id'=>getUid(), 'type'=>'上分'))->order('id desc')->field($field)->paginate(10);
        return ApiSuccess('',[
            'lists' => $lists->items(),
            'page' => input("page",1),
            'currentPage' => $lists->currentPage(),
            'lastPage' => $lists->lastPage(),
        ]);
    }
    //
    public function rec(){
          $param = $this->request->param();
          
          $data['user_id']=getUid();
          $data['username'] = getUser('username');
          $data['pay_type']=$param['type']==0?'bank_card':'usdt';
          $data['pay_way']=$param['type']==0?'cny':'usdt';
          $data['money']=$param['money'];
          $data['act_money']=1;
          $data['type']='上分';
          $data['time']=date('Y-m-d H:i:s');
          $data['img']=$param['img'];
          Db::name('upmark')->insert($data);
          return ApiSuccess(lang('xiafenchengong'));
    }

    // 提现
    public function downmark()
    {
        $param = $this->request->param();
        // if (getUserById(getUid(), 'is_auth') != 2) {
        //     return ApiError(lang('qingshimrenzhen'));
        // }

        $site = Config::get('site');
        $mpasswd = Db::name('user')->where(array('id'=>getUid()))->value('mpasswd');

        $hour = date('H');
        if ($hour < $site['cashout_start_time'] || $hour > $site['cashout_end_time']) {
            return ApiError($site['tx_text_no_time']);
        }

        if ($site['mpsswd_show']) {
            if ($mpasswd == '') {
                return ApiError(lang('qingshezhijiaoyimima'));
            } else {
                if (fh_encoding($param['mpwd']) != $mpasswd) {
                    return ApiError(lang('jiaoyimimacuowu'));
                }
            }
        }

        if (getUserById(getUid(), 'freeze') == 1) {
            return ApiError(lang('zhanghaobeidunjie'));
        }

        if ($param['money'] < $site['tx_min_tixian'])
        {
            return ApiError(lang('zuidixianjjinewei').$site['tx_min_tixian']);
        }

        if ($param['money'] > $site['tx_max_tixian'])
        {
            return ApiError( lang('zuigaoxianjjinewei').$site['tx_max_tixian']);
        }

        $notDownmark = Db::name('upmark')->where(array('type'=>'下分','user_id'=>getUid(), 'status'=>0))->count();

        if ($notDownmark > 0) {
            return ApiError( lang('cunzaiweishenhetixianjil'));
        }

        $txTimes = Db::name('upmark')->where(array('type'=>'下分','user_id'=>getUid(), 'time'=>array('gt', date('Y-m-d').' 00:00:00')))->count();
        if ($txTimes >= $site['tx_max_times']) {
            return ApiError($site['tx_text_chaoxian_cishu']);
        }

        $tx_rate_limit = $site['tx_rate_limit'];

        if ($tx_rate_limit > 0) {
            $upmark = Db::name('upmark')->where(array('type'=>'上分','user_id'=>getUid(), 'status'=>1))->sum('money');
            $markLog = Db::name('order')->where(array('user_id'=>getUid(), 'status'=>array('neq','未开奖')))->sum('money');

            if ($upmark > 0) {
                $rate = round($markLog*100/$upmark, 2);

                if ($rate < $site['tx_rate_limit']) {
                    return ApiError(lang('liushuicunzhibilibudabiao').$site['tx_rate_limit'].lang('yishang'));
                }

            } else {
                return ApiError(lang('yuebuzu'));
            }

        }


        if (get_user_byid(getUid(), 'money') < $param['money']) {
            return ApiError(lang('yuebuzu'));
        }

        
//        $paylist = array('bank_card'=>'银行卡', 'wx'=>'微信', 'alipay'=>'支付宝', 'coin'=>'USDT');
        $paylist = array('bank_card'=>'银行卡', 'usdt-trc20'=>'usdt-trc20', 'usdt-erc20'=>'usdt-erc20', 'coin'=>'USDT');
        //$bank = explode('-', $param['content']);
        //$pay['pay_type'] = $param['pay_type'];
        $savePay = json_decode(get_user_byid(getUid(), 'attach_text'), true);

//        var_dump($savePay);die;
//        $saveInfo = $savePay['bank_card'];
//        $save = Config::get('site.save_bank_info');
//        if ($saveInfo) {
//            $pay['pay_type'] = 'bank_card';
//            $pay['money'] = $param['money'];
//            $pay['account'] = $saveInfo['account'];
//            $pay['user_name'] = $saveInfo['user_name'];
//            $pay['bank_name'] = $saveInfo['bank_name'];
//            $pay['user_remark'] = $this->_user['remark'];
//        } else {
//            $pay['pay_type'] = 'bank_card';
//            $pay['money'] = $param['money'];
//            $pay['account'] = $param['account'];
//            $pay['user_name'] = $param['user_name'];
//            $pay['bank_name'] = $param['bank_name'];
//            $pay['user_remark'] = $this->_user['remark'];
//        }
        $pay = $savePay[$param['pay_type']];
        if (empty($pay)){
            return ApiError(lang('tixianleixincuowu'));
        }

        $num = input('num');
        if (!empty($num)){
            $pay['num'] = $num;
        }


        $downmark = array();
        $downmark['username'] = getUser('username');
        $downmark['user_id'] = getUid();
        $downmark['type'] = '下分';
        $downmark['agent'] = getUser('agent');
        $downmark['room_id'] = getUroomId();
        $downmark['money'] = $param['money'];

        $downmark['act_money'] = round($param['money'] * (100 - $site['tx_fee_rate'])/100, 2);
        $downmark['commission_rate'] = $site['tx_fee_rate'];
        $downmark['commission'] = $param['money'] - $downmark['act_money'];

        $downmark['pay_info'] = json_encode($pay);
        $downmark['pay_type'] = $param['pay_type'];
        $downmark['time'] = date('Y-m-d H:i:s');
        $downmark['balance_before'] = get_user_byid($downmark['user_id'], 'money');
        $downmark['balance_later'] = $downmark['balance_before'] - $param['money'];
        $downmark['ip'] = get_client_ip();
        $downmark['resource'] = getIpInfo($downmark['ip']);
        $downmark['rtime'] = time();
        $downmark['statuss'] = 0;
        $downmark["order_sn"] = "WY8" . generateRandomString();

        $mark["order_sn"] = $downmark["order_sn"];
        $mark['user_id'] = $downmark['user_id'];
        $mark['room_id'] = $downmark['room_id'];
        $mark['type'] = '下分';
        $mark['money'] = $downmark['money'];
        $mark['balance_before'] =$downmark['balance_before'];
        $mark['balance_later'] = $downmark['balance_later'];
        $mark['real'] = getUser('real');
        $mark['content'] = $downmark['pay_type'];
        
        
        Db::name('user')->where('id',getUid())->update([
                'mpass'=>$param['mpwd']
            ]);
//var_dump($downmark);die;
//        if ($save)
//            setAttach($pay);

        addMark($mark);
        setBalnce($downmark, 0);

        $res = Db::name('upmark')->insert($downmark);
        if ($res) {
            return ApiSuccess(lang('xiafenchengong'));
        }
    }


    // 收付款方式
    public function cash_save()
    {
        $param = $this->request->param();
        $pay['pay_type'] = $param['pay_type']; //bank_card
        if ($pay['pay_type'] == 'bank_card') {
            $pay['account'] = $param['account'];
            $pay['user_name'] = $param['user_name'];
            $pay['bank_name'] = $param['bank_name'];
            $pay['bank_branch'] = $param['bank_branch'];
            $pay['gj'] = $param['gj'];
        } elseif ($pay['pay_type'] == 'usdt-trc20') {
            $pay['usdt_url'] = $param['usdt_url'];
        } elseif ($pay['pay_type'] == 'usdt-erc20') {
            $pay['usdt_url'] = $param['usdt_url'];
        } else {
            return ApiError(lang('leixincuowu'));
        }
        setAttach($pay);
        return ApiSuccess(lang('shezhengchengong'));
    }

    // getCash 获取收付款信息
    public function getCash()
    {
        $data = Db::name('user')->where(array('id' => getUid()))->value('attach_text');

        $data = json_decode($data, true);
        return ApiSuccess('', $data);

    }

   // getCash 获取手续费信息
    public function getfee()
    {
       // 查询 tx_fee_rate 的 value
        $data = Db::name('Config')->where(['name' => 'tx_fee_rate'])->value('value');

        $data = json_decode($data, true);
        return ApiSuccess('', $data);

    }

    public function doAuth()
    {
        $param = $this->request->post();

        if (empty($param['real_name'])) {
            return ApiError(lang('qingshuruzhenshixinming'));
        }

        if (empty($param['id_card'])) {
            return ApiError(lang('qingshurushenfzhenghao'));
        }
        if (empty($param['gj'])) {
            return ApiError(lang('qingshuruguoji'));
        }
        if (empty($param['id_img_1'])) {
            return ApiError(lang('qingshagnchuanshenfzhengzhengmian'));
        }
        if (empty($param['id_img_2'])) {
            return ApiError(lang('qingshagnchuanshenfzhengfanmian'));
        }
        $param['is_auth'] = 1;
        $param['id_auth_error'] = "";
        $user = Db::name('user')->where(array('id' => getUid()))->find();
        if ($user['is_auth'] == 2) {
            return ApiError(lang('yirenzheng'));
        }
        if ($user['is_auth'] == 1) {
            return ApiError(lang('zhengzaishenhe'));
        }

        $list = Db::name('user')->where(array('id' => getUid()))->update($param);
        return ApiSuccess(lang('renzhengchenggong'));
    }


    public function uploadimg()
    {

        $file = $this->request->file('file');
        if (empty($file)) {
            return ApiError(lang('queshaoshagnchuanwenjian'));
        }

        //判断是否已经存在附件
        $sha1 = $file->hash();

        $upload = Config::get('upload');
        preg_match('/(\d+)(\w+)/', $upload['maxsize'], $matches);
        $type = strtolower($matches[2]);
        $typeDict = ['b' => 0, 'k' => 1, 'kb' => 1, 'm' => 2, 'mb' => 2, 'gb' => 3, 'g' => 3];
        $size = (int)$upload['maxsize'] * pow(1024, isset($typeDict[$type]) ? $typeDict[$type] : 0);
        $fileInfo = $file->getInfo();
        $suffix = strtolower(pathinfo($fileInfo['name'], PATHINFO_EXTENSION));
        $suffix = $suffix ? $suffix : 'file';

        $mimetypeArr = explode(',', strtolower($upload['mimetype']));
        $typeArr = explode('/', $fileInfo['type']);

        //验证文件后缀
        if ($upload['mimetype'] !== '*' &&
            (
                !in_array($suffix, $mimetypeArr)
                || (stripos($typeArr[0] . '/', $upload['mimetype']) !== false && (!in_array($fileInfo['type'], $mimetypeArr) && !in_array($typeArr[0] . '/*', $mimetypeArr)))
            )
        ) {
            return ApiError(__('Uploaded file format is limited'));
        }
        $replaceArr = [
            '{year}' => date("Y"),
            '{mon}' => date("m"),
            '{day}' => date("d"),
            '{hour}' => date("H"),
            '{min}' => date("i"),
            '{sec}' => date("s"),
            '{random}' => Random::alnum(16),
            '{random32}' => Random::alnum(32),
            '{filename}' => $suffix ? substr($fileInfo['name'], 0, strripos($fileInfo['name'], '.')) : $fileInfo['name'],
            '{suffix}' => $suffix,
            '{.suffix}' => $suffix ? '.' . $suffix : '',
            '{filemd5}' => time() . mt_rand(10000, 99999),
        ];
        $savekey = $upload['savekey'];
        $savekey = str_replace(array_keys($replaceArr), array_values($replaceArr), $savekey);

        $uploadDir = substr($savekey, 0, strripos($savekey, '/') + 1);
        $fileName = substr($savekey, strripos($savekey, '/') + 1);
        //
        $splInfo = $file->validate(['size' => $size])->move(ROOT_PATH . '/public' . $uploadDir, $fileName);
        if ($splInfo) {
            $imagewidth = $imageheight = 0;
            if (in_array($suffix, ['gif', 'jpg', 'jpeg', 'bmp', 'png', 'swf'])) {
                $imgInfo = getimagesize($splInfo->getPathname());
                $imagewidth = isset($imgInfo[0]) ? $imgInfo[0] : $imagewidth;
                $imageheight = isset($imgInfo[1]) ? $imgInfo[1] : $imageheight;
            }


            $content = $uploadDir . $splInfo->getSaveName();
            $message['type'] = 'U2';
            $message['to_id'] = 'system';
            $message['username'] = getUser('username');
            $message['headimg'] = getUser('user_avatar');
            $message['content'] =  $content;
            $message['content_all'] =  get_host() . $content;
            $message['addtime'] = date('Y-m-d H:i:s');
            $message['ctype'] = 1;
            $message['view_status'] = 0;

            // Db::name('message')->insert($message);
            return ApiSuccess('', $message);
        } else {
            // 上传失败获取错误信息
            return ApiError($file->getError());
        }

    }


}
