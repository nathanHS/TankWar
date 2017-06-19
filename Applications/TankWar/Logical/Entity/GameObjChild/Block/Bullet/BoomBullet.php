<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/14
 * Time: 17:03
 */
class BoomBullet extends Bullet
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

    protected static $r;
    protected static $R;
    protected static $boom_edge_attack;
    protected static $boom_center_attack;

    protected $have_boom_ever = false;

    /**
     * @return boolean
     */
    public function isHaveBoomEver(): bool
    {
        return $this->have_boom_ever;
    }

    /**
     * @param boolean $have_boom_ever
     */
    public function setHaveBoomEver(bool $have_boom_ever)
    {
        $this->have_boom_ever = $have_boom_ever;
    }

    /**
     * @return mixed
     */
    public function getCenterHurtR()
    {
        return static::$r;
    }

    /**
     * @return mixed
     */
    public function getNormalHurtR()
    {
        return static::$R;
    }

    /**
     * @return mixed
     */
    public static function getBoomEdgeAttack()
    {
        return static::$boom_edge_attack;
    }

    /**
     * @return mixed
     */
    public static function getBoomCenterAttack()
    {
        return static::$boom_center_attack;
    }

    /**
     * 因为爆炸弹碰到物体直接爆炸，没有互减伤害的部分，所以直接重写move方法
     *
     * @param $po
     *
     * @return $this|bool
     */
    public function move($po)
    {
        echo "进入BoomBullet的Move\n";
        if (!$this->getDistanceLife()) {
            echo "因为距离爆炸\n";
            $this->boom();
            return $this;
        }
        if ($this->isAlive()) {
            //        获得【假设要朝着方向$po移动一步后】自己的x和y
            list($virtual_x, $virtual_y) = $virtual_x_y = $this->setFaceTo($po)->oneStep($this->getFaceTo());
//        边界
            if ($virtual_x_y == array($this->x, $this->y)) {
                echo "因为边界爆炸\n";
                $this->boom();
                return $this;
            }
            if ($bullets_blocks_hit = Bullet::isHittingObj($virtual_x, $virtual_y, $this->getWidth(), $this->getLength(), $this)) {
                echo "因为碰撞到了东西爆炸\n";
                $this->boom();
                return $this;
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
    public function boom()
    {

        echo "进入BoomBullet的boom里\n";
        if ($this->isHaveBoomEver())
            $this->destroy();

//        设置作用半径
        $R = $this->getNormalHurtR();
        $r = $this->getCenterHurtR();

//        分别代表普通伤害和核心伤害的菱形位置数组
        $normal_positions = getAllPositionOfLozenge($this->getX(), $this->getY(), $R);
        $center_positions = getAllPositionOfLozenge($this->getX(), $this->getY(), $r);

//        分别代表普通伤害和核心伤害的物体的数组
        $arr_normal_attack = array();
        $arr_center_attack = array();

//        将被伤害的物体添加进容器
        foreach ($normal_positions as list($x, $y)) {
//                超出地图范围
            if ($x < 0 || $x >= Map::getWidthStatic() || $y < 0 || $y >= Map::getLengthStatic())
                continue;
//                在地图范围内
            else {
//                    如果有碰撞的物体
                if ($hit_objects = $this->getMap()->computeCollision($x, $y, 1, 1, Block::getConcept())) {
//                        同阵营
                    /** @var Block $block */
                    $block = $hit_objects[0];
                    if ($block->getSide() == $this->getFrom()->getSide())
                        continue;
//                        如果在核心爆炸范围内
                    if (in_array(array($x, $y), $center_positions))
                        $arr_center_attack[] = $block;
//                        如果不在核心爆炸范围内
                    else {
//                            如果这个对象有一部分已经在核心爆炸范围内
                        if (in_array($block, $arr_center_attack))
                            continue;
                        else
                            $arr_normal_attack[] = $block;
                    }
                }
            }
        }

//        去重
        $arr_normal_attack = array_unique($arr_normal_attack);
        $arr_center_attack = array_unique($arr_center_attack);

//        伤害计算
        /** @var Block $each_obj */
        foreach ($arr_normal_attack as $each_obj)
            $each_obj->hurt($this->getBoomEdgeAttack(),$this);
        /** @var Block $each_obj */
        foreach ($arr_center_attack as $each_obj)
            $each_obj->hurt($this->getBoomCenterAttack(),$this);

//        设置已经爆炸过，这个变量的设置是因为防止别的子弹碰到爆炸弹，
//        然后别的子弹计算（扣爆炸弹的血），导致爆炸弹直接destroy，如果在destroy里直接爆炸可能会爆炸过又destroy。
        $this->setHaveBoomEver(true);
        $this->destroy();
    }

    /**
     * 因为怕不爆炸，也怕二次爆炸，所以重写destory方法
     *
     * @param bool $update
     *
     * @return $this
     */
    public function destroy($update = true)
    {
        $map = $this->getMap();
        if (!$this->isHaveBoomEver())
            $this->boom();
        parent::destroy($update);
    }
}