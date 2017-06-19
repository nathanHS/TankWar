<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/27
 * Time: 9:58
 */

namespace DbOperate;


use GatewayWorker\Lib\Gateway;
use Workerman\Lib\Timer;

class RedisInstance
{
    private static $redis;

    public static function saveRedis(\Redis $redis)
    {
        self::$redis = $redis;
    }
    public static function get():\Redis{
        if (!self::$redis) {
            $redis = new Redis();
            $redis->pconnect("172.20.94.201", 6380);
            RedisInstance::saveRedis($redis);
        }
        return self::$redis;
    }
}