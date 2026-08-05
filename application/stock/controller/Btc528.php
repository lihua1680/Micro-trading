<?php
namespace app\stock\controller;

use think\Controller;
use think\Request;
use think\Db;
use think\Config;
use think\Session;
use think\Cache;

class Btc528 extends Controller
{

    
    public function _initialize() {

    }

    
    /**
     * 获取最新K线数据
     */
    public function runLastData() {
        $type = $this->request->param('type');
        
        $types = array('1min', '5min', '30min', '1hour', '1day');
        
        if (!in_array($type, $types)) {
            return ;
        }
   
        

        $stocks = Db::name('product')->where(array('status'=>1, 'is_open'=>1))->select();
        $stockList = array();
        foreach ($stocks as $key=>$stock) {
            if($stock['cid'] == 1){
                $code = $stock['code'];
                $this->getLastData($type,$code);
            }else{
                $stockList[] = $stock;
            }
            
        }
        
       
        //获取最新K线数据 -- 华尔街见闻数据
        $this->getLastData4OTC($stockList,$type);
        
        
        exit(0) ;
    }
    
    
    /**
     * 获取最新价数据脚本
     */
    public function runStock() {
        $stocks = Db::name('product')->where(array('status'=>1, 'is_open'=>1))->select();
        $runObj = array();
        $simpleData = array();
        $otcData = array();
        foreach ($stocks as $key=>$val) {
            if($val['cid'] == 1){
                $simpleData[] = $val;
            }else{
                $otcData[] = $val;
            }
        }
        foreach ($simpleData as $val){
            $code = $val['code'];
            $this->getStock($code);
        }
        
        // 获取最新价格数据，华尔街见闻接口
        $this->getStock4OTC($otcData);
        
        exit(0);
    }

    /**
     * 获取商品价格信息 -- 华尔街见闻接口
     */
    public function getStock4OTC($stockList) {
        $codeList = array();
        foreach ($stockList as $item){
            $codeList[] = $item['code'].'.'.$item['product_suffix'];
            
        }
		
		$url = "https://api-ddc-wscn.awtmt.com/market/real?prod_code=".join(',',$codeList);
		$url .= "&fields=symbol,prod_code,prod_name,prod_en_name,preclose_px,price_precision,open_px,high_px,low_px,week_52_high,week_52_low,update_time,last_px,px_change,px_change_rate,market_type,trade_status";
		
		$response = http_request($url);
		$data = json_decode($response, true);
		if (!$data) {
			//说明采用了GZIP压缩
			$data = gzdecode($response);
			$data = json_decode($data, true);
		} 
		
		if(!empty($data) && $data['code'] == 20000){
			$dataList = $data['data']['snapshot'];
			foreach($dataList as $key=>$tick){
                $code = $tick[0];
                $result = array();
                $result['code'] = $code;
                $result['vol'] = $tick[10]*10000;
                $result['price_high'] = $tick[7];
                $result['price_low'] = $tick[8];
                $result['price'] = $tick[12];
                
                //波动价格
                $numss = strlen(substr(strrchr($result['price'], "."), 1));  // 获取小数点位数
                if($numss < 3){
                    $numss = 3;
                }
                $randNum = mt_rand(-18, 18);
                $randNum = $randNum * pow(10,-($numss+1));
                $price =  $result['price'] + $randNum;
                $price =  round($price,$numss+1);
                $result['price'] = $price;
                $types = array('1min', '5min', '30min', '1hour', '1day');
                foreach($types as $type){
                    $stockKList = Cache::get($code.'_stock_'.$type);
                    $stockKList = unserialize($stockKList);
                    $lastData =  array_slice($stockKList, -1, 1);
                    $lastData = $lastData[0];
                    $lastData['close'] = $price;
                    if($price > $lastData['high'] ){
                        $lastData['high'] = $price;
                    }
                    if($price < $lastData['low'] ){
                        $lastData['low'] = $price;
                    }
                    
                    $stockKList[$lastData['time']] = $lastData;
                    Cache::set($code.'_stock_'.$type,serialize($stockKList));
                    
                }
                
                // $result['vol'] = mt_rand(1000,999999)/pow(10,mt_rand(1,3));
                $result['price_update'] = date('Y-m-d H:i:s');
                $result['open_price'] = $tick[6];
                $result['time'] = date('Y-m-d H:i:s', $tick[11]);
                $result['price_zf'] = $tick[13];
                if ($result){
                    Db::name('product')->where(array('code'=>$code))->update($result);
                }

                $res = $result;
                if ($res) {
                    Cache::set($code.'_stock', serialize($res));
                    // echo $code.PHP_EOL;
                }
			}
		}
    
        
    }
/**
     * 获取商品价格信息
     */
    public function getStock($code) {
        $last1MinK = (unserialize(Cache::get($code.'_stock_new_1min')));
        $stock24 = (unserialize(Cache::get($code.'_stock24_')));
        
        $stock24['close'] = $last1MinK['close'];
        $stock24['volume'] = $stock24['volume'] / 10000;
        if ($stock24) {
            $stock24['code'] = $code;
            $res = $this->getFormat($stock24);
            
            if ($res) {
                Cache::set($code.'_stock', serialize($res));
                // echo $code.PHP_EOL;
            }
        }else{
            // echo 'getStock 异常'.PHP_EOL;
        }
       
        
    }

    
    
