<?php

namespace app\index\controller;

use app\index\controller\Common;
use think\Request;
use think\Db;
use think\Config;
use fast\Random;
use Endroid\QrCode\QrCode;

class User extends Common
{
    private $_user = array();
    
    public function _initialize() {
        parent::_initialize();
        $user = getUserById(getUid());
        $this->_user = $user;
        $action = $this->request->action();
        if (in_array($action, array('team', 'myextend', 'spread'))) {
            if (!$user['isagent']) {
               die;   
            }
        }
        $current = $this->request->action();
        $this->view->assign('current', $current);
        $this->view->assign('user', $user);
    }
   
   
    public function credit_score()
    {
        
        $this->view->config('view_path', '../application/index/view/'.Config::get('site.template_theme').DS);
        return $this->view->fetch();
    }
    
    public function index()
    {
        if (get_user_byid(getUid(),'isagent')) {
            $agent = Db::name('user')->where(array('agent'=>getUid(), 'status'=>1))->count();
        } else {
            $agent = 0;
        }
        $this->view->assign('agent', $agent)->assign('userdata', getUserToday());
        $this->view->config('view_path', '../application/index/view/'.Config::get('site.template_theme').DS);
        
        return $this->view->fetch();
    }
    
    public function shiming()
    {
        // if (get_user_byid(getUid(),'isagent')) {
        //     $agent = Db::name('user')->where(array('agent'=>getUid(), 'status'=>1))->count();
        // } else {
        //     $agent = 0;
        // }
        // $this->view->assign('agent', $agent)->assign('userdata', getUserToday());
        $this->view->config('view_path', '../application/index/view/'.Config::get('site.template_theme').DS);
        
        return $this->view->fetch();
    }

    public function doAuth(){
        $param = $this->request->post();
        $data=array('code'=>301,'msg'=>"");
        
        if(empty($param['real_name'])){
            $data['msg'] = "请输入真实姓名";
            return json($data);
        }
        
        if(empty($param['id_card'])){
            $data['msg'] = "请输入身份证号";
            return json($data);
        }
        // if(empty($param['id_img_1'])){
        //     $data['msg'] = "请上传身份证正面照";
        //     return json($data);
        // }
        // if(empty($param['id_img_2'])){
        //     $data['msg'] = "请上传身份证反面照";
        //     return json($data);
        // }
        $param['is_auth']=2;
        $param['id_auth_error']="";
        $user =  Db::name('user')->where(array('id'=>getUid()))->find();
        // if($user['is_auth'] == 2){
        //     $data['msg'] = "已认证成功，请勿重复认证";
        //     return json($data);
        // }
        // if($user['is_auth'] == 1){
        //     $data['msg'] = "正在审核中，请勿重复认证";
        //     return json($data);
        // }
        
        $list = Db::name('user')->where(array('id'=>getUid()))->update($param);
        $data['code'] = 200;
        $data['msg'] = "认证成功！";
        return json($data);
    }
    
    public function balance() {
        $list = Db::name('mark')->where(array('user_id'=>getUid()))->order('id desc')->paginate(10);
        $this->assign('list', $list);
        if (Config::get('site.template_theme') == 'default')
            $this->view->config('view_path', '../application/index/view/'.Config::get('site.template_theme').DS);
            else $this->view->config('view_path', '../application/index/view/tmp1'.DS);
        return $this->view->fetch();
    }
    
    public function user()
    {
        $kefu_config = Db::name('config')->where(array('name'=>'kefu_url'))->find();
        $this->assign('kefu_config', $kefu_config);
        $this->view->config('view_path', '../application/index/view/'.Config::get('site.template_theme').DS);
        return $this->view->fetch();
    }
    
    public function mark()
    {
        $this->view->config('view_path', '../application/index/view/'.Config::get('site.template_theme').DS);
        return $this->view->fetch();
    }
    
    public function exchange_record()
    {
        $this->view->config('view_path', '../application/index/view/'.Config::get('site.template_theme').DS);
        return $this->view->fetch();
    }
    
    
    public function exchange()
    {
        $this->view->config('view_path', '../application/index/view/'.Config::get('site.template_theme').DS);
        return $this->view->fetch();
    }
    
    public function upmark()
    {
        
        $this->view->config('view_path', '../application/index/view/'.Config::get('site.template_theme').DS);
        return $this->view->fetch();
    }
    
    public function kefu()
    {
       
        $this->view->assign('cuser', get_user_byid(getUid()));
        $this->view->config('view_path', '../application/index/view/'.Config::get('site.template_theme').DS);
        return $this->view->fetch();
    }
    public function wallet()
    {
        $data = array(
            'total' => $this->_user['money'] + $this->_user['usdt'] * get_config_value('usdt_cny_rate'),
            'usdt'  => $this->_user['usdt'] * get_config_value('usdt_cny_rate'),
            'cny_freeze' => Db::name('upmark')->where(array('type'=>'下分', 'status'=>0))->sum('money'),
            'kefu_config' => Db::name('config')->where(array('name'=>'kefu_url'))->find()
            
        );
        $this->view->assign('data', $data);
        $this->view->config('view_path', '../application/index/view/'.Config::get('site.template_theme').DS);
        return $this->view->fetch();
    }
    
    public function recharge()
    {
        $type = $this->request->param('type');
        $template = '';
        if ($type == 'bank') {
            $bank['status'] = Config::get('site.bank_status');
            $bank['web_bank_name'] = Config::get('site.web_bank_name');
            $bank['web_bank_user'] = Config::get('site.web_bank_user');
            $bank['web_bank_number'] = Config::get('site.web_bank_number');
            $bank['web_bank_tips'] = Config::get('site.web_bank_tips');
            $this->view->assign('bankpay', $bank);
            $template = 'user/recharge/bank';
        } elseif ($type == 'coin') {
            $coin = Config::get('site.usdt_address');
            $this->view->assign('coin', $coin)->assign('coinData', json_encode($coin))->assign('coinList', array_values($coin))->assign('coinKey', array_keys($coin));
            $template = 'user/recharge/coin';
        } elseif ($type == 'thirdpay') {
            $alipay['qrcode'] = Config::get('site.alipay_qrcode');
            $alipay['status'] = Config::get('site.alipay_status');
            $wxpay['qrcode'] = Config::get('site.wxpay_qrcode');
            $wxpay['status'] = Config::get('site.weixin_status');
            $this->view->assign('alipay', $alipay)->assign('wxpay', $wxpay);
            $template = 'user/recharge/thirdpay';
        }
        $this->view->config('view_path', '../application/index/view/'.Config::get('site.template_theme').DS);
        return $this->view->fetch($template);
    }
    
