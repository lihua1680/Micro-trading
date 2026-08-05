<?php
namespace app\stock\controller;

use think\Controller;
use think\Request;
use think\Db;
use think\Config;
use think\Session;
use think\Cache;

class Zb extends Controller
{
    private $_apiUrl = '';
    private $_tradeType = '';
    private $_apiType = '';
    
    public function _initialize() {
        $type = Config::get('site.api_type');
        $this->_apiUrl = Config::get('site.api_url_'.$type);
        $this->_tradeType = Config::get('site.trade_type');
        $this->_apiType = $type;
    }

    /**
     * 构建安全的 CLI 命令，防止商品代码注入 shell
     */
    private function buildStockCmd($route)
    {
        $script = dirname(dirname(dirname(dirname(__FILE__)))) . '/stock.php';
        return 'php ' . escapeshellarg($script) . ' ' . escapeshellarg($route);
    }

    private function sanitizeProductCode($code)
    {
        return preg_match('/^[A-Za-z0-9._\-]+$/', (string)$code) ? $code : '';
    }
    
    /**
     * 获取最新K线数据
     */
    public function runLastData() {
        $types = array('1min', '5min', '30min', '1hour', '1day', '1week');
        $time = time();
        $cmds = array();
        
        foreach ($types as $key=>$val) {
            $cmds[] = $this->buildStockCmd('Zb/getLastData/type/'.$val);
        }
        
        pcntl_signal(SIGCHLD, SIG_IGN);	//如果父进程不关心子进程什么时候结束,子进程结束后，内核会回收。
        foreach ($cmds as  $cmd) {
            $pid = pcntl_fork();	//创建子进程
            //父进程和子进程都会执行下面代码
            if ($pid == -1) {
                //错误处理：创建子进程失败时返回-1.
                die('could not fork');
            } else if ($pid) {
                //父进程会得到子进程号，所以这里是父进程执行的逻辑
                //如果不需要阻塞进程，而又想得到子进程的退出状态，则可以注释掉pcntl_wait($status)语句，或写成：
                pcntl_wait($status, WNOHANG); //等待子进程中断，防止子进程成为僵尸进程。
            } else {
                //子进程得到的$pid为0, 所以这里是子进程执行的逻辑。
                echo shell_exec($cmd);
                exit(0) ;
            }
        }
        exit(0) ;
    }
    
    
    /**
     * 获取历史
     */
    public function runHistory() {
        $stocks = Db::name('product')->where(array('status'=>1, 'is_open'=>1))->select();
        $cmds = array();
        foreach ($stocks as $key=>$val) {
            
            $code = $this->sanitizeProductCode($val['code']);
            if ($code === '') {
                continue;
            }
            $data = unserialize(Cache::get($code.'_stock_open'));

            if (!$data) {
                $cmds[$code] = $this->buildStockCmd('Zb/getHistoryData/code/'.$code);
                continue;
            } 
            
            $lastData = array_slice($data, -1, 1);
            if ($lastData[0]['time'] < (time() - 3600*20)*1000 || !$lastData) {
                $cmds[$code] = $this->buildStockCmd('Zb/getHistoryData/code/'.$code);
            }
            
        }

        
        pcntl_signal(SIGCHLD, SIG_IGN);	//如果父进程不关心子进程什么时候结束,子进程结束后，内核会回收。
        foreach ($cmds as  $cmd) {
            $pid = pcntl_fork();	//创建子进程
            //父进程和子进程都会执行下面代码
            if ($pid == -1) {
                //错误处理：创建子进程失败时返回-1.
                die('could not fork');
            } else if ($pid) {
                //父进程会得到子进程号，所以这里是父进程执行的逻辑
                //如果不需要阻塞进程，而又想得到子进程的退出状态，则可以注释掉pcntl_wait($status)语句，或写成：
                pcntl_wait($status, WNOHANG); //等待子进程中断，防止子进程成为僵尸进程。
            } else {
                //子进程得到的$pid为0, 所以这里是子进程执行的逻辑。
                echo shell_exec($cmd);
                exit(0) ;
            }
        }
        exit(0) ;
     
        
    }
    
    
    /**
     * 获取最新价数据脚本
     */
    public function runStock() {
        $stocks = Db::name('product')->where(array('status'=>1, 'is_open'=>1))->select();
        $time = time();
        $cmds = array();
        
        foreach ($stocks as $key=>$val) {
            $code = $this->sanitizeProductCode($val['code']);
            if ($code === '') {
                continue;
            }
            $cmds[] = $this->buildStockCmd('Zb/getStock/code/'.$code);
        }
        
        pcntl_signal(SIGCHLD, SIG_IGN);	//如果父进程不关心子进程什么时候结束,子进程结束后，内核会回收。
        foreach ($cmds as  $cmd) {
            $pid = pcntl_fork();	//创建子进程
            //父进程和子进程都会执行下面代码
            if ($pid == -1) {
                //错误处理：创建子进程失败时返回-1.
                die('could not fork');
            } else if ($pid) {
                //父进程会得到子进程号，所以这里是父进程执行的逻辑
                //如果不需要阻塞进程，而又想得到子进程的退出状态，则可以注释掉pcntl_wait($status)语句，或写成：
                pcntl_wait($status, WNOHANG); //等待子进程中断，防止子进程成为僵尸进程。
            } else {
                //子进程得到的$pid为0, 所以这里是子进程执行的逻辑。
                echo shell_exec($cmd);
                exit(0) ;
            }
        }
        exit(0);
    }

