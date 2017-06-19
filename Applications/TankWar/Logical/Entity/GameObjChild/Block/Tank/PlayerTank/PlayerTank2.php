<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/9
 * Time: 16:33
 */
class PlayerTank2 extends PlayerTank
{
    protected static $concept;
    protected static $id;
    protected static $die;
    protected static $length;
    protected static $width;

    protected static $hp;
    protected static $life;
    protected static $side;
    protected static $is_no_enemy;
    protected static $can_move;
    protected static $can_shoot;

    protected static $x_pos;
    protected static $y_pos;
//    移动速度
    protected static $speed;
//    护甲
    protected static $armor;
//    护甲上限
    protected static $armor_top_limit;
//    普通子弹射击间隔
    protected static $normal_shoot_interval;
//    上次射击时间
    protected static $last_shoot_time;
//    朝向
    protected static $face_to;

    public static function getRebornPosition(){
        return array(array(self::getXPos(),self::getYPos()),array(PlayerTank2::getXPos(),PlayerTank2::getYPos()));
    }
}