    public function withdraw()
    {
        
        $save = Config::get('site.save_bank_info');
        if ($save) {
            $attach_text = $this->_user['attach_text'];
            if ($attach_text) {
                $pay = json_decode($attach_text, true);
                $pay['bank_card']['account'] = '****'.substr($pay['bank_card']['account'], -4);
                $pay['bank_card']['save'] = 1;
                $this->view->assign('attach', $pay['bank_card']);
            }
        }

        $this->view->assign('mpsswd_show', Config::get('site.mpsswd_show'));
        $this->view->config('view_path', '../application/index/view/'.Config::get('site.template_theme').DS);
        if (!$this->_user['mpasswd'] && Config::get('site.mpsswd_show') == 1) {
            return $this->view->fetch('user/withdraw/withdraw_mpwd');
        } else {
            return $this->view->fetch('user/withdraw/withdraw');
        
        }
        
    }
    
    public function recharge_record()
    {
        $this->view->config('view_path', '../application/index/view/'.Config::get('site.template_theme').DS);
        return $this->view->fetch('user/recharge/recharge_record');
    }
    
    public function withdraw_record()
    {
        $this->view->config('view_path', '../application/index/view/'.Config::get('site.template_theme').DS);
        return $this->view->fetch('user/withdraw/withdraw_record');
    }
    
    public function withdraw_account()
    {
        $this->view->config('view_path', '../application/index/view/'.Config::get('site.template_theme').DS);
        return $this->view->fetch('user/withdraw/withdraw_account');
    }
    
    public function pwd()
    {
        $this->view->config('view_path', '../application/index/view/'.Config::get('site.template_theme').DS);
        return $this->view->fetch();
    }
    
    public function mpwd()
    {
        
        $this->view->config('view_path', '../application/index/view/'.Config::get('site.template_theme').DS);
        if (!$this->_user['mpasswd']) {
            return $this->view->fetch('user/withdraw/withdraw_mpwd');
        }
        return $this->view->fetch();
    }
    
    public function orderlist()
    {
        $this->view->config('view_path', '../application/index/view/'.Config::get('site.template_theme').DS);
        return $this->view->fetch();
    }
    
    
    public function account()
    {
        if (Config::get('site.template_theme') == 'default')
            $this->view->config('view_path', '../application/index/view/'.Config::get('site.template_theme').DS);
            else $this->view->config('view_path', '../application/index/view/tmp1'.DS);
        return $this->view->fetch();
    }
    
    public function bind()
    {
        if (Config::get('site.template_theme') == 'default')
            $this->view->config('view_path', '../application/index/view/'.Config::get('site.template_theme').DS);
            else $this->view->config('view_path', '../application/index/view/tmp1'.DS);
        return $this->view->fetch();
    }
    
    public function share()
    {
        $qr_domain = Config::get("site.wx_qrdomain1");
        $info = "http://{$qr_domain}/?agent=".getUid();
        $this->view->assign('url', $info);
        $this->view->config('view_path', '../application/index/view/'.Config::get('site.template_theme').DS);
        return $this->view->fetch();
    }
    
    
    public function getQrcode() {
        $qr_domain = Config::get("site.wx_qrdomain1");
        $info = "http://{$qr_domain}/?agent=".getUid();
        $qrCode = new QrCode($info);
        header('Content-Type: '.$qrCode->getContentType('png'));
        echo $qrCode->get();
        exit;
    }  
    
    public function chat()
    {
        $kefu = Config::get('site.kefu_script');
        if ($kefu) {
            $this->view->assign('kefu', $kefu);
            $this->view->config('view_path', '../application/index/view/'.Config::get('site.template_theme').DS); 
            //$this->view->config('view_path', '../application/index/view/default'.DS);
            return $this->view->fetch();
        }

    }
    
    public function setting() {
        if (Config::get('site.template_theme') == 'default')
            $this->view->config('view_path', '../application/index/view/'.Config::get('site.template_theme').DS);
            else $this->view->config('view_path', '../application/index/view/tmp1'.DS);
        return $this->view->fetch();
    }
    
    
    
