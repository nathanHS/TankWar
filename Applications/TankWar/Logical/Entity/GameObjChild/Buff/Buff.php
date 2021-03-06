<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/11/27
 * Time: 19:52
 */
class Buff extends GameObj
{
    protected static $concept;
    protected static $id;
    protected static $die;
    protected static $length;
    protected static $width;

    protected static $buff_type;

    protected $tank_got;
    protected $get_time;

    /**
     * 获得静态的Buff类型（一次作用还是或是抵命）
     * @return mixed
     */
    public static function getBuffTypeStatic()
    {
        return static::$buff_type;
    }

    /**
     * 非静态方法获取Buff类型
     * @return mixed
     */
    public function getBuffType()
    {
        return $this->buff_type??static::$buff_type;
    }

    /**
     * 设置非静态Buff类型
     * @param mixed $buff_type
     */
    public function setBuffType($buff_type)
    {
        $this->buff_type = $buff_type;
    }

    /**
     * 激活Buff（由子类实现）
     */
    public function active()
    {
        ;
    }

    /**
     * 获取该Buff的所有者
     * @return Tank
     */
    public function getTankGot()
    {
        return $this->tank_got;
    }

    /**
     * 设置该Buff的所有者
     * @param Tank $tank_got
     */
    public function setTankGot($tank_got)
    {
        $this->tank_got = $tank_got;
    }

    /**
     * 获取Buff的获取时间
     * @return mixed
     */
    public function getGetTime()
    {
        return $this->get_time;
    }

    /**
     * 设置Buff的获取时间
     * @param mixed $get_time
     */
    public function setGetTime($get_time)
    {
        $this->get_time = $get_time;
    }

    /**
     * 设置Buff的非静态持续时间
     * @param mixed $continue_time
     */
    public function setContinueTime($continue_time)
    {
        $this->continue_time = $continue_time;
    }

    /**
     * Buff失活（由子类实现）
     */
    public function invalid()
    {
        ;
    }

    /**
     * 手动销毁Buff对象
     * @param bool $update
     *
     * @return GameObj
     */
    public function destroy($update = true)
    {
        $this->invalid();
        if ($this->getBuffType() == NoEnemyBuff::getBuffTypeStatic()) {
            $this->getTankGot()->deleteBuffInLoopBuff($this);
        } elseif ($this->getBuffType() == Buff::getBuffTypeStatic()) {
            $this->getTankGot()->deleteLifeBuff();
        }
        return parent::destroy($update);
    }

    /**
     * 序列化:将Buff对象转成数组
     * @return array
     */
    public function currentInfoArray()
    {
        $arr = parent::currentInfoArray(); // TODO: Change the autogenerated stub
        unset($arr["x"]);
        unset($arr["y"]);
        $arr["tk"] = $this->getTankGot()->get_Id();
        $arr["class"] = (string)get_class($this);
        return $arr;
    }
}

