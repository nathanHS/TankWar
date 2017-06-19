<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/9
 * Time: 18:56
 */
class FireBullet extends Bullet
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
    protected $isHaveBoomEver = false;

//    燃烧间隔
    protected static $fire_interval;
//    燃烧次数
    protected static $times;
//    作用半径
    protected static $r;

    /**
     * @return mixed
     */
    public function getR()
    {
        return static::$r;
    }

    /**
     * @return mixed
     */
    public function getTimes()
    {
        return static::$times;
    }

    /**
     * @return mixed
     */
    public static function getFireInterval()
    {
        return static::$fire_interval;
    }

    /**
     * 因为火焰弹碰到物体直接爆炸，没有互减伤害的部分，所以直接重写move方法
     * @param $po
     *
     * @return $this|bool
     */
    public function move($po)
    {
        if (!$this->getDistanceLife()) {
            $this->boom();
            return $this;
        }
        if ($this->isAlive()) {
            //        获得【假设要朝着方向$po移动一步后】自己的x和y
            list($virtual_x, $virtual_y) = $virtual_x_y = $this->setFaceTo($po)->oneStep($this->getFaceTo());
//        边界
            if ($virtual_x_y == array($this->x, $this->y)) {
                $this->boom();
                return $this;
            }
            if ($bullets_blocks_hit = Bullet::isHittingObj($virtual_x, $virtual_y, $this->getWidth(), $this->getLength(), $this)) {
                foreach ($bullets_blocks_hit as $hit_obj) {
                    if ($is_dead = $this->fightWithBlock($hit_obj)) {
                        $this->boom();
                        return $this;
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

    /**
     * 爆炸
     */
    public function boom(){
        if ($this->isHaveBoomEver())
            $this->destroy();
//        设置作用半径
        $R = $this->getR();
//        获取菱形范围
        $positions = getAllPositionOfLozenge($this->getX(),$this->getY(),$R);
//        对菱形范围遍历，将被伤害的物体添加进容器
        foreach ($positions as list($x,$y))
            $this->getMap()->addPositionToFireBulletHurtArea($x,$y,$this);
//        设置已经爆炸过，这个变量的设置是因为防止别的子弹碰到火焰弹，
//        然后别的子弹计算（扣火焰弹的血），导致火焰弹直接destroy，如果在destroy里直接爆炸可能会爆炸过又destroy。
        $this->setHaveBoomEver(true);
        $this->destroy();
    }

    /**
     * 因为怕不爆炸，也怕二次爆炸，所以重写destory方法
     * @param bool $update
     *
     * @return $this
     */
    public function destroy($update = true)
    {
        if (!$this->isHaveBoomEver())
            $this->boom();
        parent::destroy($update);
    }


    /**
     * @return boolean
     */
    public function isHaveBoomEver(): bool
    {
        return $this->isHaveBoomEver;
    }

    /**
     * @param boolean $isHaveBoomEver
     */
    public function setHaveBoomEver(bool $isHaveBoomEver)
    {
        $this->isHaveBoomEver = $isHaveBoomEver;
    }
}