    public function ajax() {
        
        if ($this->request->isAjax()) {
            $param = $this->request->param();
            $result = array('status'=> 0);
            $request = $param['request'];
            switch ($request) {
                case 'getTradeList':
                    $result['status'] = 1;
                    $order = Db::name('order')->where(array('id'=>$param['id'], 'status'=>3))->find();
                    $result['data'] = array();
                    if ($order) {
                       
                        $olist = array();
                        $olist[] = array(
                            'id' => $order['id'],
                            'open_price' => $order['buy_price'],
                            'number' => (float)$order['buy_money'],
                            'type' => $order['o_style'],
                            'fact_profits' => $order['end_profit'],
                            'profit_ratio' => $order['profit_ratio'],
                            'endTime' => strtotime($order['sell_time'])*1000,
                            'remain_milli_seconds' => (strtotime($order['sell_time']) - time())*1000,
                            'status' => $order['status'],
                        );
                        $result['data'] = $olist;
                    }
                    break;
                case 'microtrade':
                    if (getUserById(getUid(), 'is_auth') != 2) {
                        $result['msg'] = '请先实名认证';
                        return json($result);
                    }
                    
                    $site = Config::get('site');
                    if (getUserById(getUid(), 'freeze') == 1) {
                        $result['msg'] = 'account_limit';//账户被冻结
                        return json($result);
                    }
                    // 判断是否在交易时间内
                    if(!empty($site['trade_time'])){
                        $trade_timeStr = $site['trade_time'];
                        $trade_time = explode("-",$trade_timeStr);
                        $startTimeStr = $trade_time[0];
                        $startTime = strtotime(date("Y-m-d {$startTimeStr}:00"));
                        $endTimeStr = $trade_time[1];
                        $endTime = strtotime(date("Y-m-d {$endTimeStr}:00"));
                        $nowTime = time();
                        if(!($nowTime >= $startTime && $nowTime <= $endTime)){
                            $result['msg'] = "市场已休市，请于{$trade_timeStr}时间段内进行交易";
                            return json($result);
                        }
                    }
                    
                    
                    if ($param['number'] > $site['bet_max'] || $param['number'] < $site['bet_min']) {
                        $result['msg'] = '最小购买金额:'.$site['bet_min'].'最大购买金额:'.$site['bet_max'];//账户被冻结
                        return json($result);
                    }
                    
                    $stock = getStockByCode($param['currency_id']);
                    if (!$stock) {
                        $result['msg'] = '交易品种不存在';
                        return json($result);
                    }
                    
                    if ($param['number'] <= get_user_byid(getUid(), 'money')) {
                        
                        $timeList = array();
                        $play_rule = json_decode($stock['play_rule'], true);
                        foreach ($play_rule as $key=>$val) {
                            $timeList[$val['time']] = $val;
                        }
                        if (!isset($timeList[$param['seconds']])) {
                            $result['msg'] = '交易参数错误';
                            return json($result);
                        }
                        
                        $mini = $param['seconds'];
                        
                        $now = time();

                        $order['buy_money'] = $param['number'];
                        $order['user_id'] = getUid();
                        $order['p_id'] = $stock['id'];
                        $order['p_code'] = $stock['code'];
                        $order['seconds'] = $mini;
                        $order['p_title'] = $stock['title'];
                        $order['o_style'] = $param['type'];
                        $order['buy_time'] = date('Y-m-d H:i:s', $now);
                        $order['sell_time'] = date('Y-m-d H:i:s', $now + $mini);
                        $order['buy_price'] = $stock['price'];
                        $order['win_profit'] = $timeList[$param['seconds']]['win'];
                        $order['lose_profit'] = $timeList[$param['seconds']]['lose'];
                        
                        $order['balance_buy_before'] = get_user_byid(getUid(), 'money');
                        $order['balance_buy_after'] = $order['balance_buy_before'] - $order['buy_money'];
                        $res = DB::name('order')->insertGetId($order);
                        if ($res) {
                            $mark['user_id'] = $order['user_id'];
                            $mark['room_id'] = 1;
                            $mark['type'] = '下分';
                            $mark['money'] = $order['buy_money'];
                            $mark['balance_before'] = $order['balance_buy_before'];
                            $mark['balance_later'] = $order['balance_buy_after'];
                            $mark['real'] = $param['real'];
                            $mark['content'] = '购买'.$param['currency_id'];
                            
                            addMark($mark);//写入流水
                            setBalnce(array('user_id'=>getUid(), 'money'=>$order['buy_money']), 0);//下分
                            
                            $result['status'] = 1;
                            $result['balance'] = $mark['balance_later'];
                            $result['buy_price'] = $order['buy_price'];
                            
                        }
                        
                    } else {
                        $result['msg'] = 'runningLow';//余额不足
                    }
                    break;
                case 'newOrderList':
                    
                    $orderlist = Db::name('order')->where(array('p_code'=>$param['type'], 'status'=>1))->order('id desc')->select();
                    $olist = array();
                    $result['status'] = 1;
                    foreach ($orderlist as $key=>$order) {
                        $olist[] = array(
                            'id' => $order['id'],
                            'open_price' => $order['buy_price'],
                            'number' => (float)$order['buy_money'],
                            'type' => $order['o_style'],
                            'profit_ratio' => $order['win_profit'],
                            'endTime' => strtotime($order['sell_time'])*1000,
                            'fact_profits' => $order['ploss'],
                            'remain_milli_seconds' => (strtotime($order['sell_time']) - time())*1000,
                            'status' => $order['status'],
                        );
                    }
                    $result['data'] = $olist;
                    break;
                case 'info':
                    $result['status'] = 1;
                    $result['uid'] = $this->_user['id'];
                    $result['balance'] = $this->_user['money'];
                    break;
                case 'orderlist':
                    $result['status'] = 1;
                    $page = $param['page'] ? $param['page'] : 1;
                    $size = $param['size'] ? $param['size'] : 5;
                    $limit = ($page-1)*$size.','.$size;
                    $condition = array('user_id'=>getUid(), 'status'=>$param['status']);
                    $list = Db::name('order')->where($condition)->order('id desc')->limit($limit)->select();
                    $olist = array();
                    foreach ($list as $key=>$order) {
                        $olist[] = array(
                            'id' => $order['id'],
                            'code' => $order['p_code'].'/'.$order['p_title'],
                            'title'=>$order['p_title'],
                            'open_price' => $order['buy_price'],
                            'number' => (float)$order['buy_money'],
                            'type' => $order['o_style'],
                            'ploss' => $order['ploss'],
                            'profit_ratio' => $order['win_profit'],
                            'endTime' => strtotime($order['sell_time'])*1000,
                            'remain_milli_seconds' => (strtotime($order['sell_time']) - time())*1000,
                            'status' => $order['status'],
                            'buy_time' => $order['buy_time'],
                            'sell_time' => $order['sell_time'],
                            'end_price' => $order['sell_price'],
                            'end_profit' => $order['end_profit']
                        );
                    }
                    $result['data'] = $olist;
                    break;
                case 'mark':
                    $result['status'] = 1;
                    $page = $param['page'] ? $param['page'] : 1;
                    $size = $param['size'] ? $param['size'] : 5;
                    $limit = ($page-1)*$size.','.$size;
                    $condition = array('user_id'=>getUid());
                    $list = Db::name('mark')->where($condition)->order('id desc')->limit($limit)->select();
                    foreach($list as &$item){
                        if($item['type'] == "人工存入"||$item['type'] == "系统存入"){
                            $item['content'] = $item['type'];
                        }
                    }
                    $result['data'] = $list;
                    break;
                case 'withdraw_record':
                    $result['status'] = 1;
                    $page = $param['page'] ? $param['page'] : 1;
                    $size = $param['size'] ? $param['size'] : 5;
                    $limit = ($page-1)*$size.','.$size;
                    $field = 'id,money,time,pay_type,status,check_remark';
                    $list = Db::name('upmark')->where(array('user_id'=>getUid(), 'type'=>'下分'))->order('id desc')->field($field)->limit($limit)->select();
                    $result['data'] = $list;
                    break;
                case 'upmark_record':
                    $result['status'] = 1;
                    $page = $param['page'] ? $param['page'] : 1;
                    $size = $param['size'] ? $param['size'] : 5;
                    $limit = ($page-1)*$size.','.$size;
                    $field = 'id,money,time,pay_type,status';
                    $list = Db::name('upmark')->where(array('user_id'=>getUid(), 'type'=>'上分'))->order('id desc')->field($field)->limit($limit)->select();
                    $result['data'] = $list;
                    break;
                case 'exchange_record':
                    $result['status'] = 1;
                    $page = $param['page'] ? $param['page'] : 1;
                    $size = $param['size'] ? $param['size'] : 5;
                    $limit = ($page-1)*$size.','.$size;
                    $field = 'id,type_a,type_b,rate,amount,add_time,status';
                    $list = Db::name('exchange')->where(array('user_id'=>getUid()))->order('id desc')->field($field)->limit($limit)->select();
                    $result['data'] = $list;
                    break;
                case 'cash_info':
                    $result['status'] = 1;
                    $result['balance'] = $this->_user['money'];

                    $result['data'] = array();
                    
                    if (getUid()) {
                        
                        $attach_text = $this->_user['attach_text'];
                        $save = Config::get('site.save_bank_info');
                        if ($attach_text && $save) {
                            $pay = json_decode($attach_text, true);
                            if ($pay['bank_card']['account'] && $pay['bank_card']['user_name'] && $pay['bank_card']['bank_name']) {
                                if (!$param['type'])
                                    $pay['bank_card']['account'] =  $pay['bank_card']['account'];
                                    //$pay['bank_card']['account'] = '**** **** **** ***'.substr($pay['bank_card']['account'], -4);
                                $result['data']['bank'] = $pay['bank_card'];
                            }
                            
                            if ($pay['coin']['account']) {
                                if (!$param['type'])
                                    $pay['coin']['account'] = '**** **** **** ***'.substr($pay['coin']['account'], -4);
                                $result['data']['coin'] = $pay['coin'];
                            }
                                
                        }
                        
                        if (Config::get('site.cny_open') == 1) {
                            $coin = array(
                                'symbol' => '银行卡',
                                'balance'=> $this->_user['money']
                            );
                            $result['data']['list'][] = $coin;
                        }
                        if (Config::get('site.usdt_open') == 1) {

                            $coin = array(
                                'symbol' => 'USDT',
                                'balance'=> $this->_user['usdt']
                            );
                            $result['data']['list'][] = $coin;
                        }
                    }
                    break;
                case 'cash_save':
                    if (getUid()) {
                        $pay['pay_type'] = 'bank_card';
                        $pay['account'] = $param['account'];
                        $pay['user_name'] = $param['user_name'];
                        $pay['bank_name'] = $param['bank_name'];
                        $pay['bank_branch'] = $param['bank_branch'];
                        setAttach($pay);
                        $result['status'] = 1;
                        $result['msg'] = '设置成功，是否跳转提现页面？';
                    }
                    break;
                case 'upmark':
                    $site = Config::get('site');
                    
                    if ($param['money'] < $site['min_chongzhi'])
                    {
                        $result['msg'] = '最低充值金额为'.$site['min_chongzhi'];
                        return json($result);
                    }
                    
                    if ($param['money'] > $site['max_chongzhi'])
                    {
                        $result['msg'] = '最高充值金额为'.$site['max_chongzhi'];
                        return json($result);
                    }
                     
                   
                    if (getUser('real') == 1) {
                        
                        $notUpmark = Db::name('upmark')->where(array('type'=>'上分','user_id'=>getUid(), 'status'=>0))->count();
                        
                        if ($notUpmark > 0) {
                            $result['msg'] = '存在未审核上分记录';
                            return json($result);
                        }
                        
                        $paylist = array('wxpay'=>'微信支付', 'alipay'=>'支付宝支付', 'bank_card'=>'银行卡支付');
                        $upmark = array();
                        $upmark['username'] = getUser('username');
                        $upmark['user_id'] = getUid();
                        $upmark['type'] = '上分';
                        $upmark['agent'] = getUser('agent');
                        $upmark['room_id'] = getUroomId();
                        $upmark['money'] = $param['money'];
                        //$bank_user_name = Db::name('bank')->where(array('user_id'=>getUid(), 'status'=>1))->value('name');
                        $upmark['pay_info'] = $this->_user['remark'];
                        $upmark['pay_type'] = $paylist[$param['pay_type']];
                        $upmark['time'] = date('Y-m-d H:i:s');
                        $upmark['ip'] = get_client_ip();
                        $upmark['resource'] = getIpInfo($upmark['ip']);
                        
                        $upmark['rtime'] = time();
                        $res = Db::name('upmark')->insert($upmark);
                        if ($res) {
                            $result['status'] = 1;
                            $result['msg'] = '操作成功';
                        }
                    }
                    if (getUser('real') == 0) {
                        $paylist = array('wxpay'=>'微信支付', 'alipay'=>'支付宝支付', 'bank_card'=>'银行卡支付');
                        $upmark = array();
                        $upmark['username'] = getUser('username');
                        $upmark['user_id'] = getUid();
                        $upmark['type'] = '上分';
                        $upmark['pay_way'] = 'cny';
                        $upmark['agent'] = getUser('agent');
                        $upmark['room_id'] = getUroomId();
                        $upmark['money'] = $param['money'];
                        $bank_user_name = Db::name('bank')->where(array('user_id'=>getUid(), 'status'=>1))->value('name');
                        $upmark['pay_info'] = $bank_user_name;
                        $upmark['pay_type'] = $paylist[$param['pay_type']];
                        $upmark['time'] = date('Y-m-d H:i:s');
                        $upmark['real'] = 0;
                        $upmark['status'] = 1;
                        $upmark['ip'] = get_client_ip();
                        $upmark['resource'] = getIpInfo($upmark['ip']);
                        $upmark['rtime'] = time();
                        $res = Db::name('upmark')->insert($upmark);
                        
                       $res = setBalnce(array('user_id'=>getUid(), 'money'=>$param['money']), 1);
                       if ($res) {
                           $result['status'] = 1;
                           $result['msg'] = '操作成功';
                       }
                    }
                    $result['url'] = url('journal/log');
                    break;
                case 'downmark':
                    if (getUserById(getUid(), 'is_auth') != 2) {
                        $result['msg'] = '请先实名认证';
                        return json($result);
                    }
                    
                    $site = Config::get('site');
                    $mpasswd = Db::name('user')->where(array('id'=>getUid()))->value('mpasswd');
                    
                    $hour = date('H');
                    if ($hour < $site['cashout_start_time'] || $hour > $site['cashout_end_time']) {
                        $result['msg'] = $site['tx_text_no_time'];
                        return json($result);
                    }
                    
                    if ($site['mpsswd_show']) {
                        if ($mpasswd == '') {
                            $result['status'] = 2;
                            return json($result);
                        } else {
                            if (fh_encoding($param['mpwd']) != $mpasswd) {
                                $result['msg'] = '资金密码不正确';
                                return json($result);
                            }
                        }
                    } 
                    
                    if (getUserById(getUid(), 'freeze') == 1) {
                        $result['msg'] = 'account_limit';//账户被冻结
                        return json($result);
                    }
                    
                    if ($param['money'] < $site['tx_min_tixian'])
                    {
                        $result['msg'] = '最低提现金额为'.$site['tx_min_tixian'];
                        return json($result);
                    }
                    
                    if ($param['money'] > $site['tx_max_tixian'])
                    {
                        $result['msg'] = '最高提现金额为'.$site['tx_max_tixian'];
                        return json($result);
                    }
                    
                    $notDownmark = Db::name('upmark')->where(array('type'=>'下分','user_id'=>getUid(), 'status'=>0))->count();
                    
                    if ($notDownmark > 0) {
                        $result['msg'] = 'czwshjl';
                        return json($result);
                    }
                    
                    $txTimes = Db::name('upmark')->where(array('type'=>'下分','user_id'=>getUid(), 'time'=>array('gt', date('Y-m-d').' 00:00:00')))->count(); 
                    if ($txTimes >= $site['tx_max_times']) {
                        $result['msg'] = $site['tx_text_chaoxian_cishu'];
                        return json($result);
                    }
                    
                    $tx_rate_limit = $site['tx_rate_limit'];
                    
                    if ($tx_rate_limit > 0) {
                        $upmark = Db::name('upmark')->where(array('type'=>'上分','user_id'=>getUid(), 'status'=>1))->sum('money');
                        $markLog = Db::name('order')->where(array('user_id'=>getUid(), 'status'=>array('neq','未开奖')))->sum('money');
                        
                        if ($upmark > 0) { 
                            $rate = round($markLog*100/$upmark, 2);

                            if ($rate < $site['tx_rate_limit']) {
                                $result['msg'] = '流水/充值比例不达标，需达到'.$site['tx_rate_limit'].'以上!';
                                return json($result);
                            }
                            
                        } else {
                            $result['msg'] = '账户余额不足!';
                            return json($result);
                        }
                        
                    }
                    
                    
                    $rest = true;
                    if (get_user_byid(getUid(), 'money') < $param['money']) {
                        $result['msg'] = 'runningLow';
                        return json($result);
                    }
                    
                    
                    $paylist = array('bank_card'=>'银行卡', 'wx'=>'微信', 'alipay'=>'支付宝', 'coin'=>'USDT');
                    //$bank = explode('-', $param['content']);
                    //$pay['pay_type'] = $param['pay_type'];
                    $savePay = json_decode(get_user_byid(getUid(), 'attach_text'), true);
                    $saveInfo = $savePay['bank_card'];
                    $save = Config::get('site.save_bank_info');
                    if ($saveInfo) {
                        $pay['pay_type'] = 'bank_card';
                        $pay['money'] = $param['money'];
                        $pay['account'] = $saveInfo['account'];
                        $pay['user_name'] = $saveInfo['user_name'];
                        $pay['bank_name'] = $saveInfo['bank_name'];
                        $pay['user_remark'] = $this->_user['remark'];
                    } else {
                        $pay['pay_type'] = 'bank_card';
                        $pay['money'] = $param['money'];
                        $pay['account'] = $param['account'];
                        $pay['user_name'] = $param['user_name'];
                        $pay['bank_name'] = $param['bank_name'];
                        $pay['user_remark'] = $this->_user['remark'];
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
                    $downmark['pay_type'] = $paylist[$pay['pay_type']];
                    $downmark['time'] = date('Y-m-d H:i:s');
                    $downmark['balance_before'] = get_user_byid($downmark['user_id'], 'money');
                    $downmark['balance_later'] = $downmark['balance_before'] - $param['money'];
                    $downmark['ip'] = get_client_ip();
                    $downmark['resource'] = getIpInfo($downmark['ip']);
                    $downmark['rtime'] = time();
                    
                    $mark['user_id'] = $downmark['user_id'];
                    $mark['room_id'] = $downmark['room_id'];
                    $mark['type'] = '下分';
                    $mark['money'] = $downmark['money'];
                    $mark['balance_before'] =$downmark['balance_before'];
                    $mark['balance_later'] = $downmark['balance_later'];
                    $mark['real'] = getUser('real');
                    $mark['content'] = $downmark['pay_type'].'下分金额';
                    
                    
                    if ($save)
                        setAttach($pay);
                    
                    addMark($mark);
                    setBalnce($downmark, 0);
                        
                    if ($rest) {
                       
                        $res = Db::name('upmark')->insert($downmark);
                        if ($res) {
                            $result['status'] = 1;
                            $result['msg'] = '下分提交成功';
                        }
                        
                    }
                    
                    
                    break;
                case 'coin_downmark':
                    
                    $site = Config::get('site');
                    
                    $hour = date('H');
                    if ($hour < $site['cashout_start_time'] || $hour > $site['cashout_end_time']) {
                        $result['msg'] = $site['tx_text_no_time'];
                        return json($result);
                    }
                    
                    if ($site['mpsswd_show']) {
                        $mpasswd = Db::name('user')->where(array('id'=>getUid()))->value('mpasswd');
                        
                        if ($mpasswd == '') {
                            $result['status'] = 2;
                            return json($result);
                        } else {
                            if (fh_encoding($param['mpwd']) != $mpasswd) {
                                $result['msg'] = '资金密码不正确';
                                return json($result);
                            }
                        }
                    }
                    
                    if (getUserById(getUid(), 'freeze') == 1) {
                        $result['msg'] = 'account_limit';//账户被冻结
                        return json($result);
                    }
                    
                    if ($param['money'] < $site['tx_min_tixian'])
                    {
                        $result['msg'] = '最低提现为'.$site['tx_min_tixian'];
                        return json($result);
                    }
                    
                    if ($param['money'] > $site['tx_max_tixian'])
                    {
                        $result['msg'] = '最高提现为'.$site['tx_max_tixian'];
                        return json($result);
                    }
                    
                    $notDownmark = Db::name('upmark')->where(array('type'=>'下分','user_id'=>getUid(), 'status'=>0))->count();
                    
                    if ($notDownmark > 0) {
                        $result['msg'] = 'czwshjl';
                        return json($result);
                    }
                    
                    $txTimes = Db::name('upmark')->where(array('type'=>'下分','user_id'=>getUid(), 'time'=>array('gt', date('Y-m-d').' 00:00:00')))->count();
                    if ($txTimes >= $site['tx_max_times']) {
                        $result['msg'] = $site['tx_text_chaoxian_cishu'];
                        return json($result);
                    }
                    
                    $tx_rate_limit = $site['tx_rate_limit'];
                    
                    if ($tx_rate_limit > 0) {
                        $upmark = Db::name('upmark')->where(array('type'=>'上分','user_id'=>getUid(), 'status'=>1))->sum('money');
                        $markLog = Db::name('order')->where(array('user_id'=>getUid(), 'status'=>array('neq','未开奖')))->sum('money');
                        
                        if ($upmark > 0) {
                            $rate = round($markLog*100/$upmark, 2);
                            
                            if ($rate < $site['tx_rate_limit']) {
                                $result['msg'] = '流水/充值比例不达标，需达到'.$site['tx_rate_limit'].'以上!';
                                return json($result);
                            }
                            
                        } else {
                            $result['msg'] = '账户余额不足!';
                            return json($result);
                        }
                        
                    }
                    
                    
                    $rest = true;
                    if (get_user_byid(getUid(), 'usdt') < $param['money']) {
                        $result['msg'] = 'runningLow';
                        return json($result);
                    }

                    
                    $paylist = array('bank_card'=>'银行卡', 'wx'=>'微信', 'alipay'=>'支付宝', 'coin'=>'USDT');
                    //$bank = explode('-', $param['content']);
                    //$pay['pay_type'] = $param['pay_type'];
                    $savePay = json_decode(get_user_byid(getUid(), 'attach_text'), true);
                    $saveInfo = $savePay['coin'];
                    $save = Config::get('site.save_bank_info');
                    if ($saveInfo['account'] && $save) {
                        $pay['pay_type'] = 'coin';
                        $pay['account'] = $saveInfo['account'];
                        $pay['bank_name'] = $saveInfo['bank_name'];
                    } else {
                        $pay['pay_type'] = 'coin';
                        $pay['account'] = $param['address'];
                        $pay['bank_name'] = 'USDT';
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
                    $downmark['pay_type'] = $paylist[$pay['pay_type']];
                    $downmark['time'] = date('Y-m-d H:i:s');
                    $downmark['balance_before'] = get_user_byid($downmark['user_id'], 'usdt');
                    $downmark['balance_later'] = $downmark['balance_before'] - $param['money'];
                    $downmark['ip'] = get_client_ip();
                    $downmark['resource'] = getIpInfo($downmark['ip']);
                    $downmark['rtime'] = time();
                    
                    $mark['user_id'] = $downmark['user_id'];
                    $mark['room_id'] = $downmark['room_id'];
                    $mark['type'] = '下分';
                    $mark['money'] = $downmark['money'];
                    $mark['balance_before'] =$downmark['balance_before'];
                    $mark['balance_later'] = $downmark['balance_later'];
                    $mark['real'] = getUser('real');
                    $mark['content'] = $downmark['pay_type'].'下分数量';
                    
                   
                    if ($save)
                        setAttach($pay);
                        
                    addMark($mark);
                    setBalnce($downmark, 0, 'usdt');
                    
                    if ($rest) {
                        
                        $res = Db::name('upmark')->insert($downmark);
                        if ($res) {
                            $result['status'] = 1;
                            $result['msg'] = '下分提交成功';
                        }
                        
                    }
                        
                        
                        break;
                case 'coin_upmark':
                    $site = Config::get('site');
                    
                    
                    if (getUser('real') == 1) {
                        
                        $notUpmark = Db::name('upmark')->where(array('type'=>'上分','user_id'=>getUid(), 'status'=>0))->count();
                        
                        if ($notUpmark > 0) {
                            $result['msg'] = '存在未审核上分记录';
                            return json($result);
                        }
                        
                        $paylist = array('wxpay'=>'微信支付', 'alipay'=>'支付宝支付', 'bank_card'=>'银行卡支付', 'coin'=>'USDT');
                        $upmark = array();
                        $upmark['username'] = getUser('username');
                        $upmark['user_id'] = getUid();
                        $upmark['type'] = '上分';
                        $upmark['address_type'] = $param['address_type'].'-'.$site['usdt_address'][$param['address_type']];
                        
                        $upmark['agent'] = getUser('agent');
                        $upmark['room_id'] = getUroomId();
                        $upmark['money'] = $param['money'];
                        //$bank_user_name = Db::name('bank')->where(array('user_id'=>getUid(), 'status'=>1))->value('name');
                        $upmark['pay_info'] = $this->_user['remark'];
                        $upmark['pay_type'] = $paylist[$param['pay_type']];
                        $upmark['pay_way'] = 'usdt';
                        $upmark['time'] = date('Y-m-d H:i:s');
                        $upmark['ip'] = get_client_ip();
                        $upmark['resource'] = getIpInfo($upmark['ip']);
                        
                        $upmark['rtime'] = time();
                        $res = Db::name('upmark')->insert($upmark);
                        if ($res) {
                            $result['status'] = 1;
                            $result['msg'] = '操作成功';
                        }
                    }
                    
                    break;
                case 'order_cancel':
                    if ($param['oid']) {
                        $oid = $param['oid'];
                        if (!in_array('forbid_cancel', Config::get('site.chat_setting'))) {

                            $order = getOrderBy(array('id'=>$oid));
                            if ($order['order_status'] == 1 && $order['status'] == '未开奖') {
                                $config = getGameSet($order['type'], $order['vip']);
                                $term = Db::name('open')->where(array('game_code'=>$order['type'], 'term'=>$order['term'] - 1))->find();
                                if (strtotime($term['next_time']) > time() + $config['game_close_time']) {
                                    
                                    if ($order) {
                                        $mark['user_id'] = $order['user_id'];
                                        $mark['room_id'] = $order['room_id'];
                                        $mark['type'] = '上分';
                                        $mark['money'] = $order['money'];
                                        $mark['balance_before'] = get_user_byid($order['user_id'], 'money');
                                        $mark['balance_later'] = $mark['balance_before'] + $order['money'];
                                        $mark['real'] = 1;
                                        $mark['content'] = '订单编号为:'.$order['id'].'撤单返款';
                                        addMark($mark);
                                        setBalnce($order, 1);
                                        Db::name('order')->where(array('order_status'=>1, 'id'=>$order['id']))->update(array('status'=>'已退还', 'order_status'=>2, 'order_back'=>$order['money'], 'order_back_time'=>date('Y-m-d H:i:s')));
                                        $result['status'] = 1;
                                        $result['msg'] = '撤单成功';
                                    }
                                    
                                } else {
                                    $result['msg'] = '该订单已封盘,禁止撤单';
                                }
                                
                            } else {
                                $result['msg'] = '撤销失败,该订单已撤销，不能重复撤销！';
                            }
                                                    
                        }  else {
                            $result['msg'] = '系统禁止撤单';
                        }
                    } else {
                        $result['msg'] = '撤销失败';
                    }
                    break;
                    
                case 'kefu':
                    if ($param['type'] == 'first') {
                        $map['user_id|to_id'] = getUid();
                        $message = Db::name('message')->where($map)->order('id desc')->select();
                        $result = $message;
                    } elseif ($param['type'] == 'update') {
                        $map['user_id|to_id'] = getUid();
                        $map['id'] = array('gt', $param['id']);
                        $message = Db::name('message')->where($map)->select();
                        $result = $message;
                    } elseif ($param['type'] == 'send') {
                        $content = $param['content'];
                        $message['type'] = 'U2';
                        $message['user_id'] = getUid();
                        $message['to_id'] = 'system';
                        $message['username'] = getUser('username');
                        $message['headimg'] = getUser('user_avatar');
                        $message['content'] = $content;
                        $message['addtime'] = date('Y-m-d H:i:s');
                        $message['view_status'] = 0;
                        $message['rtime'] = time();
                        Db::name('message')->insert($message);
                        
                        $result['success'] = 1;
                        
                    }
                    
                    break;
                 case 'account':
                     $user = getUserByCondition(array('account'=>$param['account']));
                     if (!$user || $user['id'] == getUid()) {
                         $save = array();
                         $save['account'] = $param['account'];
                         $save['passwd'] = fh_encoding($param['passwd']);
                         $save['rtime'] = time();
                         $res = Db::name('user')->where(array('id'=>getUid()))->update($save);
                         $result['status'] = 1;
                         if ($user) {
                             $result['msg'] = '账号更新成功';
                             $result['url'] = url('user/index');
                         } else {
                             $result['msg'] = '账号绑定成功';
                             $result['url'] = url('index/index');
                         }
                     } else {
                         $result['msg'] = '该用户名已存在';
                     }
                     break;
                 case 'mpwd':
                     $user = getUserByCondition(array('id'=>getUid()));
                     if ($user && $user['mpasswd'] == '') {
                         $save = array();
                         $save['mpasswd'] = fh_encoding($param['mpasswd']);
                         $save['rtime'] = time();
                         $res = Db::name('user')->where(array('id'=>getUid()))->update($save);
                         $result['status'] = 1;
                         if ($res) {
                             $result['msg'] = '资金密码设置成功';
                             $result['url'] = url('user/index');
                         } else {
                             $result['msg'] = '资金密码设置成功';
                             $result['url'] = url('index/index');
                         }
                     } else {
                         $result['msg'] = '非法操作';
                     }
                     break;
                  case 'cpwd':
                         $user = getUserByCondition(array('id'=>getUid()));
                         if ($user) {
                             if ($user['passwd'] == fh_encoding($param['opass'])) {
                                 $save = array();
                                 $save['passwd'] = fh_encoding($param['pass']);
                                 $save['rtime'] = time();
                                 $res = Db::name('user')->where(array('id'=>getUid()))->update($save);
                                 $result['status'] = 1;
                                 if ($res) {
                                     $result['msg'] = '账号密码设置成功';
                                     $result['url'] = url('user/user');
                                 } else {
                                     $result['msg'] = '账号密码设置成功';
                                     $result['url'] = url('index/index');
                                 }
                             } else {
                                 $result['msg'] = '原密码不正确';
                             }
                             
                         } else {
                             $result['msg'] = '非法操作';
                         }
                         break;
                   case 'cmpwd':
                             $user = getUserByCondition(array('id'=>getUid()));
                             if ($user && $user['mpasswd']) {
                                 if ($user['mpasswd'] == fh_encoding($param['opass'])) {
                                     $save = array();
                                     $save['mpasswd'] = fh_encoding($param['pass']);
                                     $save['rtime'] = time();
                                     $res = Db::name('user')->where(array('id'=>getUid()))->update($save);
                                     $result['status'] = 1;
                                     if ($res) {
                                         $result['msg'] = '资金密码设置成功';
                                         $result['url'] = url('user/user');
                                     } else {
                                         $result['msg'] = '资金密码设置成功';
                                         $result['url'] = url('index/index');
                                     }
                                 } else {
                                     $result['msg'] = '原密码不正确';
                                 }
                                  
                             } else {
                                 $result['msg'] = '非法操作';
                             }
                             break;
                 case 'bank':
                     $info['user_id'] = getUid();
                     $info['type'] = $param['type'];
                     $info['name'] = $param['real_name'];
                     $info['account'] = $param['account'];
                     $info['bank_name'] = $param['bank_name'];
                     $info['bank_place'] = $param['bank_place'];
                     $info['rtime'] = date('Y-m-d H:i:s');
                     
                     if (Db::name('bank')->where(array('user_id'=>getUid()))->count() == 1) {
                         $result['msg'] = '提现账户最多设置1个，如有需要请联系管理员!';
                     } else {
                         if (Db::name('bank')->insert($info)) {
                             Db::name('user')->where(array('id'=>getUid()))->update(array('mpasswd'=>fh_encoding($param['mpasswd']), 'rtime'=>time() ));
                             $result['status'] = 1; 
                             $result['msg'] = '操作成功';
                         }
                     }
                     
                     break;
                 case 'bank_del':
                     if (Db::name('bank')->where(array('id'=>$param['bankid']))->delete()) {
                         $result['status'] = 1;
                         $result['msg'] = '操作成功';
                     }
                     break;
                 case 'exchange':
                     $amount = $param['num'];
                     $type_a = $param['l_currency_id'];
                     $type_b = $param['r_currency_id'];
                     if ($type_b == 'cny') {
                         if ($amount <= $this->_user['usdt']) {
                             
                             $exchange = array();
                             $exchange['user_id'] = getUid();
                             $exchange['type_a'] = $type_a;
                             $exchange['type_b'] = $type_b;
                             $exchange['amount'] = $param['num'];
                             $exchange['rate'] = get_config_value('usdt_cny_rate');
                             $exchange['money'] = round($exchange['amount']*$exchange['rate'], 2);
                             
                             $exchange['usdt_before'] = get_user_byid(getUid(), 'usdt');
                             $exchange['usdt_later'] = $exchange['usdt_before'] - $exchange['amount'];
                             
                             $exchange['balance_before'] = get_user_byid(getUid(), 'money');
                             $exchange['balance_later'] = $exchange['balance_before'] + $exchange['money'];
                             $exchange['add_time'] = date('Y-m-d H:i:s');
                             
                             $mark['user_id'] = getUid();
                             $mark['room_id'] = 1;
                             $mark['type'] = '上分';
                             $mark['money'] = $exchange['money'];
                             $mark['balance_before'] = $exchange['balance_before'];
                             $mark['balance_later'] = $exchange['balance_later'];
                             $mark['real'] = 1;
                             $mark['content'] = 'USDT兑换CNY,兑换数量:'.$exchange['amount'];
                             
                             
                             Db::name('exchange')->insert($exchange);
                             
                             addMark($mark);
                             
                             setBalnce(array('user_id'=>getUid(), 'money'=>$exchange['amount']), 0, 'usdt');
                             setBalnce(array('user_id'=>getUid(), 'money'=>$exchange['money']), 1, 'money');
                             
                             $result['status'] = 1;
                             $result['msg'] = '操作成功';
                             
                         } else {
                             $result['msg'] = 'runningLow';
                         }
                     } else {
                         if ($amount <= $this->_user['money']) {
                             
                             $exchange = array();
                             $exchange['user_id'] = getUid();
                             $exchange['type_a'] = $type_a;
                             $exchange['type_b'] = $type_b;
                             $exchange['amount'] = $param['num'];
                             $exchange['rate'] = 1/get_config_value('usdt_cny_rate');
                             $exchange['money'] = round($exchange['amount']*$exchange['rate'], 2);
                             $exchange['usdt_before'] = get_user_byid(getUid(), 'usdt');
                             $exchange['usdt_later'] = $exchange['usdt_before'] + $exchange['money'];
                             
                             $exchange['balance_before'] = get_user_byid(getUid(), 'money');
                             $exchange['balance_later'] = $exchange['balance_before'] - $exchange['amount'];
                             $exchange['add_time'] = date('Y-m-d H:i:s');
                             
                             $mark['user_id'] = getUid();
                             $mark['room_id'] = 1;
                             $mark['type'] = '下分';
                             $mark['money'] = $exchange['amount'];
                             $mark['balance_before'] = get_user_byid(getUid(), 'money');;
                             $mark['balance_later'] = $mark['balance_before'] - $exchange['amount'];
                             $mark['real'] = 1;
                             $mark['content'] = 'CNY兑换USDT,兑换金额:'.$exchange['amount'];
                             
                             
                             Db::name('exchange')->insert($exchange);
                             
                             addMark($mark);
                             
                             setBalnce(array('user_id'=>getUid(), 'money'=>$exchange['money']), 1, 'usdt');
                             setBalnce(array('user_id'=>getUid(), 'money'=>$mark['money']), 0, 'money');
                             
                             $result['status'] = 1;
                             $result['msg'] = '操作成功';
                             
                         } else {
                             $result['msg'] = 'runningLow';
                         }
                     }
                     
                     break;
            }
            return json($result);
        }
    }
    
    
    /**
     * 上传文件
     */
    public function upload()
    {
        
        $file = $this->request->file('file');
        if (empty($file)) {
            $this->error(__('No file upload or server upload limit exceeded'));
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
                $this->error(__('Uploaded file format is limited'));
            }
            $replaceArr = [
                '{year}'     => date("Y"),
                '{mon}'      => date("m"),
                '{day}'      => date("d"),
                '{hour}'     => date("H"),
                '{min}'      => date("i"),
                '{sec}'      => date("s"),
                '{random}'   => Random::alnum(16),
                '{random32}' => Random::alnum(32),
                '{filename}' => $suffix ? substr($fileInfo['name'], 0, strripos($fileInfo['name'], '.')) : $fileInfo['name'],
                '{suffix}'   => $suffix,
                '{.suffix}'  => $suffix ? '.' . $suffix : '',
                '{filemd5}'  => md5_file($fileInfo['tmp_name']),
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
                $params = array(
                    'admin_id'    => 0,
                    'user_id'     => getUid(),
                    'filesize'    => $fileInfo['size'],
                    'imagewidth'  => $imagewidth,
                    'imageheight' => $imageheight,
                    'imagetype'   => $suffix,
                    'imageframes' => 0,
                    'mimetype'    => $fileInfo['type'],
                    'url'         => $uploadDir . $splInfo->getSaveName(),
                    'uploadtime'  => time(),
                    'storage'     => 'local',
                    'sha1'        => $sha1,
                );
                $attachment = model("attachment");
                $attachment->data(array_filter($params));
                $attachment->save();
                
                $content = $uploadDir . $splInfo->getSaveName();
                $message['type'] = 'U2';
                $message['user_id'] = getUid();
                $message['to_id'] = 'system';
                $message['username'] = getUser('username');
                $message['headimg'] = getUser('user_avatar');
                $message['content'] = $content;
                $message['addtime'] = date('Y-m-d H:i:s');
                $message['ctype'] = 1;
                $message['view_status'] = 0;
                
                Db::name('message')->insert($message);
                
                return json(array('code'=> 0,'data'=>$message));
                
            } else {
                // 上传失败获取错误信息
                $this->error($file->getError());
            }
            
    }
    
    /**
     * 上传文件
     */
    public function uploadimg()
    {
        
        $file = $this->request->file('file');
        if (empty($file)) {
            $this->error(__('No file upload or server upload limit exceeded'));
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
                $this->error(__('Uploaded file format is limited'));
            }
            $replaceArr = [
                '{year}'     => date("Y"),
                '{mon}'      => date("m"),
                '{day}'      => date("d"),
                '{hour}'     => date("H"),
                '{min}'      => date("i"),
                '{sec}'      => date("s"),
                '{random}'   => Random::alnum(16),
                '{random32}' => Random::alnum(32),
                '{filename}' => $suffix ? substr($fileInfo['name'], 0, strripos($fileInfo['name'], '.')) : $fileInfo['name'],
                '{suffix}'   => $suffix,
                '{.suffix}'  => $suffix ? '.' . $suffix : '',
                '{filemd5}'  => time().mt_rand(10000,99999),
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
                $message['content'] = $content;
                $message['addtime'] = date('Y-m-d H:i:s');
                $message['ctype'] = 1;
                $message['view_status'] = 0;
                
                // Db::name('message')->insert($message);
                
                return json(array('code'=> 0,'data'=>$message));
                
            } else {
                // 上传失败获取错误信息
                $this->error($file->getError());
            }
            
    }
    
}