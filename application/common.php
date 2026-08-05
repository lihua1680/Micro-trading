<?php
use think\Db;
use think\Session;
use think\Config;
use think\Request;
use fast\Arr;
// 公共助手函数

function dd($param) {
    echo "<pre>";
    print_r($param);
    echo "</pre>";
}


if (!function_exists('__')) {

    /**
     * 获取语言变量值
     * @param string $name 语言变量名
     * @param array  $vars 动态变量值
     * @param string $lang 语言
     * @return mixed
     */
    function __($name, $vars = [], $lang = '')
    {
        if (is_numeric($name) || !$name) {
            return $name;
        }
        if (!is_array($vars)) {
            $vars = func_get_args();
            array_shift($vars);
            $lang = '';
        }
        return \think\Lang::get($name, $vars, $lang);
    }
}
 
if (!function_exists('format_bytes')) {

    /**
     * 将字节转换为可读文本
     * @param int    $size      大小
     * @param string $delimiter 分隔符
     * @return string
     */
    function format_bytes($size, $delimiter = '')
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        for ($i = 0; $size >= 1024 && $i < 6; $i++) {
            $size /= 1024;
        }
        return round($size, 2) . $delimiter . $units[$i];
    }
}

if (!function_exists('datetime')) {

    /**
     * 将时间戳转换为日期时间
     * @param int    $time   时间戳
     * @param string $format 日期时间格式
     * @return string
     */
    function datetime($time, $format = 'Y-m-d H:i:s')
    {
        $time = is_numeric($time) ? $time : strtotime($time);
        return date($format, $time);
    }
}

if (!function_exists('human_date')) {

    /**
     * 获取语义化时间
     * @param int $time  时间
     * @param int $local 本地时间
     * @return string
     */
    function human_date($time, $local = null)
    {
        return \fast\Date::human($time, $local);
    }
}

if (!function_exists('cdnurl')) {

    /**
     * 获取上传资源的CDN的地址
     * @param string  $url    资源相对地址
     * @param boolean $domain 是否显示域名 或者直接传入域名
     * @return string
     */
    function cdnurl($url, $domain = false)
    {
        $regex = "/^((?:[a-z]+:)?\/\/|data:image\/)(.*)/i";
        $url = preg_match($regex, $url) ? $url : \think\Config::get('upload.cdnurl') . $url;
        if ($domain && !preg_match($regex, $url)) {
            $domain = is_bool($domain) ? request()->domain() : $domain;
            $url = $domain . $url;
        }
        return $url;
    }
}


if (!function_exists('is_really_writable')) {

    /**
     * 判断文件或文件夹是否可写
     * @param    string $file 文件或目录
     * @return    bool
     */
    function is_really_writable($file)
    {
        if (DIRECTORY_SEPARATOR === '/') {
            return is_writable($file);
        }
        if (is_dir($file)) {
            $file = rtrim($file, '/') . '/' . md5(mt_rand());
            if (($fp = @fopen($file, 'ab')) === false) {
                return false;
            }
            fclose($fp);
            @chmod($file, 0777);
            @unlink($file);
            return true;
        } elseif (!is_file($file) or ($fp = @fopen($file, 'ab')) === false) {
            return false;
        }
        fclose($fp);
        return true;
    }
}

if (!function_exists('rmdirs')) {

    /**
     * 删除文件夹
     * @param string $dirname  目录
     * @param bool   $withself 是否删除自身
     * @return boolean
     */
    function rmdirs($dirname, $withself = true)
    {
        if (!is_dir($dirname)) {
            return false;
        }
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirname, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        if ($withself) {
            @rmdir($dirname);
        }
        return true;
    }
}

if (!function_exists('copydirs')) {

    /**
     * 复制文件夹
     * @param string $source 源文件夹
     * @param string $dest   目标文件夹
     */
    function copydirs($source, $dest)
    {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }
        foreach (
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            ) as $item
        ) {
            if ($item->isDir()) {
                $sontDir = $dest . DS . $iterator->getSubPathName();
                if (!is_dir($sontDir)) {
                    mkdir($sontDir, 0755, true);
                }
            } else {
                copy($item, $dest . DS . $iterator->getSubPathName());
            }
        }
    }
}

if (!function_exists('mb_ucfirst')) {
    function mb_ucfirst($string)
    {
        return mb_strtoupper(mb_substr($string, 0, 1)) . mb_strtolower(mb_substr($string, 1));
    }
}

