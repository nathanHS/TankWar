<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/9
 * Time: 18:53
 */
class BuildHeartStoneWallBuff extends Buff
{
    protected static $concept;
    protected static $id;
    protected static $die;
    protected static $length;
    protected static $width;

    protected static $buff_type;
    protected static $lasting_time;
    protected static $position = [[48,0],[48,2],[48,4],[50,4],[52,4],[54,4],[54,2],[54,0]];

    protected $walls = array();

    /**
     * @return array
     */
    public static function getPosition(): array
    {
        return self::$position;
    }

    /**
     * @return mixed
     */
    public static function getLastingTime()
    {
        return self::$lasting_time;
    }


    public function active()
    {
        echo "进入active\n";
        $who_get = $this->getTankGot();
        if (is_a($who_get,PcTank::class))
            return $this->destroy();

        $map = $this->getMap();
        $home_stone = $map->getHomeStone();
        if ($before_buff = $home_stone->getProtectBuff())
        {
            $before_buff->destory();
        }
        $home_stone->setProtectBuff($this);

        // 生成钢块墙
        foreach (self::getPosition() as $point){
            echo "尝试生成一道墙\n";
            list($x,$y) = $point;
            $iron_wall = $this->getMap()->makeObjectInPointForce($x,$y,IronBrick::class);
            $this->walls[] = $iron_wall;
        }

//        一定时间后变成普通砖块
        $timer_id = \Workerman\Lib\Timer::add(BuildHeartStoneWallBuff::getLastingTime(),array($this,"wallBeNormal"),array(),false);
        $this->setTimerId($timer_id);
    }



    public function wallBeNormal(){
        // 生成普通墙
        foreach (self::getPosition() as $point){
            list($x,$y) = $point;
            $this->getMap()->makeObjectInPointForce($x,$y,Brick::class);
        }
        unset($this->walls);
        $this->destroy(true);
    }

    public function destroy($update = true)
    {
        $this->getMap()->getHomeStone()->setProtectBuff(null);
        return parent::destroy($update);
    }
}