<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/12/16
 * Time: 9:16
 */
class SpiderNetBuff extends Buff
{
    protected static $concept;
    protected static $id;
    protected static $die;
    protected static $length;
    protected static $width;

    protected static $buff_type;
    protected static $continue_time;

    /**
     * 设置对方阵营坦克不可以动，一定时间后设为可以移动
     * @return $this
     */
    public function active()
    {
        $map = $this->getMap();
        $target_tanks = $map->getPlayerTanks();
        if ($this->getTankGot()->getSide() == PlayerTank::getSideStatic()){
            $target_tanks=$map->getPcTanks();
        }

        /** @var Tank $tank */
        foreach ($target_tanks as $tank){
            $tank->setCanMove(false)->setCanShoot(false)->setCanShootSuperBullet(false);
        }
        $this->loop();
        return $this;
    }

    /**
     * 目标坦克都变为可移动，变为可以发射子弹
     */
    public function invalid(){
        $map = $this->getMap();
        $target_tanks = $map->getPlayerTanks();
        if ($this->getTankGot()->getSide() == PlayerTank::getSideStatic()){
            $target_tanks=$map->getPcTanks();
        }

        /** @var Tank $tank */
        foreach ($target_tanks as $tank){
            $tank->setCanMove(true)->setCanShoot(true)->setCanShootSuperBullet(true);
        }
        parent::invalid();
    }


    public function loop()
    {
        $timer_id = \Workerman\Lib\Timer::add(1, function () {
            if ($this->isOverTime()) {
                $this->destroy();
            }
        });
        return $this->setTimerId($timer_id);
    }

    public function isOverTime()
    {
        return getMillisecond() > $this->getGetTime() + $this->getContinueTime();
    }


    /**
     * @return mixed
     */
    public function getContinueTime()
    {
        return $this->continue_time ?? static::$continue_time;
    }
}