if (!function_exists('addtion')) {

    /**
     * 附加关联字段数据
     * @param array $items  数据列表
     * @param mixed $fields 渲染的来源字段
     * @return array
     */
    function addtion($items, $fields)
    {
        if (!$items || !$fields) {
            return $items;
        }
        $fieldsArr = [];
        if (!is_array($fields)) {
            $arr = explode(',', $fields);
            foreach ($arr as $k => $v) {
                $fieldsArr[$v] = ['field' => $v];
            }
        } else {
            foreach ($fields as $k => $v) {
                if (is_array($v)) {
                    $v['field'] = isset($v['field']) ? $v['field'] : $k;
                } else {
                    $v = ['field' => $v];
                }
                $fieldsArr[$v['field']] = $v;
            }
        }
        foreach ($fieldsArr as $k => &$v) {
            $v = is_array($v) ? $v : ['field' => $v];
            $v['display'] = isset($v['display']) ? $v['display'] : str_replace(['_ids', '_id'], ['_names', '_name'], $v['field']);
            $v['primary'] = isset($v['primary']) ? $v['primary'] : '';
            $v['column'] = isset($v['column']) ? $v['column'] : 'name';
            $v['model'] = isset($v['model']) ? $v['model'] : '';
            $v['table'] = isset($v['table']) ? $v['table'] : '';
            $v['name'] = isset($v['name']) ? $v['name'] : str_replace(['_ids', '_id'], '', $v['field']);
        }
        unset($v);
        $ids = [];
        $fields = array_keys($fieldsArr);
        foreach ($items as $k => $v) {
            foreach ($fields as $m => $n) {
                if (isset($v[$n])) {
                    $ids[$n] = array_merge(isset($ids[$n]) && is_array($ids[$n]) ? $ids[$n] : [], explode(',', $v[$n]));
                }
            }
        }
        $result = [];
        foreach ($fieldsArr as $k => $v) {
            if ($v['model']) {
                $model = new $v['model'];
            } else {
                $model = $v['name'] ? \think\Db::name($v['name']) : \think\Db::table($v['table']);
            }
            $primary = $v['primary'] ? $v['primary'] : $model->getPk();
            $result[$v['field']] = $model->where($primary, 'in', $ids[$v['field']])->column("{$primary},{$v['column']}");
        }

        foreach ($items as $k => &$v) {
            foreach ($fields as $m => $n) {
                if (isset($v[$n])) {
                    $curr = array_flip(explode(',', $v[$n]));

                    $v[$fieldsArr[$n]['display']] = implode(',', array_intersect_key($result[$n], $curr));
                }
            }
        }
        return $items;
    }
}

if (!function_exists('var_export_short')) {

    /**
     * 返回打印数组结构
     * @param string $var    数组
     * @param string $indent 缩进字符
     * @return string
     */
    function var_export_short($var, $indent = "")
    {
        switch (gettype($var)) {
            case "string":
                return '"' . addcslashes($var, "\\\$\"\r\n\t\v\f") . '"';
            case "array":
                $indexed = array_keys($var) === range(0, count($var) - 1);
                $r = [];
                foreach ($var as $key => $value) {
                    $r[] = "$indent    "
                        . ($indexed ? "" : var_export_short($key) . " => ")
                        . var_export_short($value, "$indent    ");
                }
                return "[\n" . implode(",\n", $r) . "\n" . $indent . "]";
            case "boolean":
                return $var ? "TRUE" : "FALSE";
            default:
                return var_export($var, true);
        }
    }
}

if (!function_exists('letter_avatar')) {
    /**
     * 首字母头像
     * @param $text
     * @return string
     */
    function letter_avatar($text)
    {
        $total = unpack('L', hash('adler32', $text, true))[1];
        $hue = $total % 360;
        list($r, $g, $b) = hsv2rgb($hue / 360, 0.3, 0.9);

        $bg = "rgb({$r},{$g},{$b})";
        $color = "#ffffff";
        $first = mb_strtoupper(mb_substr($text, 0, 1));
        $src = base64_encode('<svg xmlns="http://www.w3.org/2000/svg" version="1.1" height="100" width="100"><rect fill="' . $bg . '" x="0" y="0" width="100" height="100"></rect><text x="50" y="50" font-size="50" text-copy="fast" fill="' . $color . '" text-anchor="middle" text-rights="admin" alignment-baseline="central">' . $first . '</text></svg>');
        $value = 'data:image/svg+xml;base64,' . $src;
        return $value;
    }
}

