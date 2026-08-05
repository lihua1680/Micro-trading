<?php
namespace app\stock\controller;

use think\Controller;
use think\Request;
use think\Db;
use think\Config;
use think\Session;
use think\Cache;

class Bian extends Controller
{
    private $_apiUrl = 'https://api.binance.com/api/v3';
    private $_tradeType = '';
    private $_apiType = '';
    
    public function _initialize() {
        $type = Config::get('site.api_type');
        $this->_tradeType = Config::get('site.trade_type');
        $this->_apiType = $type;
    }
    public function redisTest(){
        // echo 123;
        Cache::set('abc', 123,90);
        echo Cache::get('abc');
        exit;
    }
    public function getUsdt() {
        $url = 'http://api.zb.live/data/v1/ticker?market=usdt_qc';
        $result = stock_request($url);
        // var_dump($result);
        if ($result['ticker']['last']) {
            if ($result['ticker']['last'] > 0) {
                Db::name('config')->where(array('name'=>'usdt_cny_rate'))->setField('value', $result['ticker']['last']);
            }
        }
        
    }
    
    public function start() {
        
        $code = 'BTC';
        $type = '1M';
        $start = date('Y-m-d H:i:s', time() - 60*500 + 1);
        $end = date('Y-m-d H:i:s', time());
        $codeList = array();
        $stocks = Db::name('product')->where(array('status'=>1, 'is_open'=>1, 'cid'=>1))->select();
        foreach ($stocks as $stock) {
            $codeList[] = $stock['code'].'USDT';
        }
     
        $url = $this->_apiUrl . "/ticker/24hr";  
        $result = stock_request($url);

        $stockData = array();
        foreach ($result as $key=>$val) {
            if (in_array($val['symbol'], $codeList)) {
                $stockData[] = $val;
            }
        }
        fh($stockData);
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
            $code = $val['code'].'USDT';
            $this->getStock($code);
        }
        
        // 获取最新价格数据，华尔街见闻接口
        $this->getStock4OTC($otcData);
        
