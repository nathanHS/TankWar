<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/9
 * Time: 15:33
 */
class MarketItem
{
    public $id;
    public $price;
    public $num;
    public $time;
    public $class;
    public $buy_time = 0;

    /**
     * MarketItem constructor.
     *
     * @param $id
     * @param $price
     * @param $num
     * @param $time
     * @param $class
     */
    public function __construct($id, $price, $num, $time, $class)
    {
        $this->id = $id;
        $this->price = $price;
        $this->num = $num;
        $this->time = $time;
        $this->class = $class;
    }

    /**
     * @param $json string
     *
     * @return MarketItem
     */
    public static function constructAItemFromJson($json)
    {
        $item = new MarketItem(1,1,1,1,1);
        $obj = json_decode($json);
        $item->setId($obj->id);
        $item->setPrice($obj->price);
        $item->setNum((int)$obj->num);
        $item->setTime((int)$obj->time);
        $item->setClass($obj->class);
        $item->setBuyTime((int)$obj->buy_time);
        return $item;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param mixed $price
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }

    /**
     * @return mixed
     */
    public function getNum()
    {
        return $this->num;
    }

    /**
     * @param mixed $num
     */
    public function setNum($num)
    {
        $this->num = $num;
    }

    /**
     * @return mixed
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @param mixed $time
     */
    public function setTime($time)
    {
        $this->time = $time;
    }

    /**
     * @return mixed
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param mixed $class
     */
    public function setClass($class)
    {
        $this->class = $class;
    }

    /**
     * @return int
     */
    public function getBuyTime(): int
    {
        return $this->buy_time;
    }

    /**
     * @param int $buy_time
     */
    public function setBuyTime(int $buy_time)
    {
        $this->buy_time = $buy_time;
    }

    /**
     * 用户购买了一个道具，记录当前时间
     * @return MarketItem
     */
    public function createAnItem(){
        $new_item = MarketItem::constructAItemFromJson(json_encode($this));
        $new_item->setBuyTime(getMillisecond());
        return $new_item;
    }
}