    /**
     * 获取商品价格信息
     */
    public function getStock() {
        $code = $this->request->param('code');
        $market = strtolower($code).'_'.$this->_tradeType;
        switch ($this->_apiType) {
            case 'zb':
                $apiUrl = $this->_apiUrl.'ticker?market='.$market;
                break;
        }
        $result = http_request($apiUrl);
        $data = json_decode($result, true);
        $res = $this->getFormat($data, $code);
        
        if ($res) {
            Cache::set($code.'_stock', serialize($res));
            echo $code.PHP_EOL;
        }
        echo $stock['code'].PHP_EOL;
        
    }
    
    
    /**
     * 获取最新K线数据
     * @return boolean
     */
    public function getLastData() {
        
        $type = $this->request->param('type');
        
        if (!in_array($type, array('1min', '5min', '30min', '1hour', '1day', '1week'))) {
            return false;
        }
        $stocks = Db::name('product')->where(array('status'=>1, 'is_open'=>1))->select();

        foreach ($stocks as $key=>$stock) {
            $code = $stock['code'];
            $market = strtolower($code).'_'.$this->_tradeType;
            switch ($this->_apiType) {
                case 'zb':
                    $size = 500;
                    if ($type == '1min') 
                        $size = 1000;
                    $apiUrl = $this->_apiUrl.'kline?type='.$type.'&size='.$size.'&market='.$market;
                    break;
            }
            $result = http_request($apiUrl);

            $data = $this->getFormatLine($result);
            
            if ($data) {
                $lastData =  array_slice($data, -1, 1);
                
                if ($lastData[0]) {
                    $res = Cache::set($code.'_stock_new_'.$type, serialize($lastData[0]));
                    if ($res)
                        echo $code.'_stock_new_'.$type.PHP_EOL;
                        
                }
                //存储K线行情数据
                $res = Cache::set($code.'_stock_'.$type, serialize($data));
                if ($res)
                    echo $code.'_stock_'.$type.PHP_EOL;
            }
        }
        
        
    }
    
    /**
     * 获取历史K线数据   主要用来查找历史数据
     */
    public function getHistoryData() {
        $code = $this->request->param('code');
        $stocks = Db::name('product')->where(array('status'=>1, 'is_open'=>1,'code'=>$code))->select();
        $stime = strtotime(" -25 hours", strtotime(date('Y-m-d H:i').':00'));

        foreach ($stocks as $key=>$stock) {
            $code = $stock['code'];
            $market = strtolower($code).'_'.$this->_tradeType;
            switch ($this->_apiType) {
                case 'zb':
                    $apiUrl = $this->_apiUrl.'kline?type=1min&size=1000&market='.$market.'&since='.$stime*1000;
                    break;
            }
            $result = http_request($apiUrl);
            $data = $this->getFormatLine($result);
            
            if ($data) {
                $res = Cache::set($code.'_stock_open', serialize($data));
                echo $code.'_stock_open'.PHP_EOL;
            }
        }
        
        
    }
    
    /**
     * 获取格式化K线数据
     * @param unknown $data
     * @return boolean|unknown[][]|mixed[][]
     */
    private function getFormatLine($data) {
        $data = json_decode($data, true);
        $result = array();
        
        if (!isset($data['data']))
            return false;
        foreach ($data['data'] as $key=>$val) {
            $result[$val[0]] = array(
                'time' => $val[0],
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
    private function getFormat($data, $code) {

        $result = array();
        if ($this->_apiType == 'zb') {
            $date = $data['date'];
            $data = $data['ticker'];
            
            if (!$data)
                return false;
            $open_price = $this->get24HData($code);
            
            if (!$open_price)
                return false;
            $result['code'] = $code;
            $result['price_high'] = $data['high'];
            $result['price_low'] = $data['low'];
            $result['price'] = $data['last'];
            $result['vol'] = $data['vol'];
            $result['price_update'] = date('Y-m-d H:i:s');
            $result['open_price'] = $open_price;
            $result['time'] = $date;
            $result['price_zf'] = round(($result['price'] - $result['open_price'])/$result['open_price']*100, 2);
        }
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