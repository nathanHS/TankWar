<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/11/20
 * Time: 15:11
 */
use GatewayWorker\Lib\Gateway;
use \AnalysisQues\Protocol;

class Bullet extends Block
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
    protected static $face_to;


    protected static $speed;
    protected static $attack;
    protected static $from;
    protected static $number;

    protected static $distance_life;
    protected static $super_shoot_interval = 1000;

    /**
     * @return mixed
     */
    public function getSuperShootInterval()
    {
        return $this->super_shoot_interval??static::$super_shoot_interval;
    }

    /**
     * @param mixed $super_shoot_interval
     */
    public function setSuperShootInterval($super_shoot_interval)
    {
        $this->super_shoot_interval = $super_shoot_interval;
    }


    /**
     * @return mixed
     */
    public function getDistanceLife()
    {
        return $this->distance_life??static::$distance_life;
    }

    /**
     * @param mixed $distance_life
     *
     * @return $this
     */
    public function setDistanceLife($distance_life)
    {
        $this->distance_life = $distance_life;
        return $this;
    }


    function currentInfoArray()
    {
        $arr = parent::currentInfoArray();
        $arr["sp"] = $this->getSpeed();
        $arr["po"] = $this->getFaceTo();
        return $arr;
    }

    public function setFaceTo($face_to)
    {
        $this->face_to = $face_to;
        return $this;
    }

    public function setFrom($tank)
    {
        $this->from = $tank;
        return $this;
    }

    public function getNumber()
    {
        return $this->number??static::$number;
    }

    /**
     * @param $x
     * @param $y
     * @param $width
     * @param $length
     * @param $obj
     * @return array
     * @param Bullet|map $bullet Bullet
     *                           是否打到砖块(假设自己是x,y,width,length的情况下)
     *
     * if else  是因为: 当刚射出来的时候(正打算射出来,这个时候是没有子弹对象的 所以不能用上面那段有bullets_hit的代码)
     *
     */
    static public function isHittingObj($x, $y, $width, $length, $obj)
    {
        if ((is_a($obj, Bullet::class)||is_subclass_of(obj,Bullet::class)) && ($bullet = $obj)) {

            /** @var Bullet $bullet */
            $map = $bullet->getMap();
//        碰撞到子弹
            $collision_objects = $bullet->getMap()->computeCollision($x, $y, $width, $length, $bullet->getConcept());
            $id = $bullet->get_Id();
            $from_tank = $bullet->getFrom();
//        把自己过滤掉
            $bullets_hit = (array_filter($collision_objects,
                function ($var) use ($id) {
                    return $var->get_Id() != $id;
                }
            ));

//            碰到碰撞体
            $block_collision_objects = $map->computeCollision($x, $y, $width, $length, 3);
//            把自己的坦克过滤掉
            $blocks_hit = (array_filter($block_collision_objects,
                function ($var) use ($from_tank) {
                    return ($var->get_Id() != $from_tank->get_Id()) && ($var->getSide() != $from_tank->getSide());

                }
            ));
            return array_merge($bullets_hit, $blocks_hit);

        } else if ((is_subclass_of($obj, Tank::class) || is_a($obj, Tank::class)) && ($tank = $obj)) {
            /** @var Tank $tank */
            $map = $tank->getMap();
//            碰撞到子弹(此时自己不可能存在)
            $bullets_hit = $map->computeCollision($x, $y, $width, $length, 5);
//            碰到砖块坦克
            $block_collision_objects = $map->computeCollision($x, $y, $width, $length, 3);
//            把开炮的坦克过滤掉
            $blocks_hit = (array_filter($block_collision_objects,
                function ($var) use ($tank) {
                    return ($var->get_Id() != $tank->get_Id());
                }
            ));
            return array_merge($bullets_hit, $blocks_hit);

        }
        return null;
    }

    /**
     * @param $block Tank
     *
     * @return bool is_dead
     */
    public function fightWithBlock($block)
    {
        $block_side = $block->getSide();
        if ($block_side == ($from_side = $this->getFrom()->getSide()) || $block->isNoEnemy()) {
            $this->destroy();
            return false;
        }

        while ($block->isAlive() && $this->isAlive()) {
            $this->hurt(method_exists($block, "getAttack") ? $block->getAttack() : $block->getHp(),$block)->updateMe();
            $block->hurt($this->getAttack(),$this);
            $block->updateMe();
            if (!$block->isAlive() && $from_side == PlayerTank::getSideStatic() && $block_side == PcTank::getSideStatic()) {
                $this->getMap()->recordAPlayerKill((string)$this->getFrom(), $block->getId());
            }
        }
        return !$this->isAlive();
    }

    public function move($po)
    {
        if (!$this->getDistanceLife()) {
            $this->destroy();
        }
        if ($this->isAlive()) {
            //        获得【假设要朝着方向$po移动一步后】自己的x和y
            list($virtual_x, $virtual_y) = $virtual_x_y = $this->setFaceTo($po)->oneStep($this->getFaceTo());
//        边界
            if ($virtual_x_y == array($this->x, $this->y)) {
                $this->destroy();
                return false;
            }
            if ($bullets_blocks_hit = $this->isHittingObj($virtual_x, $virtual_y, $this->getWidth(), $this->getLength(), $this)) {
                foreach ($bullets_blocks_hit as $hit_obj) {
                    if ($is_dead = $this->fightWithBlock($hit_obj)) {
                        return false;
                    }
                }
            }
            $this->getMap()->setObjectOutOfMatrix($this);
            $this->oneStep($po, false);
            $this->getMap()->addObjToMatrix($this, $this->getLength(), $this->getWidth(), $this->x, $this->y, $this->getConcept());
            $this->updateMe();
            $this->setDistanceLife($this->getDistanceLife() - 1);
            return $this;
        }
    }


    public function objAddedToMap($map)
    {
        $map->addObjToBulletContainer($this);
        return parent::objAddedToMap($map);
    }

    public function destroy($update = true)
    {
        $this->setDie();
        $this->updateMe();
        /** @var Map $map */
        $map = $this->getMap();
        $map->deleteAllObjIndex($this);
        $map->setObjectOutOfMatrix($this);
        $this->getMap()->deleteBulletIndex($this);
        return $this;
    }


    /**
     * @return mixed
     */
    public function getAttack()
    {
        return $this->attack ?? static::$attack;
    }

    /**
     * @param mixed $attack
     */
    public function setAttack($attack)
    {
        $this->attack = $attack;
    }

    /**
     * @return Tank
     */
    public function getFrom()
    {
        return $this->from ?? static::$from;
    }


    /**
     * @return mixed
     */
    public function getFaceTo()
    {
        if (isset($this->face_to))
            return $this->face_to;
        return static::$face_to;
    }

    function computeReverseFaceTo($face_to)
    {
        switch ($face_to) {
            case 1:
                return 2;
            case 2:
                return 1;
            case 3:
                return 4;
            case 4:
                return 3;
        }

    }

    /**
     * @return mixed
     */
    public function getSpeed()
    {
        if (isset($this->speed))
            return $this->speed;
        return static::$speed;
    }

    /**
     * @param mixed $speed
     */
    public function setSpeed($speed)
    {
        $this->speed = $speed;
    }
}