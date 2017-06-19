<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/12/24
 * Time: 12:03
 */
class HeartBuff extends Buff
{
    protected static $concept;
    protected static $id;
    protected static $die;
    protected static $length;
    protected static $width;

    protected static $buff_type;

    protected static $move_up;
    protected static $shoot_interval_time_down;
    protected static $blood_up;
    protected static $shield_up;

    /**
     * @return mixed
     */
    public function getShieldUp()
    {
        return $this->shield_up??static::$shield_up;
    }

    /**
     * @return mixed
     */
    public function getBloodUp()
    {
        return $this->blood_up??static::$blood_up;
    }

    /**
     * @return mixed
     */
    public function getShootIntervalTimeDown()
    {
        return $this->shoot_interval_time_down??static::$shoot_interval_time_down;
    }

    /**
     * @return mixed
     */
    public function getMoveUp()
    {
        return $this->move_up??static::$move_up;
    }

    function active()
    {
        parent::active();

        $this->getTankGot()
            ->hpUp($this->getBloodUp())
            ->moveUp($this->getMoveUp())
            ->shootIntervalDown($this->getShootIntervalTimeDown());
        $this->getTankGot()
            ->shieldUp($this->getShieldUp());
    }

}