    // 获取最新K线数据 -- 华尔街见闻数据
    public function getLastData4OTC($stockList,$type){
        
        if (!in_array($type, array('1min', '5min', '30min', '1hour', '1day'))) {
            return false;
        }
        $map = array('1min'=>'1m', '5min'=>'5m', '30min'=>'30m', '1hour'=>'1h', '1day'=>'1d');
        $rows = 100;
        $interval = $map[$type];
        if(strpos($interval,'m')){
			$interval = str_replace('m','',$interval);
			$interval = $interval * 60;
			$rows = 100;
		}else if(strpos($interval,'d')){
			$interval = str_replace('d','',$interval);
			$interval = $interval * 60 * 24 * 60;
		}else if(strpos($interval,'h')){
			$interval = str_replace('h','',$interval);
			$interval = $interval * 60 * 60;
		}
        foreach ($stockList as $stockItem){
            $prod_code = $stockItem['code'].'.'.$stockItem['product_suffix'];
            $url = "https://api-ddc-wscn.awtmt.com/market/kline?prod_code={$prod_code}&tick_count={$rows}&period_type={$interval}&adjust_price_type=forward";
			$url .= "&fields=tick_at,open_px,close_px,high_px,low_px,turnover_volume,turnover_value,average_px,px_change,px_change_rate,avg_px,ma2";
			
			$response = http_request($url);
		var_dump($response);
		
			$res = json_decode($response,true);
			
			if($res['code'] == 20000){
				$returnData = [];
				$dataList = $res['data']['candle'][$prod_code]['lines'];
				foreach($dataList as $item){
				    $time = $item[9].'000';
				    $temp = array(
                        'time' => $time,
                        'open' => $item[0],
                        'close' => $item[1],
                        'high' => $item[2],
                        'low' => $item[3],
                        'volume' => mt_rand(1000,9999) / pow(10,mt_rand(1,3)),
                    );
					$returnData[$time] = $temp;
				}
				
				// $vol = array_column($returnData,0);
    //             array_multisort($vol,SORT_ASC,$returnData);
                // $returnData;
                $code = $stockItem['code'];
                $data = $returnData;
                if ($data) {
                    $lastData =  array_slice($data, -1, 1);
                    if ($lastData[0]) {
                        $res = Cache::set($code.'_stock_new_'.$type, serialize($lastData[0]));
                        // if ($res)
                        //     echo $code.'_stock_new_'.$type.PHP_EOL;
                    }
                    //存储K线行情数据
                    $res = Cache::set($code.'_stock_'.$type, serialize($data));
                    // if ($res)
                    //     echo $code.'_stock_'.$type.PHP_EOL;
                }

			}

        }

    }

    
    /**
     * 获取最新K线数据
     * @return boolean
     */
    public function getLastData($type,$code) {

        if (!in_array($type, array('1min', '5min', '30min', '1hour', '1day'))) {
            return false;
        }
        $typeMap = array('1min'=>'1m', '5min'=>'5m', '30min'=>'30m', '1hour'=>'1h', '1day'=>'1d');

        $mapLimit = array('1min'=>120, '5min'=>300, '30min'=>1800, '1hour'=>3600, '1day'=>86400);
        
        $limit = 120;
        $startTime = time();
        $startTime = $startTime - ($mapLimit[$type] * $limit);
        $startTime = $startTime."000";

        // $lastDataCache = array();
        // if(!empty(Cache::get($code.'_stock_'.$type))){
        //     $lastDataCache = (unserialize(Cache::get($code.'_stock_'.$type)));
        //     $tempData = array();
        //     if(!empty($lastDataCache)){

        //         foreach ($lastDataCache as $i){
        //             $tempData[$i['time']] = $i;
        //         }
        //         $lastDataCache = $tempData;
        //     }

        // }

        $url = "https://www.528btc.com/e/extend/api/index.php?m=v2&c=kline&coin=binance_{$code}_USDT&start={$startTime}&interval={$typeMap[$type]}";

        //file_put_contents('getLastData.txt',$url);
        
        $result = stock_request($url);

        $data = $this->getFormatLine($result, $code);
        
        if ($data) {
            $lastData =  array_slice($data, -1, 1);
            
            if ($type == '1min' && $lastData[0]) {
                //var_dump($lastData[0]);
                $res = Cache::set($code.'_stock_new_'.$type, serialize($lastData[0]));
                // if ($res)
                //     echo $code.'_stock_new_'.$type.PHP_EOL;
            }
            $lastData =  array_slice($data, count($data)-24, 24);
            
            if($type == '1hour'){ // 如果是小时k，获取最近24小时的最大/最小/开盘

                $dataFor24h = $lastData[0];
                
                for($h=0;$h<=23;$h++){
                    if($lastData[$h]['high']>$dataFor24h['high']){
                        $dataFor24h['high'] = $lastData[$h]['high'];
                    }
                    
                    if($lastData[$h]['low']<$dataFor24h['low']){
                        $dataFor24h['low'] = $lastData[$h]['low'];
                    }
                    
                    $dataFor24h['volume'] += $lastData[$h]['volume'];
                }
                //echo "$code ==== ".json_encode($dataFor24h).PHP_EOL;
                Cache::set($code.'_stock24_', serialize($dataFor24h));
            }
            //存储K线行情数据
            $lastDataCache=[];
            $aa = array_merge($lastDataCache,$data);
            $res = Cache::set($code.'_stock_'.$type, serialize($aa));
            if ($res)
                echo $code.'_stock_'.$type."_设置了数量：".count($aa).PHP_EOL;
        }

        
    }
 
    
    /**
     * 获取格式化K线数据
     * @param unknown $data
     * @return boolean|unknown[][]|mixed[][]
     */
    private function getFormatLine($data) {

        $result = array();
        
        if (!data)
            return false;
        foreach ($data as $key=>$val) {
            $time = $val['T'];
            $result[$time] = array(
                'time' => $time,
                'open' => $val['o'],
                'close' => $val['c'],
                'high' => $val['h'],
                'low' => $val['l'],
                'volume' => $val['v']*10000,
            );
        }
       
        return $result;
    }
    
    
   
    
    