if (!function_exists('hsv2rgb')) {
    function hsv2rgb($h, $s, $v)
    {
        $r = $g = $b = 0;

        $i = floor($h * 6);
        $f = $h * 6 - $i;
        $p = $v * (1 - $s);
        $q = $v * (1 - $f * $s);
        $t = $v * (1 - (1 - $f) * $s);

        switch ($i % 6) {
            case 0:
                $r = $v;
                $g = $t;
                $b = $p;
                break;
            case 1:
                $r = $q;
                $g = $v;
                $b = $p;
                break;
            case 2:
                $r = $p;
                $g = $v;
                $b = $t;
                break;
            case 3:
                $r = $p;
                $g = $q;
                $b = $v;
                break;
            case 4:
                $r = $t;
                $g = $p;
                $b = $v;
                break;
            case 5:
                $r = $v;
                $g = $p;
                $b = $q;
                break;
        }

        return [
            floor($r * 255),
            floor($g * 255),
            floor($b * 255)
        ];
    }
}

/**
 * 获取游戏名称
 * @param unknown $gamecode
 */
function get_game_name($gamecode) {
    //$gameList = getGameConfig();
    $gameInfo = Db::name('game_config')->where(array('game_type'=>$gamecode))->find();
    return $gameInfo['game_name'];
}


/**
 * 获取配置
 * @param unknown $field 
 */
function get_config_value($field) {
    return Db::name('config')->where(array('name'=>$field))->value('value');
}

/**
 * 判断域名
 */
function jumpDomain() {

    if ($_SERVER['HTTP_HOST'] == Config::get('site.wx_qrdomain1') || $_SERVER['HTTP_HOST'] == Config::get('site.wx_qrdomain2')) {
        $urlInfo = parse_url($_SERVER["REQUEST_URI"]);
        $domain = Db::name('domain')->where(array('using'=>1))->value('domain');
        if (!$domain)
            return false;
        $url = 'http://'.str_replace(array('http://', 'https://'), '', $domain).$_SERVER["REQUEST_URI"];
        header('Location: '.$url);
    }
    return false;
}


/**
 * 获取game列表
 * @param string $field
 * @return unknown|unknown[]
 */
function getGameConfig($field = false, $all = true) {
    $result = array();
    if (!$all)
        $data = Db::name('game')->where('game_status', 1)->field('game_code,game_name')->order('rank desc')->select();
        else {
            $data = Db::name('game')->field('game_code,game_name')->order('rank desc')->select();
        }
        if ($field)
            return $data;
    foreach ($data as $key=>$val) {
        $result[$val['game_code']] = $val['game_name'];
    }
    return $result;
}

/**
 * 添加账变记录
 * @param unknown $param
 */
function addMark($param){
    
    $param['addtime'] = date('Y-m-d H:i:s');
    return Db::name('mark')->insert($param);
}

/**
 * 获取会员信息
 * @param string $field
 */
function getUserLevel($field = '') {
    $level = Config::get('site.vip');
    if ($field)
        return $level[$field];
    return $level;
}


/**
 * 添加上下分记录
 * @param unknown $param
 */
function addUpmark($param){
    $param['time'] = date('Y-m-d H:i:s');
    return Db::name('upmark')->insert($param);
}


/**
 * 添加对话记录
 * @param unknown $param
 */
function addMessage($param){
    $param['addtime'] = date('Y-m-d H:i:s');
    return Db::name('message')->insert($param);
}

/**
 * 获取session数据
 * @param unknown $name
 * @param string $field
 * @return unknown
 */
function get_session($name, $field = '') {
    $data = Session::get($name);
    if ($field)
        return $data[$field];
    return $data;
}


function generateRandomString($length = 12) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }
    
    return $randomString;
}

function setOdds($str) {
    if ($str)
        return $str;
        return '--';
}

/**
 * 设置账户
 * @param array $param
 * @param number $position
 * @return boolean
 */
function setBalnce($param, $position = 0, $field = 'money'){

    if ($position)
        return Db::name('user')->where(array('id'=>$param['user_id']))->setInc($field, $param['money']);
    return Db::name('user')->where(array('id'=>$param['user_id']))->setDec($field, $param['money']);
}

