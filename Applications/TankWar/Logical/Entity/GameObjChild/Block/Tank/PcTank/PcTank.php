<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/12/21
 * Time: 14:18
 */
class PcTank extends Tank
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

    protected $ai_type;
    protected $way_to_base;
    protected $p_shoot;
    public function destroy($update = true)
    {
        parent::destroy($update);
        $this->getMap()->recordATankDeath();
        $this->getMap()->deletePcTankIndex($this);
        if ($this->getMap()->getAllTankKilledNum() == $this->getMap()->getPcTankNumAll()){
            $this->getMap()->setFinish(1);
            $this->getMap()->getGame()->flushAllInfo();
        }
        return $this;
    }
    /**
     * @return mixed
     */
    public function getCanShoot()
    {
        return $this->can_shoot??self::$can_shoot && p_happen($this->getPShoot());
    }
    /**
     * @return mixed
     */
    public function getAiType()
    {
        return $this->ai_type;
    }

    /**
     * @param mixed $ai_type
     *
     * @return  $this;
     */
    public function setAiType($ai_type)
    {
        $this->ai_type = $ai_type;
        return $this;
    }

    public function moveByAiType()
    {
        switch ($this->getAiType()) {
            case "rand":
                $this->randMove();
                break;
            case "base":
                $this->toBaseMove();
                break;
        }
    }

    /**
     * 随机移动【速度】个单位
     */
    public function randMove()
    {
//        随机朝向
        $rand_face_to = array(1, 2, 3, 4);
        shuffle($rand_face_to);
//        固定时刻的速度
        for ($i = 0; $i < $this->getSpeed(); $i++) {
            $face_to = $this->getFaceTo();
//            移动失败（撞到可碰撞体或者边界）
            if (!$this->move($face_to)) {
//                射击
                if (random_int(0, 15) < 8)
                    $this->shootNormalBullet();
//                    转向
                foreach ($rand_face_to as $change_face_to) {
                    if ($this->move($change_face_to))
                        break;
                    if (rand(1, 10) < 3) {
                        $this->shootNormalBullet();
                    }
                }

            }

            if (random_int(1, 20) < 3)
                $this->shootNormalBullet();
            $this->updateMe();
        }
    }

    private function toBaseMove()
    {
        /** @var array $way_to_base */
        for ($i = 0; $i < $this->getSpeed(); $i++) {
            $way_to_base = $this->getWayToBase();
            $way_index = $way_face_to = 0;
            foreach ($way_to_base as $index => $face_to) {
                $way_index = $index;
                $way_face_to = $face_to;
                break;
            }
//        如果到了目的地
            if (!$way_face_to) {
                $face_to = 3;
                if ($this->getX() < $this->getMap()->getHomeStone()->getX()) {
                    $face_to = 4;
                }
//                射家
                $this->setFaceTo($face_to);
                $this->shootNormalBullet();
            } else {
//                如果有东西挡着
                if (!$this->move($way_face_to)) {
                    $this->shootNormalBullet();
                } else {
//                    如果没东西挡着
                    unset($way_to_base[$way_index]);
                    $this->setWayToBase($way_to_base);
                    // TODO 这里设置一个射击的概率 在Strategy里
                    $this->shootNormalBullet();
                }
            }
            $this->updateMe();
        }
    }

    /**
     * @param mixed $way_to_base
     *
     * @return PcTank
     */
    public function setWayToBase($way_to_base)
    {
        $this->way_to_base = $way_to_base;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getWayToBase()
    {
        return $this->way_to_base;
    }

    /**
     * @param mixed $p_shoot
     *
     * @return PcTank
     */
    public function setPShoot($p_shoot)
    {
        $this->p_shoot = $p_shoot;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPShoot()
    {
        return $this->p_shoot;
    }
}