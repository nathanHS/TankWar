<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/12/16
 * Time: 11:03
 */
class KillAllBuff extends Buff
{
    protected static $concept;
    protected static $id;
    protected static $die;
    protected static $length;
    protected static $width;

    protected static $buff_type;

    function active()
    {
        parent::active();
        $map = $this->getMap();
        if ($this->getTankGot()->getSide() != PlayerTank::getSideStatic())
            return;
        else
            $target_tanks = $map->getPcTanks();
        /** @var Tank $each_tank */
        foreach ($target_tanks as $each_tank){
            if (!$each_tank->isNoEnemy()){
                $each_tank->destroy();
            }
        }
    }
}