/**
 * 冻结/取消冻结 资金
 * @param array $param
 * @param number $position   0 冻结资金，1取消冻结
 * @return boolean
 */
function setBalnceWithFreezeFunds($param, $position = 0){

    if ($position){
		return Db::name('user')->where(array('id'=>$param['user_id']))->inc('money', $param['money'])->dec('freeze_funds', $param['money'])->update();
	}else{
		return Db::name('user')->where(array('id'=>$param['user_id']))->dec('money', $param['money'])->inc('freeze_funds', $param['money'])->update();
	}
}

/**
 * 信誉分增加或减少
 * @param array $param
 * @param number $position   0 减少，1增加
 * @return boolean
 */
function setCreditScore($param, $position = 0){

    if ($position){
		return Db::name('user')
		->where(array('id'=>$param['user_id']))->inc('credit_score', $param['credit_score'])->update();
	}else{
		return Db::name('user')
		->where(array('id'=>$param['user_id']))->dec('credit_score', $param['credit_score'])->update();
	}
}
/**
 * 获取用户信息
 * @param unknown $userid
 * @return boolean|unknown
 */
function get_user_byid($userid, $field = '') {
    if (!$userid)
        return false;
    $info = Db::name('user')->where(array('id'=>$userid))->find();
    if ($field)
        return $info[$field];
    return $info;
}

/**
 * 获取订单信息
 * @param unknown $userid
 * @return boolean|unknown
 */
function getOrderBy($condition, $field = '') {
    if (!$condition)
        return false;
    $order = Db::name('order')->where($condition)->find();
    if ($field)
        return $order[$field];
    return $order;
}

/**
 * 获取数组分列
 * @param array $array
 * @param unknown $column_key
 * @param unknown $index_key
 * @return unknown[]
 */
function get_array_column(array $array, $column_key, $index_key=null){
    $result = [];
    foreach($array as $arr) {
        if(!is_array($arr)) continue;
        
        if(is_null($column_key)){
            $value = $arr;
        }else{
            $value = $arr[$column_key];
        }
        
        if(!is_null($index_key)){
            $key = $arr[$index_key];
            $result[$key] = $value;
        }else{
            $result[] = $value;
        }
    }
    return $result;
}

/**
 * 获取产品列表
 * @return array
 */
function getProductList($type = 1) {
    if ($type)
        return Db::name('product')->where(array('status'=>1))->column('id','title');
    else  return Db::name('product')->where(array('status'=>1))->select();  
}

/**
 * 获取房间名称
 * @param number $pid
 * @return string
 */
function getRoomName($pid) {
    return Db::name('room')->where(array('id'=>$pid))->value('room_name');
}


/**
 * 获取真人ID
 * @param string $ids
 */
function getRealIds($ids) {
    $userIds = array();
    foreach ($ids as $id) {
        if (getUserReal($id))
            $userIds[] = $id;
    }
    return $userIds;
}

/**
 * 获取客户端ip
 * @return mixed|NULL|string|number|unknown
 */
function get_client_ip() {
    $request = Request::instance();
    return $request->ip();
}

/**
 * 获取IP信息
 * @param unknown $ip
 */
function getIpInfo($ip) {
    $info = @file_get_contents('http://ip-api.com/json/'.$ip.'?lang=zh-CN');
    $ipInfo = json_decode($info, true);
    $area = $ipInfo['country'].'_'.$ipInfo['city'];
    return $area;
}
function dxb_sms_send($phone,$content){
    $username = config('dxb_sms.username');
    $password = config('dxb_sms.password');
    $url = "https://api.smsbao.com/sms?u={$username}&p={$password}&m={$phone}&c={$content}";
    $aa = http_request($url);
    return true;
}


function http_request($url, $data = null)
{
   
   $curl = curl_init();
   curl_setopt($curl, CURLOPT_URL, $url);
   curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
   curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
   if (!empty($data)) {
         curl_setopt($curl, CURLOPT_POST, 1);
         curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
     }
     curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
     // 在尝试连接时等待的秒数
     curl_setopt($curl, CURLOPT_CONNECTTIMEOUT , 5);
     // 最大执行时间
     curl_setopt($curl, CURLOPT_TIMEOUT, 5);
     $info = curl_exec($curl);
     curl_close($curl);
     return $info;
 }
 
 
 function stock_request($url, $method = 'GET', $headers = array()) {
 
     array_push($headers, "Authorization:APPCODE " . Config::get('site.api_stock_code'));
     $curl = curl_init();
     curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
     curl_setopt($curl, CURLOPT_URL, $url);
     curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
     curl_setopt($curl, CURLOPT_FAILONERROR, false);
     curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
     curl_setopt($curl, CURLOPT_HEADER, false);
     if (1 == strpos("$".$url, "https://"))
     {
         curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
         curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
     }
     $result = curl_exec($curl);

     $result = json_decode($result, true);
     return $result;
 }
 