        exit(0);
    }

    
    /**
     * 获取最新价数据脚本
     */
    // public function runStock() {
    //     $stocks = Db::name('product')->where(array('status'=>1, 'is_open'=>1, 'cid'=>1))->select();
    //     $time = time();
    //     $cmds = array();
    //     $path = dirname(dirname(dirname(dirname(__FILE__)))).'/stock.php ';
    //     $runObj = array();
    //     foreach ($stocks as $key=>$val) {
    //         $runObj[] = $val['code'].'USDT';
    //     }
        
    //     foreach ($runObj as $key=>$val) {
    //         $cmds[] = 'php '.$path.'  Bian/getStock/code/'.$val;
    //     }
        
    //     pcntl_signal(SIGCHLD, SIG_IGN);	//如果父进程不关心子进程什么时候结束,子进程结束后，内核会回收。
    //     foreach ($cmds as  $cmd) {
    //         echo $cmd;
            
    //         $pid = pcntl_fork();	//创建子进程
    //         //父进程和子进程都会执行下面代码
    //         if ($pid == -1) {
    //             //错误处理：创建子进程失败时返回-1.
    //             die('could not fork');
    //         } else if ($pid) {
    //             //父进程会得到子进程号，所以这里是父进程执行的逻辑
    //             //如果不需要阻塞进程，而又想得到子进程的退出状态，则可以注释掉pcntl_wait($status)语句，或写成：
    //             pcntl_wait($status, WNOHANG); //等待子进程中断，防止子进程成为僵尸进程。
    //         } else {
    //             //子进程得到的$pid为0, 所以这里是子进程执行的逻辑。
    //             echo shell_exec($cmd);
    //             exit(0) ;
    //         }
    //     }
    //     exit(0);
    // }
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
                $result['price_high'] = $tick[7];
                $result['price_low'] = $tick[8];
                $result['price'] = $tick[12];
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
        
        $url = $this->_apiUrl . "/ticker/24hr?symbol=".$code;
        // echo $url.PHP_EOL;
        $result = stock_request($url);
        // var_dump($result);

        if ($result) {
            $code = str_replace('USDT', '', $code);
            $result['code'] = $code;
            $res = $this->getFormat($result);
            
            if ($res) {
                Cache::set($code.'_stock', serialize($res));
                // echo $code.PHP_EOL;
            }
        }else{
            // echo 'getStock 异常'.PHP_EOL;
        }
       
        
    }
    /**
     * 获取商品价格信息
     */
    // public function getStock() {
        
    //     $code = $this->request->param('code');
    //     $url = $this->_apiUrl . "/ticker/24hr?symbol=".$code;
        
    //     $result = stock_request($url);

    //     if ($result) {
    //         $code = str_replace('USDT', '', $code);
    //         $result['code'] = $code;
    //         $res = $this->getFormat($result);
    //         if ($res) {
    //             Cache::set($code.'_stock', serialize($res));
    //             echo $code.PHP_EOL;
    //         }
    //     }
       
        
    // }
    
    
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
        $map = array('1min'=>'1m', '5min'=>'5m', '30min'=>'30m', '1hour'=>'1h', '1day'=>'1d');
        $mapLimit = array('1min'=>2, '5min'=>14, '30min'=>3, '1hour'=>2, '1day'=>1);
        
        $limit = 100;
        if ($type == '1min')
            $limit = 100;
        $lastDataCache = array();
        if(!empty(Cache::get($code.'_stock_'.$type))){
            $lastDataCache = array_values(unserialize(Cache::get($code.'_stock_'.$type)));
            $tempData = array();
            if(!empty($lastDataCache)){
                $limit=$mapLimit[$type];
                foreach ($lastDataCache as $i){
                    $tempData[$i['time']] = $i;
                }
                $lastDataCache = $tempData;
            }

        }
        
        
        $url = $this->_apiUrl . "/klines?symbol=".$code."USDT&interval=".$map[$type]."&limit=".$limit;
        file_put_contents('getLastData.txt',$url);
        $result = stock_request($url);

        // echo $result.PHP_EOL;
        $data = $this->getFormatLine($result, $code);
        
        if ($data) {
            $lastData =  array_slice($data, -1, 1);
            if ($lastData[0]) {
                $res = Cache::set($code.'_stock_new_'.$type, serialize($lastData[0]));
                // if ($res)
                //     echo $code.'_stock_new_'.$type.PHP_EOL;
                    
            }
            //存储K线行情数据
            $aa = array_merge($lastDataCache,$data);
            $res = Cache::set($code.'_stock_'.$type, serialize($aa),new \DateTime(date('Y-m-d 23:59:59')));
            if ($res)
                echo $code.'_stock_'.$type."_设置了数量：".count($aa).PHP_EOL;
        }

        
    }
     /**
     * 获取最新K线数据
     * @return boolean
     */
    // public function getLastData() {
        
    //     $code = $this->request->param('code');
        
    //     $type = $this->request->param('type');
        
    //     if (!in_array($type, array('1min', '5min', '30min', '1hour', '1day'))) {
    //         return false;
    //     }
    //     $map = array('1min'=>'1m', '5min'=>'5m', '30min'=>'30m', '1hour'=>'1h', '1day'=>'1d');
        
    //     $limit = 500;
    //     if ($type == '1min')
    //         $limit = 1000;
        
    //     $url = $this->_apiUrl . "/klines?symbol=".$code."USDT&interval=".$map[$type]."&limit=".$limit;
    //     file_put_contents('getLastData.txt',$url);
    //     echo $url.PHP_EOL;
    //     $result = stock_request($url);
    //     echo $result.PHP_EOL;
    //     $data = $this->getFormatLine($result, $code);

    //     if ($data) {
    //         $lastData =  array_slice($data, -1, 1);
    //         if ($lastData[0]) {
    //             $res = Cache::set($code.'_stock_new_'.$type, serialize($lastData[0]));
    //             if ($res)
    //                 echo $code.'_stock_new_'.$type.PHP_EOL;
                    
    //         }
    //         //存储K线行情数据
    //         $res = Cache::set($code.'_stock_'.$type, serialize($data));
    //         if ($res)
    //             echo $code.'_stock_'.$type.PHP_EOL;
    //     }

        
    // }
    
    
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
            $time = $val[0];
            $result[$time] = array(
                'time' => $time,
                'open' => $val[1],
                'close' => $val[4],
                'high' => $val[2],
                'low' => $val[3],
                'volume' => $val[5],
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
        // var_dump($data);
        $result = array();
        if (!$data)
            return false;
        $code = $data['code'];
        $result['code'] = $code;
        $result['price_high'] = $data['highPrice'];
        $result['price_low'] = $data['lowPrice'];
        $result['price'] = $data['lastPrice'];
        $result['vol'] = $data['volume'];
        $result['price_update'] = date('Y-m-d H:i:s');
        $result['open_price'] = $data['openPrice'];
        $result['time'] = date('Y-m-d H:i:s', $data['closeTime']/1000);
        $result['price_zf'] = $data['priceChangePercent'];
        if ($result)
            Db::name('product')->where(array('code'=>$code))->update($result);
        return $result;
    }
    
    
    /**
     * 获取24H前信息
     * @param unknown $code
     * @return mixed|boolean
     */
    private function get24HData($code) {
        
        $key = $code.'_stock_open';
        $openPrice = 0;
        $history = unserialize(Cache::get($key));
        $time = strtotime(" -24 hours", strtotime(date('Y-m-d H:i').':00'))*1000;
        if (isset($history[$time])) {
            $openPrice = $history[$time]['open'];
        }
        
        if ($openPrice > 0) {
            return $openPrice;
        }
        return false;
    }
    
    
    
}