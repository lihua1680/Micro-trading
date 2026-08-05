<?php 
namespace app\common;

class RedisLock
{
    private $redis;

    public function __construct()
    {
        $this->redis = new \Redis();
        $this->redis->connect(config('cache.host'), 6379);
        if(!empty(config('cache.password'))){
            $this->redis->auth(config('cache.password'));
        }
        
    }

    public function lock($key, $expire = 10)
    {
        $now = time();
        $timeout = $now + $expire;

        $result = $this->redis->setnx($key, $timeout);

        if ($result) {
            return true;
        }

        $timeout = $this->redis->get($key);

        if ($now > $timeout) {
            $oldTimeout = $this->redis->getset($key, $now + $expire);
            if ($timeout == $oldTimeout) {
                return true;
            }
        }

        return false;
    }

    public function unlock($key)
    {
        $this->redis->del($key);
    }
}