/**
 * 获取用户类型
 * @param string $userid
 */
function getUserReal($userid) {
    return Db::name('user')->where(array('id'=>$userid))->column('real');
}


/**
 * 加密
 * @param string $str
 * @return string
 */
function fh_encoding($str, $key = "fhsoft-yule-system") {
    return substr ( md5 ( $str . $key ), 4, 28 );
}


/**
 * 获取产品信息
 * @param unknown $condition
 * @param unknown $field
 */
function getStock($condition, $field = 'id,code,title,price,open_price,cid,vol,remark,image', $order = 'rank desc') {
    return Db::name('product')->where($condition)->field($field)->order($order)->select();
}

/**
 * 获取产品
 * @param unknown $code
 * @param string $field
 * @return array|\think\db\false|PDOStatement|string|\think\Model
 */
function getStockByCode($code, $field = '') {
    $stock = Db::name('product')->where(array('code'=>$code))->find();
    if ($field && isset($stock[$field]))
        return $stock[$field];
    return $stock;
}

/**
 * 更新订单
 * @param unknown $order
 * @param number $money
 */
function updateOrder($order, $money = 0, $win, $oid) {
    
    $tableOrder = Db::name('order')->where(array('id'=>$oid))->find();
    if ($tableOrder["status"] == 1 && $win >= 0) {
        $mark['order_sn'] = $tableOrder["order_sn"];
        $mark['user_id'] = $order['user_id'];
        $mark['type'] = '上分';
        $mark['room_id'] = 0;
        $mark['money'] = $money;
        $mark['balance_before'] = get_user_byid($order['user_id'], 'money');
        $mark['balance_later'] = strval($mark['balance_before'] + $money);
        $mark['real'] = 1;
        // $mark['content'] = '平仓获得收益  +'.$money;
        addMark($mark);//写入流水
        setBalnce(array('user_id'=>$order['user_id'], 'money'=>$money), 1);//上分
        return Db::name('order')->where(array('id'=>$order['id']))->update($order);
    } 
}

//计算小数点后位数
function getFloatLength($num) {
    $count = 0;
    
    $temp = explode ( '.', $num );
    
    if (sizeof ( $temp ) > 1) {
        $decimal = end ( $temp );
        $count = strlen ( $decimal );
    }
    
    return $count;
}

/**
 * 获取房间号
 */
function getAroomid() {
    return 1;
}

/**
 * 打印参数
 */
function fh($param) {
    echo "<pre>";
    var_dump($param);die;
}

function is_empty($str, $suffix = '-') {
    if (!$str && !is_numeric($str))
        return $suffix;
    return $str;
}

/**
 * 自定义返回提示信息
 * @param  [type] $data [description]
 * @param  [type] $type [description]
 */
function ApiError($massage='失败！',$data=[],$url=null)
{
    $res = array('massage'=>$massage,'data'=> $data, 'status'=> -1);
    if($url){
        $res['url'] = $url;
    }
    return json_encode($res);
}

/**
 * 自定义返回提示信息
 * @param  [type] $data [description]
 * @param  [type] $type [description]
 */
function ApiSuccess($massage='成功！',$data=[],$url=null)
{
    $res = array('massage'=>$massage,'data'=> $data, 'status'=> 1);
    if($url){
        $res['url'] = $url;
    }
    return json_encode($res);
}


/**
 * 自定义返回提示信息
 * @param  [type] $data [description]
 * @param  [type] $type [description]
 */
function ApiReturn($massage,$status,$data=[],$url=null)
{
    $res = array('massage'=>$massage,'data'=> $data, 'status'=>$status);
    if($url){
        $res['url'] = $url;
    }
    return json_encode($res);
}

function get_host()
{
    if (request()->isSsl()) {
        return 'https://' . request()->host(true) . ':' . request()->server('SERVER_PORT');
    } else {
        return 'http://' . request()->host(true) . ':' . request()->server('SERVER_PORT');
    }

 }
