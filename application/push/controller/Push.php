<?php

namespace app\push\controller;

require_once VENDOR_PATH . "workerman/phpsocket.io/src/autoload.php";

use PHPSocketIO\SocketIO;
use Workerman\Worker;
use Workerman\Lib\Timer;
use think\Cache;
class Push
{
    
    public function index()
    {
        // 传入ssl选项，包含证书的路径
        $context = array(
            'ssl' => array(
                'local_cert'  => '/www/server/panel/vhost/cert/119.45.184.58/fullchain.pem',
                'local_pk'    => '/www/server/panel/vhost/cert/119.45.184.58/privkey.pem',
                'verify_peer' => false,
            )
        );
        
        $io = new SocketIO(8080);//socket的端口
        
        
        // 当有客户端连接时
        $io->on('connection', function ($socket) use ($io) {

            
            // 定义k线事件回调函数
            $socket->on('kline_type', function ($msg) use ($io, $socket) {

                //加入k线组
                $socket->join('kline_'.$msg);
                
                Timer::add(1, function ($msg)use ($io){
                    $lines = array('1min'=>'1min', '5min'=>'5min', '30min'=>'30min', '60min'=>'1hour', '1D'=>'1day');
                    
                    foreach ($lines as $key=>$val) {
                        $stock = unserialize(Cache::get($msg.'_stock_new_'.$val));
                        $price = unserialize(Cache::get($msg.'_stock'));
                        $stock['close'] = $price['price'];
                        $stock['code'] = $price['code'];
                        $stock['type'] = $key;
                        $stock['stock'] = $price;
                        
                        //行情
                        $io->to('kline_'.$msg)->emit('kline', $stock);
                    }
                    
                    
                }, array($msg), true);
                
                
            }, array($socket), true);
            
            
        });
        
        
            
            
        $io->on('workerStart', function () use ($io) {
            
        });
                
                
        Worker::runAll();
    }
    
}