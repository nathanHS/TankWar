<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/3
 * Time: 11:24
 */
class PayItem
{
    protected $class_name;
    protected $begin_sell_time;
    protected $sell_continue_time;
    protected $num;
    protected $price;

    /**
     * PayItem constructor.
     *
     * @param $class_name string 类名
     * @param $begin_sell_time int 开始售卖时间
     * @param $sell_continue_time int 持续售卖时间
     * @param $num int 一次卖出多少个
     * @param $price int 需要花多少游戏币
     */
    public function __construct($class_name, $begin_sell_time, $sell_continue_time, $num, $price)
    {
        $this->class_name = $class_name;
        $this->begin_sell_time = $begin_sell_time;
        $this->sell_continue_time = $sell_continue_time;
        $this->num = $num;
        $this->price = $price;
    }
}