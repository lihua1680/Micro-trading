<?php

namespace app\api\controller;

use think\Cache;
use think\Config;
use think\Db;

class Index extends PublicBase
{

    public function index()
    {
        $hui_lv = cache('day_hui_lv');
        if (empty($hui_lv)){
            $hui_lv = '7.24';
        }

        $site = Config::get('site');
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
                $is_b = 1;
            }else{
                $is_b = 0;
            }
        }else{
            $startTime = '';
            $endTime = '';
            $is_b = 0;
        }

        $notice = Db::name('notice')->order('id desc')->find();
        $user = Db::name('user')->where('id',getUid())->find();
        $name = "游客";
        $id= 0;
        if (!empty($user)) {
            $name = $user["real_name"];
            $id = $user["id"];
        }
$info = "账号：" . $user["account"]. ",姓名:" . $name . ",可用余额:" . $user["money"];
        $bannerList=Db::name('category')->select();
        
        
        $data = [
            'kefu_url' => Config::get('site.kefu_url') . "?name=$info&id=$id",
//            'web_icon' => Config::get('site.web_icon'),
            'gg_title' => $notice['title']?:'',
            'gg_con' => $notice['short_content']?:'',
            'gg_time' => $notice['ctime']?:'',
            'hui_lv' => $hui_lv,
            'b_start_time' => $startTimeStr,
            'b_end_time' => $endTimeStr,
            'b_is' => $is_b,
            'bannerList'=>$bannerList

        ];
        return ApiSuccess('',$data);

    }

    public function recconfig(){
        $site = Config::get('site');
        $data=[
            'min_chongzhi'=>$site['min_chongzhi'],
            'max_chongzhi'=>$site['max_chongzhi'],
            'web_bank_name'=>$site['web_bank_name'],
            'web_bank_place'=>$site['web_bank_place'],
            'web_bank_user'=>$site['web_bank_user'],
            'web_bank_number'=>$site['web_bank_number'],
            'web_bank_tips'=>$site['web_bank_tips'],
            'bank_status'=>$site['bank_status'],
            'usdt_status'=>$site['usdt_status'],
            'usdt_address'=>$site['usdt_address'],
            'usdt_cny_rate'=>$site['usdt_cny_rate'],
            ];
            
    return ApiSuccess('',$data);
        
    }

    /**
     * 常用银行名称列表（收款账户快捷选择）
     */
    public function bank_list()
    {
        return ApiSuccess('', get_bank_list());
    }



    // 单个商品的走势数据
    public function goods_stock()
    {
        $param = $this->request->param();

//        1min : 1分钟
//        5min : 5分钟
//        15min : 15分钟
//        30min : 30分钟
//        1hour : 1小时
//        1day : 1天
//        1week : 1星期

        $stocks = array();
        if (empty($param['symbol']) || empty($param['resolution'])) {
            return ApiSuccess('', $stocks);
        }

        $resolution = $param['resolution'];
        if ($resolution == '1D') {
            $resolution = '1day';
        }
        if ($resolution == '1W') {
            $resolution = '1week';
        }
        if ($resolution == '60min' || $resolution == '60') {
            $resolution = '1hour';
        }
        if ($resolution == '15') {
            $resolution = '15min';
        }
        if ($resolution == '1') {
            $resolution = '1min';
        }

        $key = $param['symbol'].'_stock_'.$resolution;
        $cacheData = Cache::get($key);
        if (empty($cacheData)) {
            return ApiSuccess('', $stocks);
        }
        $decoded = @unserialize($cacheData);
        if (empty($decoded) || !is_array($decoded)) {
            return ApiSuccess('', $stocks);
        }
        $stocks = array_values($decoded);

        return ApiSuccess('',$stocks);
    }

    // 关于我们
    public function about()
    {
        $content = Config::get('site.company_desc');
        $title = Config::get('site.web_name') ?: Config::get('site.name');
        return ApiSuccess('', [
            'title' => $title ?: '关于我们',
            'content' => $content ?: '',
        ]);
    }

    // 资讯列表
    public function news_list()
    {
        $page = max(1, intval($this->request->param('page', 1)));
        $limit = max(1, min(50, intval($this->request->param('limit', 20))));
        $query = Db::name('article')->order('id desc');
        $total = $query->count();
        $list = Db::name('article')
            ->field('id,title,summary,image,author,pubtime,cteate_time')
            ->order('id desc')
            ->page($page, $limit)
            ->select();
        return ApiSuccess('', [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'list' => $list ?: [],
        ]);
    }

    // 资讯详情
    public function news_detail()
    {
        $id = intval($this->request->param('id', 0));
        if ($id <= 0) {
            return ApiError('参数错误');
        }
        $article = Db::name('article')->where('id', $id)->find();
        if (empty($article)) {
            return ApiError('资讯不存在');
        }
        return ApiSuccess('', $article);
    }

 public function goods()
{
    $stocks = getStock(array('status'=>1, 'is_open'=>1));

    foreach ($stocks as $key=>&$stock) {
        $zf = 0;
        if (isset($stock['open_price']) && $stock['open_price'] > 0)
            $zf = round(($stock['price'] - $stock['open_price'])*100/$stock['open_price'], 2);
        $stock['vol'] = round($stock['vol']/10000, 4);
        $trade_type = Config::get('site.trade_type');
        if ($trade_type != 'qc' && $stock['cid'] == 1) {
            $stock['codename'] = $stock['code'].'/'.strtoupper($trade_type);
        } else {
            $stock['codename'] = $stock['code'];
        }

        $stock['zf'] = $zf;
        if (isset($stock['open_price']) && $stock['open_price'] > 0){
            $stock['zf_d'] = bcsub($stock['price'],$stock['open_price'],2);
        }else{
            $stock['zf_d'] = '0.00';
        }
        if ($zf > 0){
            $stock['is_z'] = 1;
        }else{
            $stock['is_z'] = 2;
        }
        $stock['zf'] .= '%';

        // 新增：返回备注和图片
        // $stock['remark'] = isset($stock['emark']) ? $stock['Remark'] : ''; // 如果字段不存在，默认返回空字符串
        // $stock['image'] = isset($stock['Image']) ? $stock['Image'] : '';     // 如果字段不存在，默认返回空字符串
    }
    return ApiSuccess('',$stocks);
}

    // 商品详情
    public function goods_dec()
    {
        $stocks = getStock(array('status'=>1, 'is_open'=>1,'id' => input('id')));

        // var_dump($stocks);

        foreach ($stocks as $key=>&$stock) {
            $zf = 0;
            if (isset($stock['open_price']) && $stock['open_price'] > 0)
                $zf = round(($stock['price'] - $stock['open_price'])*100/$stock['open_price'], 2);
            $stock['vol'] = round($stock['vol']/10000, 4);
            $trade_type = Config::get('site.trade_type');
            if ($trade_type != 'qc' && $stock['cid'] == 1) {
                $stock['codename'] = $stock['code'].'/'.strtoupper($trade_type);
            } else {
                $stock['codename'] = $stock['code'];
            }

            $stock['zf'] = $zf;
            if (isset($stock['open_price']) && $stock['open_price'] > 0){
                $stock['zf_d'] = bcsub($stock['price'],$stock['open_price'],2);

            }else{
                $stock['zf_d'] = '0.00';
            }
            if ($zf > 0){
                $stock['is_z'] = 1;
            }else{
                $stock['is_z'] = 2;
            }
            $stock['zf'] .= '%';
        }

        if (!empty($stocks[0])){
            $stocks = $stocks[0];
            $stock = getStockByCode($stocks['code']);
            $play_rule = json_decode($stock['play_rule'], true);
            $wm_time_control = empty($stock['wm_time_control'])?[]:json_decode($stock['wm_time_control'], true);
            // print_r($wm_time_control);die;
           
            $timeList = array();
            foreach ($play_rule as $key=>$val) {
                $time = $val['time'];
                $days = intval($time/(3600*24));
                $hours = intval(($time%(3600*24))/3600);
                $minite = intval(($time%3600)/60);
                $second = $time%60;
                $time_str = ($days > 0 ? $days.'天':'').($hours > 0 ? $hours.'时':'').($minite > 0 ? $minite.'分钟':'').($second > 0 ? $second.'秒':'');
                $time_str = $time.'秒';
                if ($val['win'] > 100){
                    $profit_ratio = $val['win'] - 100;
                }else{
                    $profit_ratio = $val['win'];
                }
                $timeList[] = array(
                    'seconds'=>$val['time'],
                    'seconds_desc'=>$time_str,
                    'profit_ratio'=>$profit_ratio,
                    'min'=>isset($val['min']) ? $val['min'] : '',
                    'max'=>isset($val['max']) ? $val['max'] : '',
                );
            }
            $wmtimeList=[];
            foreach ($wm_time_control as $key1=>$val1) {
                
                 $time = $val1['time'];
                $days = intval($time/(3600*24));
                $hours = intval(($time%(3600*24))/3600);
                $minite = intval(($time%3600)/60);
                $second = $time%60;
                $time_str = ($days > 0 ? $days.'天':'').($hours > 0 ? $hours.'时':'').($minite > 0 ? $minite.'分钟':'').($second > 0 ? $second.'秒':'');
                $time_str = $time.'秒';
                if ($val1['win'] > 100){
                    $profit_ratio = $val1['win'] - 100;
                }else{
                    $profit_ratio = $val1['win'];
                }
                $wmtimeList[] = array(
                    'seconds'=>$val1['time'],
                    'seconds_desc'=>$time_str,
                    'profit_ratio'=>$profit_ratio,
                    'min'=>isset($val1['min']) ? $val1['min'] : '',
                    'max'=>isset($val1['max']) ? $val1['max'] : '',
                );
            }
            
            
            
            $stocks['wmtimeList'] = $wmtimeList;
            $stocks['timeList'] = $timeList;
            $stocks['price'] = $stock['price'];
            $stocks['open_price'] = $stock['open_price'];
            $stocks['price_high'] = $stock['price_high'];
            $stocks['price_low'] = $stock['price_low'];
            $stocks['vol'] = $stock['vol'];
        }

        return ApiSuccess('',$stocks);

    }

}