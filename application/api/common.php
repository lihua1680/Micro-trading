<?php
use think\Db;
use think\Session;
use think\Config;
/**
 * 判断是否在微信浏览器打开
 * @return boolean
 */
function is_weixin()
{
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
        return true;
    } else {
        return false;
    }
}

/**
 * 获取房间号
 * @return string
 */
function getUroomId() {
    //return Session::get('user.room_id');
    return 1;
}

function getSubstr($str, $leftStr, $rightStr){
    $left = strpos($str, $leftStr);
    $right = strpos($str, $rightStr, $left);
    if($left < 0 or $right < $left)return '';
    return substr($str, $left + strlen($leftStr), $right - $left - strlen($leftStr));
}

/**
 * 获取用户信息
 * @param unknown $userid
 * @param string $field
 * @return unknown
 */
function getUserById($userid, $field = '') {
    $user = Db::name('user')->where(array('id'=>$userid))->find();
    if ($field && isset($user[$field]))
        return $user[$field];
    return $user;
}

/**
 * 获取站点配置
 * @param unknown $key
 * @return unknown
 */
function getSiteConfig($key) {
    return Config::get('site.'.$key);
}

/**
 * 获取用户信息
 * @param unknown $userid
 * @param string $field
 * @return unknown
 */
function getUserByCondition($condition, $field = '') {
    $user = Db::name('user')->where($condition)->find();
    if ($field)
        return isset($user[$field]) ? $user[$field] : '';
    return $user;
}

/**
 * 获取用户ID
 * @return unknown
 */
function getUid() {
    return Session::get('user.id');
}

/**
 * 获取用户信息
 * @return unknown
 */
function getUser($field = '') {
    $user =  Session::get('user');
    if ($field)
        return $user[$field];
    return $user;
}

/**
 * 获取房间号
 * @return unknown
 */
function getRid() {
    return Session::get('user.room_id');
}

/**
 * 设置提现数据
 * @param unknown $param
 */
function setAttach($param) {
    $attach_text = get_user_byid(getUid(), 'attach_text');
    $type = $param['pay_type'];
    unset($param['pay_type']);
    if ($attach_text) {
       $attach = json_decode($attach_text, true);
       $attach[$type] = $param;
    } else {
      $attach[$type] = $param;
    }
    
    Db::name('user')->where(array('id'=>getUid()))->update(array('attach_text'=>json_encode($attach)));
}

/**
 * 更新session
 * @param unknown $type
 */
function updateSession($type) {
    switch ($type) {
        case 'user':
            Session::set('user', getUserById(Session::get('user.id')));
    }
    return ;
}

/**
 * 
 * @param unknown $game
 */
function getGameSet($game, $vip = 'base') {
    $data = Db::name('game_config')->where(array('game_type'=>$game, 'room_id'=>getUroomId()))->find();
    if ($data) {
        $data[$vip.'_setting'] = json_decode($data[$vip.'_setting'], true);
    }
    return $data;
}

/**
 * 获取最新一期数据
 * @param string $game
 * @return array
 */
function getLastTerm($game) {
    $data = Db::name('open')->where(array('game_code'=>$game, 'status'=>1))->order('id desc')->find();
    return $data;
}


/**
 * 添加订单
 * @param unknown $param
 */
function addOrder($param){
    
    $param['user_id'] = getUid();
    $param['username'] = getUser('username');
    $param['headimg'] = getUser('user_avatar');
    $param['agent'] = getUser('agent');
    $param['type'] = $param['type'];
    $param['vip'] = $param['vip'];
    $param['term'] = $param['term'];
    $param['mingci'] = $param['mingci'];
    $param['content'] = $param['content'];
    $param['money'] = $param['money'];
    $param['status'] = '未开奖';
    $param['real'] = get_user_byid($param['user_id'], 'real');
    $param['room_id'] = getUser('room_id');
    $param['bettime'] = date('Y-m-d H:i:s');
    
    
    $mark['user_id'] = $param['user_id'];
    $mark['room_id'] = $param['room_id'];
    $mark['type'] = '下分';
    $mark['money'] = $param['money'];
    $mark['balance_before'] = get_user_byid($param['user_id'], 'money');
    $mark['balance_later'] = $mark['balance_before'] - $param['money'];
    $mark['real'] = $param['real'];
    $mark['content'] = common . phpget_game_name($param['type']) . $param['term'] .'期下注';

    addMark($mark);//写入流水
    
    setBalnce($param, 0);//下分
    
    return Db::name('order')->insert($param);//订单写入
}