    /**
     * 格式化最新数据
     * @param unknown $data
     * @param unknown $code
     * @return boolean|unknown[]|string[]|NULL[]|mixed[]|boolean[]
     */
    private function getFormat($data) {
                
    //   array(6) {
    //       ["time"]=>
    //       int(1702841520000)
    //       ["open"]=>
    //       float(2235.13)
    //       ["close"]=>
    //       float(2236.05)
    //       ["high"]=>
    //       float(2236.4)
    //       ["low"]=>
    //       float(2235.04)
    //       ["volume"]=>
    //       float(100.196)
    //     }
    
    
        // var_dump($data);
        
        $result = array();
        if (!$data)
            return false;
            
            
        // 做一个价格的随机波动
        $str = $data['close'];
        $numss = strlen(substr(strrchr($str, "."), 1));  // 获取小数点位数
        if($numss < 3){
            $numss = 3;
        }
        $randNum = mt_rand(-18, 18);
        $randNum = $randNum * pow(10,-($numss+1));
        $price = $data['close'] + $randNum;
        $price = round($price,$numss+1);
        $code = $data['code'];
         // 对比k线 最新数据 和波动价格匹配
        $types = array('1min', '5min', '30min', '1hour', '1day');
        foreach($types as $type){
            $stockKList = Cache::get($code.'_stock_'.$type);
            $stockKList = unserialize($stockKList);
            $lastData =  array_slice($stockKList, -1, 1);
            $lastData = $lastData[0];
            $lastData['close'] = $price;
            if($price > $lastData['high'] ){
                $lastData['high'] = $price;
            }
            if($price < $lastData['low'] ){
                $lastData['low'] = $price;
            }
            
            $stockKList[$lastData['time']] = $lastData;
            Cache::set($code.'_stock_'.$type,serialize($stockKList));
            
        }
        
        $result['code'] = $code;
        $result['price_high'] = $data['high'];
        $result['price_low'] = $data['low'];
        $result['price'] = $price;
        $result['vol'] = $data['volume'];
        $result['price_update'] = date('Y-m-d H:i:s');
        $result['open_price'] = $data['open'];
        $result['time'] = date('Y-m-d H:i:s', $data['time']/1000);
        $result['price_zf'] = ($data['close'] - $data['open']) / $data['open'] / 100;
        if ($result)
            Db::name('product')->where(array('code'=>$code))->update($result);
        return $result;
    }
    
    
    
    
}