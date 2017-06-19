<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/12/29
 * Time: 15:27
 */
use \DbOperate\RedisInstance;

class GameDataSaver
{

    public static function getMaxLevel($players_arr):int
    {
        sort($players_arr);
        return (int)RedisInstance::get()->get("MaxGame" . $players_arr[0] . "+" . $players_arr[1]);
    }

    public static function setMaxLevel($players_arr, $level)
    {
        sort($players_arr);
        RedisInstance::get()->set("MaxGame" . $players_arr[0] . "+" . $players_arr[1], $level);
    }

    public static function setTankLevelInfo($players_arr,$player,$level,$info){
        sort($players_arr);
        RedisInstance::get()->hSet("GameRecord{$level}:{$players_arr[0]}+{$players_arr[1]}",$player,$info);
    }
    public static function getTankLevelInfo($players_arr,$player,$level){
        sort($players_arr);
        return (string)RedisInstance::get()->hGet("GameRecord{$level}:{$players_arr[0]}+{$players_arr[1]}",$player);
    }
}