/**
 * 获取游戏状态
 * @param unknown $config
 * @return array
 */
function getGameOpen($config) {
    $term = getLastTerm($config['game_code']);
    if (strtotime($term['next_time']) > time() + $config['game_close_time']) {
        return $term;
    }
    return false;
}



/**
 * 
 */
function getVipName($vip) {
    $vipList = array(
       'vip1' => Config::get('site.room_vip1'),
       'vip2' => Config::get('site.room_vip2'),  
       'vip3' => Config::get('site.room_vip3'),
       'vip4' => Config::get('site.room_vip4'), 
    );
    return $vipList[$vip];
}

/**
 * 获取今日用户数据
 * @return number[]|unknown[]
 */
function getUserToday() {
    
    $jrls = Db::name('order')->where(array('user_id'=>getUid(),'order_status'=>1, 'status'=>array('neq', '未开奖'), 'bettime'=> array('gt', date('Y-m-d').' 00:00:00')))->sum('money');
    
    $zj = Db::name('order')->where(array('user_id'=>getUid(), 'status'=>array('eq', '中奖'), 'bettime'=> array('gt', date('Y-m-d').' 00:00:00')))->sum('award');
    
    $jryk = $zj - $jrls;
    
    return array('jrls'=>$jrls, 'zj'=>$zj, 'jryk'=>$jryk);
}



/**
 * 获取用户账户明细
 * @param unknown $userid
 * @param string $time
 * @return unknown[]
 */
function getUserProfit($userid, $time = '') {
    
    $condition = array('user_id'=>$userid, 'status'=>array('neq', '已退还'));
    if ($time)
        $condition['bettime'] = array('between', $time);
    $mark = Db::name('order')->where($condition)->sum('money');
    $condition['status'] = array('eq', '中奖');
    $award = Db::name('order')->where($condition)->sum('money');
    return array('mark'=>$mark, 'award'=>$award, 'profit'=> round($award-$mark, 2));
}


function getQrstr($str, $leftStr, $rightStr)
{
    $left = strpos($str, $leftStr);
    $right = strpos($str, $rightStr,$left);
    if($left < 0 or $right < $left) return '';
    return substr($str, $left + strlen($leftStr), $right-$left-strlen($leftStr));
}


function get_not_balance($uid){
    return Db::name('order')->where(array('user_id'=>$uid, 'status'=>'未开奖'))->sum('money');
}


function get_user_profit($uid){
    $bet_money = Db::name('order')->where(array('user_id'=>$uid, 'status'=>array('neq', '未开奖'), 'bettime'=>array('gt', date('Y-m-d').' 00:00:00')))->sum('money');
    $bet_award = Db::name('order')->where(array('user_id'=>$uid, 'status'=>array('neq', '未开奖'), 'bettime'=>array('gt', date('Y-m-d').' 00:00:00')))->sum('award');
    $bet_money = $bet_money ? $bet_money : 0;
    $bet_award = $bet_award ? $bet_award : 0;
    return $bet_award - $bet_money;
}

function getOpenqqConfig () {
    $config = array(
        'appid' => Config::get('site.qq_appid'),
        'appkey' => Config::get('site.qq_appkey'),
        'callback' => Config::get('site.qq_domain').'/index/third/callback',
        'scope' => 'get_user_info'

    );
    $config = json_encode($config);
    $config = json_decode($config);
    return $config;
}
