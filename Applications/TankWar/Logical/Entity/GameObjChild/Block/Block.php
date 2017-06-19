<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/9
 * Time: 15:49
 */

class Block extends GameObj
{
    protected static $concept;
    protected static $id;
    protected static $die;
    protected static $length;
    protected static $width;

    protected static $hp;
    protected static $side;
    protected static $is_no_enemy;

    protected $hurt_from = -1;
    protected $get_hurt = false;

    function setHp($hp)
    {
        $this->hp = $hp;
    }

    function getHp()
    {
        if (isset($this->hp)) {
            return $this->hp;
        }
        return static::$hp;
    }


    public function currentInfoArray()
    {
        $arr = parent::currentInfoArray();
        $arr["hp"] = $this->getHp();
        $arr["hf"] = $this->getHurtFrom();
        $arr["gh"] = (int)$this->isGetHurt();
        $this->setGetHurt(false);
        return $arr;
    }


//    /**
//     * @return mixed
//     */
//    public function getHpIni()
//    {
//        return $this->hp_ini;
//    }
    public function isNoEnemy()
    {
        if (isset($this->is_no_enemy))
            return $this->is_no_enemy;
        return static::$is_no_enemy;
    }

    public function setNoEnemy($flag)
    {
        error_reporting(E_ALL^E_NOTICE);
        $this->is_no_enemy = $flag;
        return $this;
    }

    /**
     * @param      $num
     * @param GameObj $who_cause
     *
     * @return $this
     */
    public function hurt($num, $who_cause = null)
    {
        $this->setGetHurt(true);
        if ($who_cause) $this->setHurtFrom($who_cause->getId());
        else $this->setHurtFrom(-1);

        if (get_class($this) == HomeStone::class){
            echo "家少血".$num."\n";
        }
        if ($this->isNoEnemy()){
            return $this;
        }
        $this->setHp($this->getHp() - $num);
        if ($this->getHp() <= 0) {
            $this->destroy();
        }
        return $this;
    }

    /*** @return mixed
     */
    public function getSide()
    {
        if (isset($this->side))
            return $this->side;
        return static::$side;
    }

    /**
     * @param mixed $side
     */
    public function setSide($side)
    {
        $this->side = $side;
    }

    /**
     * 【作用在自己身上，改变自己属性不改变矩阵信息】的自己的移动一个单位（不改变矩阵信息）
     *
     * @param bool $try 是否只是一次尝试，并不改变自己的属性
     *
     * @return array 返回移动后的坐标值的数组
     */
    public function oneStep($face_to, $try = true):array
    {
        $x = $this->getX();
        $y = $this->getY();

        switch ($face_to) {
            case 1:
                $y = $y < Map::getLengthStatic() - static::getLength() ? $y + 1 : $y;
                break;
            case 2:
                $y = $y > 0 ? $y - 1 : $y;
                break;
            case 3:
                $x = $x > 0 ? $x - 1 : $x;
                break;
            case 4:
                $x = $x < Map::getWidthStatic() - static::getWidth() ? $x + 1 : $x;
                break;
        }
        if (!$try) {
            $this->setX($x)->setY($y);
        }
        return array($x, $y);
    }

    /**
     * @return mixed
     */
    public function getHurtFrom()
    {
        return $this->hurt_from;
    }

    /**
     * @param mixed $die_from
     */
    public function setHurtFrom($die_from)
    {
        $this->hurt_from = $die_from;
    }

    /**
     * @return boolean
     */
    public function isGetHurt(): bool
    {
        return $this->get_hurt;
    }

    /**
     * @param boolean $get_hurt
     */
    public function setGetHurt(bool $get_hurt)
    {
        $this->get_hurt = $get_hurt;
    }

}