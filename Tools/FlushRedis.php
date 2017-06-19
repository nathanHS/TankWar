<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/18
 * Time: 12:19
 */

$redis = new Redis();
$redis->pconnect("0.0.0.0", 6380);

$redis->flushDB();