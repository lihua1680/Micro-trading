<?php

namespace app\index\controller;

use app\index\controller\Common;
use think\Request;
use think\Db;
use think\Config;
use fast\Random;
use think\Cache;

class Api extends Common
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
        
        $this->view->assign('user', $user);
    }
    
    
    
    public function ajax() {
        
        if ($this->request->isAjax()) {
            $param = $this->request->param();
            $result = array('status'=> 0);
            $request = $param['request'];

            switch ($request) {
                 case 'index_news':
                     $news_list = Db::name('news')->where(array('status'=>1))->select();
                     $result['status'] = 1;
                     $result['message'] = $news_list;
                     $result['msg'] = '操作成功';
                     break;
                 case 'getQuotation':
                     $result['status'] = 1;
                     $stocks = getStock(array('status'=>1, 'is_open'=>1));
                     
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
                     }
                     $result['data'] = $stocks;
                     break;
                 case 'getStockInfo':
                         $result['status'] = 1;
                         $stock = getStockByCode($param['type']);
                         $play_rule = json_decode($stock['play_rule'], true);
                         $timeList = array();
                         foreach ($play_rule as $key=>$val) {
                             $time = $val['time'];
                             $days = intval($time/(3600*24));
                             $hours = intval(($time%(3600*24))/3600);
                             $minite = intval(($time%3600)/60);
                             $second = $time%60;
                           
                             
                             $time_str = ($days > 0 ? $days.'天':'').($hours > 0 ? $hours.'时':'').($minite > 0 ? $minite.'分钟':'').($second > 0 ? $second.'秒':'');
                             
                             
                             $time_str = $time.'秒';
                             $timeList[] = array('seconds'=>$val['time'],'seconds_desc'=>$time_str, 'profit_ratio'=>$val['win']);
                         }
                         $result['data']['timeList'] = $timeList;
                         $result['data']['price'] = $stock['price'];
                         $result['data']['open_price'] = $stock['open_price'];
                         $result['data']['price_high'] = $stock['price_high'];
                         $result['data']['price_low'] = $stock['price_low'];
                         $result['data']['vol'] = $stock['vol'];
                         break;
                 case 'stock':
                     $result['status'] = 1;
                     $stocks = array();
                     $type = Config::get('site.api_type');
                     $trade_type = Config::get('site.trade_type');
                     $apiUrl = Config::get('site.api_url_'.$type);
                     if ($param['resolution'] == '1D')
                         $param['resolution'] = '1day';
                     if ($param['resolution'] == '1W')
                         $param['resolution'] = '1week';
                     if ($param['resolution'] == '60min')
                         $param['resolution'] = '1hour';
                     $key = $param['symbol'].'_stock_'.$param['resolution'];
                     $stocks = array_values(unserialize(Cache::get($key)));
                     //var_dump($key);
                     $result['data'] = $stocks;
                     break;
            }
            return json($result);
        }
    }
    
   
}