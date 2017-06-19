<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/9
 * Time: 15:45
 */
class Grass extends Mask
{
    protected static $concept;
    protected static $id;
    protected static $die;
    protected static $length;
    protected static $width;
    protected static $fire_al;

    /**
     * @return mixed
     */
    public function getFireAl()
    {
        return $this->fire_al??static::$fire_al;
    }

    /**
     * @param mixed $flag
     * @return  $this
     */
    public function setFireAl($flag)
    {
        $this->fire_al = $flag;
        return $this;
    }

    public function currentInfoArray()
    {
        $arr = parent::currentInfoArray();
//        烧
        $arr["fire_al"] = $this->getFireAl();

        return $arr;
    }
}