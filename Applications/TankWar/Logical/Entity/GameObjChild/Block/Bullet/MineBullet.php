<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/9
 * Time: 19:07
 */
class MineBullet extends Bullet
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


    protected static $speed_slow;
    protected static $speed_slow_time;
    protected static $r;

    /**
     * 放下一颗地雷（假定已经通过了子弹数量等各种限制的检验）于坦克中心，
     * 设置放置时间（最后一次射击），
     *
     * @param $tank Tank
     * @return null
     */
    public static function setMine($tank)
    {
//        如果该位置已经有地雷了
        $map = $tank->getMap();
        if ($map->hereHasMine($x = ($tank->getX() + $tank->getWidth()/2 - 1), $y = ($tank->getY() + $tank->getLength()/2 - 1))){
            return null;
        }
//        特殊子弹减一 并 设置放置时间
        $super_bullet = $tank->getSuperBullet();
        $super_bullet["num"]--;
        if ($super_bullet["num"] <= 0)
            $super_bullet = array();
        $tank->setSuperBullet($super_bullet);
        $tank->setLastSuperShootTime(getMillisecond());
        MineBullet::MineCreate($tank,$x,$y);
    }

    /**
     * 实际构造一个地雷的操作，不包括各种检验与设置坦克的参数值（最后一次射击时间等）
     * @param $tank Tank
     * @param $x
     * @param $y
     */
    public static function MineCreate($tank, $x, $y){

        //        构造地雷
        /** @var MineBullet $mine_bullet */
        $mine_bullet = IniParser::getInstance()->createDefault("Entity",__CLASS__,array("face_to" => $tank->getFaceTo(),
            "map" => ($map = $tank->getMap()), "from" => $tank,
            "x" => $x,
            "y" => $y,
        ));
        $mine_bullet->updateMe();
//        添加进容器，以便定时器可以找到它并让它监听是否有东西中雷
        $map->addObjToMineContainer($mine_bullet);
    }

    /**
     * @return mixed
     */
    public static function getLengthStatic()
    {
        return static::$length;
    }

    /**
     * @return mixed
     */
    public static function getWidthStatic()
    {
        return static::$width;
    }

    /**
     * @param bool $update
     * @return $this
     */
    function destroy($update = true)
    {
        parent::destroy($update);
        $this->getMap()->deleteMineIndex($this);
        return $this;
    }

    /**
     * 检测是否有敌方坦克碰到地雷，如果有就爆炸
     */
    public function loop(){
        $map = $this->getMap();
//        没有人踩到
        if(!$all_objects = $map->computeCollision($this->x,$this->y,$this->getWidth(),$this->getLength(),Block::getConcept()))
            return null;
        /** @var Tank $object */
//        友军踩到
        $object = $all_objects[0];
        if ($object->getSide() == $this->getFrom()->getSide())
            return null;
//        计算爆炸范围
        $R = $this->getR();
//        容纳菱形坐标的容器
        $positions=getAllPositionOfLozenge($this->getX(),$this->getY(),$R);
//        将被伤害的物体添加进容器
        $arr_normal_attack = array();
//        对菱形范围遍历
        foreach ($positions as list($x,$y)){
//                超出地图范围
                if ($x < 0 || $x >= Map::getWidthStatic() || $y < 0 || $y >= Map::getLengthStatic())
                    continue;
//                在地图范围内
                else{
//                    如果有碰撞的物体
                    if ($hit_objects = $this->getMap()->computeCollision($x, $y, 1, 1, Block::getConcept())){
//                        同阵营
                        /** @var Block $block */
                        $block = $hit_objects[0];
                        if ($block->getSide() == $this->getFrom()->getSide())
                            continue;
                        else
                            $arr_normal_attack[] = $block;
                    }
                }
        }
//        对受伤单位去重
        $arr_normal_attack = array_unique($arr_normal_attack);
//        对筛选出来的目标：伤害+减速定时器
        foreach ($arr_normal_attack as $object){
            $object->hurt($this->getAttack(),$this);
            if ($object->isAlive() && !$object->isNoEnemy() && is_a($object,Tank::class)){
                $object->addMineSlowDownTimer($this);
            }
        }
//        炸完了，该把自己销毁了
        $this->destroy();
    }
    /**
     * @return mixed
     */
    public function getR()
    {
        return $this->r??static::$r;
    }
    /**
     * @param mixed $r
     *
     * @return $this
     */
    public function setR($r)
    {
        $this->r = $r;
        return $this;
    }
    /**
     * @return mixed
     */
    public function getSpeedSlow()
    {
        return $this->speed_slow??static::$speed_slow;
    }
    /**
     * @param mixed $speed_slow
     *
     * @return $this
     */
    public function setSpeedSlow($speed_slow)
    {
        $this->speed_slow = $speed_slow;
        return $this;
    }
    /**
     * @return mixed
     */
    public function getSpeedSlowTime()
    {
        return $this->speed_slow_time??static::$speed_slow_time;
    }
    /**
     * @param mixed $speed_slow_time
     *
     * @return $this
     */
    public function setSpeedSlowTime($speed_slow_time)
    {
        $this->speed_slow_time = $speed_slow_time;
        return $this;
    }


}