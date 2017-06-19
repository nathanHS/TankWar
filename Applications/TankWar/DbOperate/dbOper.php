<?php
namespace DbOperate;

class dbOper
{
    public static $_instance = array();

    public static function  init($instance_name, $dsn,$name,$key)
    {
        self::$_instance[$instance_name] = new \PDO($dsn,$name,$key);
    }
    public static function instance($str){
        return self::$_instance[$str];
    }
}

