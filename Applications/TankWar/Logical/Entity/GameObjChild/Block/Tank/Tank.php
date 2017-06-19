<?php

require_once __DIR__ . "/../../../../Logical.php";

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/8
 * Time: 15:57
 * @property int ice_move_step
 * @property int ice_effect_timer_id
 */
class Tank extends Block
{
    protected static $concept;
    protected static $id;
    protected static $die;
    protected static $length;
    protected static $width;

    protected static $hp;
    protected static $side;
    protected static $is_no_enemy;
    protected static $can_move;
    protected static $can_shoot;

//    跟踪子弹列表（当自己每移动一下就通知跟踪子弹）
    protected $follow_bullets_array = array();
//    可以射击特殊子弹
    protected $can_shoot_super_bullet = true;
//    移动速度
    protected static $speed;
//    护甲
    protected static $armor;
//    护甲上限
    protected static $armor_top_limit;
//    普通子弹射击间隔
    protected static $normal_shoot_interval;
//    上次射击时间
    protected static $last_shoot_time = 0;
//    上次的特殊子弹射击时间
    protected $last_super_shoot_time = 0;
//    朝向
    protected static $face_to;

    protected $ice_effect_timer;
    protected $mine_slow_down_timer;
    protected $super_bullet = array("bullet_type" => "FireBullet", "num" => 5000);
    protected $num_pay_follow_bullet = 0;
    protected $num_pay_mine = 0;
    protected $loop_buff = array();
    protected $life_buff;
    protected $super_shoot_interval = 1000;

    /**
     * @param mixed $normal_shoot_interval
     *
     * @return $this Tank
     */
    public function setNormalShootInterval($normal_shoot_interval)
    {
        $this->normal_shoot_interval = $normal_shoot_interval;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getNormalShootInterval()
    {
        return $this->normal_shoot_interval??static::$normal_shoot_interval;
    }

    public static function getNormalShootIntervalStatic()
    {
        return static::$normal_shoot_interval;
    }

    /**
     * @param mixed $speed
     *
     * @return $this
     */
    public function setSpeed($speed)
    {
        $this->speed = $speed;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSpeed()
    {
        return $this->speed??static::$speed;
    }

    /**
     * @return mixed
     */
    public function getArmor()
    {
        return $this->armor?? static::$armor;
    }

    /**
     * @param mixed $armor
     */
    public function setArmor($armor)
    {
        $this->armor = $armor;
    }


    /**
     * @return mixed
     */
    public function getCanShoot()
    {
        return $this->can_shoot??self::$can_shoot;
    }

    /**
     * @param mixed $can_shoot
     *
     * @return  $this
     */
    public function setCanShoot($can_shoot)
    {
        $this->can_shoot = $can_shoot;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCanMove()
    {
        return $this->can_move??self::$can_move;
    }

    /**
     * @param mixed $can_move
     *
     * @return  $this
     */
    public function setCanMove($can_move)
    {
        $this->can_move = $can_move;
        return $this;
    }

    public static function getSideStatic()
    {
        return static::$side;
    }


//    移动
    /**
     * @param      $po  int 移动的方向
     *
     * @return mixed
     */
    public function move($po)
    {
        try {
            $this->getTerrainEffect();
            if (!$this->getCanMove()) {
                echo "在Tank里,不能move\n";
                return false;
            }
//        获得【假设要朝着方向$po移动一步后】自己的x和y
            list($virtual_x, $virtual_y) = $virtual_x_y = $this->setFaceTo($po)->oneStep($this->getFaceTo());
//        边界
            if ($virtual_x_y == array($this->x, $this->y))
                return false;
//        本层可碰撞体
            $collision_obj = $this->getMap()->computeCollision($virtual_x, $virtual_y, $this->getWidth(), $this->getLength(), $this->getConcept());
            $id = $this->get_Id();
            $block_collision = array_filter($collision_obj, function ($var) use ($id) {
                return $var->get_Id() != $id;
            });
//            水面
            $terrain_collision = array();
            if (empty($this->getLifeBuff()) || get_class($this->getLifeBuff()) != SheepBuff::class) {
                $collision_obj = $this->getMap()->computeCollision($virtual_x, $virtual_y, $this->getWidth(), $this->getLength(), Terrain::getConcept());
                $terrain_collision = array_filter($collision_obj, function ($var) use ($id) {
                    return $var->getId() == WaterFace::getId();
                });
            }
            if ($block_collision || $terrain_collision)
                return false;
            else {
                $this->getMap()->setObjectOutOfMatrix($this);
                $this->oneStep($this->getFaceTo(), false);
                $this->getMap()->addObjToMatrix($this, $this->getLength(), $this->getWidth(), $this->x, $this->y, $this->getConcept());
            }
            $this->dealWithItem();
            $this->tellFollowBulletIMoved($po);
            return $this;
        } catch (Exception $e) {
            echo $e->getLine();
            echo $e->getTraceAsString();
            echo $e->getMessage(). $e->getFile() ."\n";
        } catch (Error $e) {
            echo $e->getLine();
            echo $e->getTraceAsString();
            echo $e->getMessage(). $e->getFile() ."\n";

        }

    }

    /**
     * @return array
     * 返回一个数组，标记需要返回给客户端的元素信息
     */
    public function currentInfoArray()
    {
        $arr = parent::currentInfoArray();
//        护甲
        $arr["ar"] = $this->getArmor();
//        朝向
        $arr["po"] = $this->getFaceTo();
//        速度
        $arr["sp"] = $this->getSpeed();
//        普通炮弹的冷却时间
        $arr["cold"] = $this->getNormalShootInterval();
//        跟踪单的数量
        $arr["pay_fol"] = $this->getPayFollowBulletNum();
//        地雷堆的数量
        $arr["pay_min"] = $this->getPayMineNum();

        if ($su = $this->getSuperBullet()) {
            $int_class = IniParser::getInstance()->getSavedObjects()["Entity"][$su["bullet_type"]]->getId();
            $arr["su"] = $su;
            $arr["su"]["id"] = $int_class;
            // $arr["id"] = $int_class;
        }
        if ($life_buff = $this->getLifeBuff()) {
            $arr["lb"] = $this->getLifeBuff()->currentInfoArray();
        }
        if ($this->isNoEnemy()) {
            foreach ($this->loop_buff as $buff) {
                if (get_class($buff) == "NoEnemyBuff") {
                    $arr["ne"] = $buff->currentInfoArray();
                    break;
                }
            }
        }

        return $arr;
    }

    /**
     *
     */
    public function dealWithItem()
    {
        //        道具层
        $items_here = $this->getMap()->computeCollision($this->x, $this->y, $this->getWidth(), $this->getLength(), 4);
        foreach ($items_here as $item) {
            /** @var Item $item */
            $this->eatItem($item);
            $item->destroy();
        }
    }

    /**
     * @return int
     * 获取坦克当前朝向
     */
    public function getFaceTo()
    {
        return $this->face_to??static::$face_to;
    }

    /**
     * @param int $face_to
     *
     * @return Tank
     */
    public function setFaceTo($face_to)
    {
        $this->face_to = $face_to;
        return $this;
    }

    /**
     * @param $item
     */
    function eatItem($item)
    {
        $item_name = get_class($item);
        $create_obj_name = explode("Item", $item_name)[0];
        if (($obj_name = substr($create_obj_name, -4)) == "Buff" && class_exists($create_obj_name)) {
            if ($buff = $this->addBuff($create_obj_name)) {
                $buff->active();
            }

        }

        if (($obj_name = substr($create_obj_name, -6)) == "Bullet" && class_exists($create_obj_name))
            $this->addSuperBullet($create_obj_name);
        $this->getMap()->getGame()->flushUpdateInfo();
    }

    /**
     * @param $str
     *
     * @return Buff|null
     */
    public function addBuff($str)
    {
        /** @var Buff $buff */
        $buff = IniParser::getInstance()->createDefault("Entity", $str, array("tank_got" => $this, "get_time" => getMillisecond(), "map" => $this->getMap()));
        if (!$buff) {
            return null;
        }
        if ($buff->getBuffType() == NoEnemyBuff::getBuffTypeStatic()) {
            /** @var Buff $each_loop_buff */
            foreach ($this->getLoopBuff() as $each_loop_buff) {
                if (get_class($buff) == get_class($each_loop_buff))
                    $each_loop_buff->destroy();
            }
            $this->addBuffToLoopBuff($buff);
        } else if ($buff->getBuffType() == Buff::getBuffTypeStatic()) {
            /** @var Buff $life_buff */
            if ($life_buff = $this->getLifeBuff())
                $life_buff->destroy();
            $this->setLifeBuff($buff);

        }
        $buff->updateMe();
        return $buff;
    }

    /**
     *
     */
    public function deleteLifeBuff()
    {
        unset($this->life_buff);
    }

    /**
     * @param $str
     */
    public function addSuperBullet($str)
    {
        $tmp_bullet = IniParser::getInstance()->getSavedObjects()["Entity"][$str];
        $this->super_bullet = array("bullet_type" => $str, "num" => $tmp_bullet->getNumber());
        $this->setSuperShootInterval($tmp_bullet->getSuperShootInterval());

    }

    /**
     * @return bool|Bullet|null 发射失败 或者 子弹对象
     * 发射一颗普通的子弹
     */
    public function shootNormalBullet()
    {
        if (!$this->getCanShoot()) {
            return null;
        }
        /** @var Bullet $bullet */
        $map = $this->getMap();

//        间隔检测
        if (getMillisecond() < $this->getLastShootTime() + $this->getNormalShootInterval()) {
            return false;
        }
//        构造子弹
        $this->setLastShootTime(getMillisecond());

        /*
         * 子弹基础属性
         */
        return $this->constructBullet("Bullet",$this->getMap());
////        根据子弹生成时，不同坦克朝向相对于坦克左下角坐标的xy偏移量。
//        $effect = array(array(1, 3), array(1, 0), array(0, 1), array(3, 1))[$this->getFaceTo() - 1];
////        根据偏移量计算子弹坐标
//        $x_y = array_map(function ($vp_each, $ac_each) {
//            return $vp_each + $ac_each;
//        }, array($this->x, $this->y), $effect);
////        长
//        $length = 1;
////        宽
//        $width = 1;
//        if ($this->getFaceTo() == 1 || $this->getFaceTo() == 2) {
//            $width = 2;
//        } else {
//            $length = 2;
//        }
//
//
//        if (!Bullet::isHittingObj($x_y[0], $x_y[1], $width, $length, $this)) {
//            $bullet = IniParser::getInstance()->createDefault("Entity", "Bullet",
//                array("face_to" => $this->getFaceTo(),
//                    "map" => $map, "from" => $this,
//                    "x" => $x_y[0],
//                    "y" => $x_y[1],
//                    "width" => $width,
//                    "length" => $length
//                ));
//            $bullet->objAddedToMap($map)->updateMe();
//            return $bullet;
//        }
//        return null;
    }

    /**
     * 发射特殊子弹
     *
     * @return Bullet|null
     */
    public function shootSuperBullet()
    {
//        getCanShoot 集成了原值检查，超过时间间隔检查，有特殊子弹检查，数量检查
        if (!$this->getCanShootSuperBullet()) {
            echo "不能发射特殊子弹！\n";
            return null;
        }

        $bullet_type = $this->getSuperBullet()["bullet_type"];
        $this->setSuperShootInterval(IniParser::getInstance()->getSavedObjects()["Entity"][$bullet_type]->getSuperShootInterval());
        if ($bullet_type == "MineBullet") {
            MineBullet::setMine($this);
            return null;
        }
//        特殊子弹减少一个
        $this->super_bullet["num"]--;
        if ($this->super_bullet["num"] <= 0) {
            $this->setSuperBullet(array());
        }
        /** @var Bullet $bullet */
        $map = $this->getMap();
        if ($this->constructBullet($bullet_type, $map))
//          构造子弹
            $this->setLastSuperShootTime(getMillisecond());
        else
            return null;

    }

    /**
     * 发射跟踪子弹
     * @return null
     */
    public function shootPayFollowBullet()
    {
//        getCanShoot 集成了原值检查，超过时间间隔检查，有特殊子弹检查，数量检查
        if (!$this->getCanShootPayFollowBullet()) {
            echo "不能发射跟踪子弹！\n";
            return null;
        }

        $bullet_type = "PayFollowBullet";
        $this->setSuperShootInterval(IniParser::getInstance()->getSavedObjects()["Entity"][$bullet_type]->getSuperShootInterval());

//        特殊子弹减少一个
        $this->setNumPayFollowBullet($this->getPayFollowBulletNum() - 1);

        /** @var PayFollowBullet $bullet */
        $map = $this->getMap();
        $face_to = $this->getFaceTo();
        $tmp_target_tank = null;
        foreach ($this->getMap()->getPcTanks() as $each_tank) {
            if ($tmp_face_to = $this->theGameObjectPositionOfMe($each_tank)) {
                $tmp_target_tank = $each_tank;
                $face_to = $tmp_face_to;
                break;
            }
        }
        foreach ($bullets = $this->constructPayFollowBullet($bullet_type, $map, $face_to) as $bullet) {
            if ($tmp_target_tank)
                $bullet->followTheNearestTank($tmp_target_tank);
        }
        $this->setLastSuperShootTime(getMillisecond());
    }

    /**
     * @param  int $x
     * @param  int $y
     * 放置一个地雷堆（在水面或是该位置有地雷的点上无法放置）
     * @return null
     */
    public function payMineSet($x, $y)
    {
        /** @var array $all_position_possible */
        $all_position_possible = getAllPositionOfLozenge($x, $y, PayMine::getSetMineNumberStatic());
        shuffle($all_position_possible);

        $positions = array();
        for ($i = 0; $i < count($all_position_possible) / 3; $i++) {
            $positions[] = array_shift($all_position_possible);
        }

//        对于半径范围内的坐标，每一个都生成一个地雷
        $map = $this->getMap();
        foreach ($positions as $position) {
            list($x,$y) = $position;
//            如果这个位置没有出界 且 没有地雷
            if (Map::pointInMap($x,$y) && !$map->hereHasMine($x,$y)){
                $collision_obj = $map->computeCollision($x,$y,MineBullet::getWidthStatic(),MineBullet::getLengthStatic(),Block::getConcept());
                $block_collision = array_filter($collision_obj, function ($element){
//                       碰撞体既不是坦克，又不是子弹，只能是墙
                    return !is_a($element,Tank::class) && !is_a($element,Bullet::class);
                });
//                   如果这个位置不是墙
                if (!$block_collision){
                    $collision_obj = $map->computeCollision($x,$y,MineBullet::getWidthStatic(),MineBullet::getLengthStatic(),Terrain::getConcept());
                    $terrain_collision = array_filter($collision_obj, function ($element){
                        return is_a($element,WaterFace::class);
                    });
//                       如果这个位置上不是水面
                    if (!$terrain_collision){
                        MineBullet::MineCreate($this, $x, $y);
                    }
                }
            }
        }
    }

    public function shootPayMineBullet()
    {
//        getCanShoot 集成了原值检查，超过时间间隔检查，有特殊子弹检查，数量检查
        if (!$this->getCanSetPayMine()) {
            echo "不能发射跟踪子弹！\n";
            return null;
        }

        $bullet_type = "PayMine";
        $this->setSuperShootInterval(IniParser::getInstance()->getSavedObjects()["Entity"][$bullet_type]->getSuperShootInterval());

//        特殊子弹减少一个
        $this->setNumPayMine($this->getPayMineNum() - 1);

        /** @var PayFollowBullet $bullet */
        $map = $this->getMap();

        if ($bullet = $this->constructBullet($bullet_type, $map)) {
            $this->setLastSuperShootTime(getMillisecond());
        }
    }
//    public function payMineSet()
//    {
////        getCanShoot 集成了原值检查，超过时间间隔检查，数量检查
//        if (!$this->getCanSetPayMine()) {
//            echo "不能发射地雷堆弹！\n";
//            return null;
//        }
//        echo "放置地雷堆\n";
//        $this->setNumPayMine($this->getPayMineNum() - 1);
//        $this->setLastSuperShootTime(getMillisecond());
//
////        计算需要放置的地雷的半径
//        $r = (int)sqrt(PayMine::getSetMineNumberStatic());
//        echo "半径为" . $r . "\n";
//        $center_x = $this->getX() + $this->getWidth()/2 - 1;
//        $center_y = $this->getY() + $this->getLength()/2 - 1;
//
////        对于半径范围内的坐标，每一个都生成一个地雷
//        $map = $this->getMap();
//        for ($x = $center_x - $r;$x <= $center_x + $r; $x++) {
//            for ($y = $center_y - $r; $y <= $center_y + $r; $y++) {
//                echo "在x=" . $x . " y=" . $y . " 处:";
////                如果这个位置没有出界 且 没有地雷
//                if (Map::pointInMap($x,$y) && !$map->hereHasMine($x,$y)){
//                    echo "此处是在地图里,并且这个地方没有放置地雷;";
//                    $collision_obj = $map->computeCollision($x,$y,MineBullet::getWidthStatic(),MineBullet::getLengthStatic(),Block::getConcept());
//                    $block_collision = array_filter($collision_obj, function ($element){
////                        碰撞体既不是坦克，又不是子弹，只能是墙
//                        return !is_a($element,Tank::class) && !is_a($element,Bullet::class);
//                    });
////                    如果这个位置不是墙
//                    if (!$block_collision){
//                        echo "并且此处不是墙壁;";
//                        $collision_obj = $map->computeCollision($x,$y,MineBullet::getWidthStatic(),MineBullet::getLengthStatic(),Terrain::getConcept());
//                        $terrain_collision = array_filter($collision_obj, function ($element){
//                            return is_a($element,WaterFace::class);
//                        });
////                        如果这个位置上不是水面
//                        if (!$terrain_collision){
//                            echo "并且此处不是水面;\n";
//                            MineBullet::MineCreate($this, $x, $y);
//                        }
//                    }
//                }
//            }
//        }
//    }

    /**
     * 设置子弹初始位置，长宽，是否碰撞如碰撞则不生成，不碰撞则生成子弹对象
     *
     * @param $bullet_type string
     * @param $map         Map
     *
     * @return GameObj|null
     */
    public function constructBullet($bullet_type, $map)
    {
        /*
         * 子弹基础属性
         */
//        根据子弹生成时，不同坦克朝向相对于坦克左下角坐标的xy偏移量。
        $effect = array(array(1, 3), array(1, 0), array(0, 1), array(3, 1))[$this->getFaceTo() - 1];
//        根据偏移量计算子弹坐标
        $x_y = array_map(function ($vp_each, $ac_each) {
            return $vp_each + $ac_each;
        }, array($this->x, $this->y), $effect);
//        长
        $length = 1;
//        宽
        $width = 1;
        if ($this->getFaceTo() == 1 || $this->getFaceTo() == 2) {
            $width = 2;
        } else {
            $length = 2;
        }
        /** @var Bullet $bullet */
        $bullet = IniParser::getInstance()->createDefault("Entity", $bullet_type,
                array("face_to" => $this->getFaceTo(),
                    "map" => $map,
                    "from" => $this,
                    "x" => $x_y[0],
                    "y" => $x_y[1],
                    "width" => $width,
                    "length" => $length
                ));
        //echo "创造的子弹的face_to是".$bullet->getFaceTo()."\n";
        $bullet->objAddedToMap($map)->updateMe();
        return $bullet;
    }

    public function constructPayFollowBullet($bullet_type, $map,$face_to = -1)
    {
        /*
         * 子弹基础属性
         */
//        根据子弹生成时，不同坦克朝向相对于坦克左下角坐标的xy偏移量。
        if ($face_to == -1) $face_to = $this->getFaceTo();
        $effect = array(array(1, 3), array(1, 0), array(0, 1), array(3, 1))[$face_to - 1];
//        根据偏移量计算子弹坐标
        $x_y = array_map(function ($vp_each, $ac_each) {
            return $vp_each + $ac_each;
        }, array($this->x, $this->y), $effect);
//        长
        $length = 1;
//        宽
        $width = 1;

        if ($face_to == 1 || $face_to == 2) {
            $width = 2;
            $x1 = $x_y[0] - 1;
            $x2 = $x_y[0] + 1;
            $y1 = $y2 = $x_y[1];
        } else {
            $length = 2;
            $y1 = $x_y[1] - 1;
            $y2 = $x_y[1] + 1;
            $x1 = $x2 = $x_y[0];
        }

        $bullet_1 = IniParser::getInstance()->createDefault("Entity", $bullet_type,
            array("face_to" => $face_to,
                "map" => $map,
                "from" => $this,
                "x" => $x1,
                "y" => $y1,
                "width" => $width,
                "length" => $length
            ));
        $bullet_2 = IniParser::getInstance()->createDefault("Entity", $bullet_type,
            array("face_to" => $face_to,
                "map" => $map,
                "from" => $this,
                "x" => $x2,
                "y" => $y2,
                "width" => $width,
                "length" => $length
            ));
        $bullet_1->objAddedToMap($map)->updateMe();
        $bullet_2->objAddedToMap($map)->updateMe();

        return array($bullet_1,$bullet_2);

    }

    /**
     * @param Map $map
     *
     * @return $this
     */
    public function objAddedToMap($map)
    {
        parent::objAddedToMap($map);
        $map->addObjToPcTankContainer($this);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getArmorTopLimit()
    {
        return $this->armor_top_limit??static::$armor_top_limit;
    }

    /**
     * @param mixed $armor_top_limit
     */
    public function setArmorTopLimit($armor_top_limit)
    {
        $this->armor_top_limit = $armor_top_limit;
    }

    /**
     * @return mixed
     */
    public function getSuperShootInterval()
    {
        return $this->super_shoot_interval;
    }
//
//    /**
//     * @param mixed $super_shoot_interval
//     */
//    public function setSuperShootInterval($super_shoot_interval)
//    {
//        $this->super_shoot_interval = $super_shoot_interval;
//    }

    /**
     * @return mixed
     */
    public function getLastShootTime()
    {
        return $this->last_shoot_time??static::$last_shoot_time;
    }

    /**
     * @param mixed $last_shoot_time
     */
    public function setLastShootTime($last_shoot_time)
    {
        $this->last_shoot_time = $last_shoot_time;
    }

    /**
     * @return array
     */
    public function getLoopBuff(): array
    {
        return $this->loop_buff;
    }

    /**
     * @param $buff Buff
     */
    public function addBuffToLoopBuff($buff)
    {
        $this->loop_buff[(string)$buff] = $buff;
    }

    /**
     * @param $buff
     */
    public function deleteBuffInLoopBuff($buff)
    {
        unset($this->loop_buff[(string)$buff]);
    }

    /**
     * @return Buff
     */
    public function getLifeBuff()
    {
        return $this->life_buff;
    }

    /**
     * @param mixed $life_buff
     *
     * @return $this
     */
    public function setLifeBuff($life_buff)
    {
        $this->life_buff = $life_buff;
        return $this;
    }

    /**
     * @param $num
     * @param $who_cause Bullet
     *
     * @return bool
     */
    public function hurt($num, $who_cause = null)
    {
        $this->setGetHurt(true);
        if ($who_cause) $this->setHurtFrom($who_cause->getId());
        else $this->setHurtFrom(-1);

//        如果是子弹造成的伤害
        if (is_a($who_cause, Bullet::class)) {
//            如果有月牙 且 子弹不是穿甲弹
            if ($this->getLifeBuff() && is_a($this->getLifeBuff(), MoonToothBuff::class) && !is_a($who_cause, ThroughBullet::class)) {
//                如果子弹正好和坦克方向反向
                if ($this->getFaceTo() - $who_cause->getFaceTo() == 1 || $this->getFaceTo() - $who_cause->getFaceTo() == -1) {
                    return false;
                }
            }
        }
        if ($this->isNoEnemy()) {
            return false;
        }
        if ($this->getLifeBuff()) {
            $this->getLifeBuff()->destroy();
            return false;
        }
        if ($this->getArmor() > 0) {
            $this->setArmor($this->getArmor() - $num < 0 ? 0 : $this->getArmor() - $num);
            return false;
        } else {
            $this->setHp($this->getHp() - $num);
            $this->resetAttr();
        }

        if ($this->getHp() <= 0) {
            $this->setDie();
            $this->destroy();
        }
        return true;
    }

//    重置属性
    function resetAttr()
    {
        $this->setSpeed(static::$speed);
        $this->setNormalShootInterval(static::$normal_shoot_interval);
//        $this->super_bullet = "";
//        $this->setSuperShootInterval(static::$super_shoot_interval);
        $this->setCanMove(static::$can_move);
        $this->setCanShoot(static::$can_shoot);
    }

//    血量增加
    function hpUp($num)
    {
        $this->setHp($this->getHp() + $num);
        return $this;
    }

//    增加护甲
    public function shieldUp($num)
    {
        $this->setArmor($this->getArmor() + $num);
        if ($this->getArmor() > $this->getArmorTopLimit()) {
            $this->setArmor($this->getArmorTopLimit());
        }
        return $this;
    }

    static function getSpeedStatic()
    {
        return static::$speed;
    }

//    增加速度
    function moveUp($num)
    {

        $this->setSpeed($this->getSpeed() + $num);
        if ($this->getSpeed() > PlayerTank::getSpeedStatic() + 2 * MoveUpBuff::getMoveUpStatic()) {
            $this->setSpeed(PlayerTank::getSpeedStatic() + 2 * MoveUpBuff::getMoveUpStatic());
        }
        if ($this->getSpeed() < 1) {
            $this->setSpeed(1);
        }
        return $this;
    }

    /**
     * @param $num
     *
     * @return $this
     */
    function shootIntervalDown($num)
    {
        if ($this->getNormalShootInterval() <= static::getNormalShootIntervalStatic() - 3 * ShootIntervalDownBuff::getShootIntervalTimeDownStatic()) {
            return $this;
        }
        return $this->setNormalShootInterval($this->getNormalShootInterval() - $num);
    }

    private function getTerrainEffect()
    {
        $all_terrains = $this->getMap()->computeCollision($this->getX(), $this->getY(), $this->getWidth(), $this->getLength(), Terrain::getConcept());
        $terrains = array();
//        过滤重复地形
        /** @var Terrain $terrain */
        foreach ($all_terrains as $terrain) {
            if (!in_array(get_class($terrain), $terrains))
                $terrains[] = $terrain;
        }
        /** @var Terrain $terrain */
        foreach ($terrains as $terrain) {
            $terrain->effect($this);
        }
    }

    /**
     * @param mixed $ice_effect_timer
     *
     * @return Tank
     */
    public function setIceEffectTimer($ice_effect_timer)
    {
        $this->ice_effect_timer = $ice_effect_timer;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIceEffectTimer()
    {
        return $this->ice_effect_timer;
    }


    /**
     * 序列化
     * @return string
     */
    public function getSaveData()
    {
        $arr = $this->currentInfoArray();
        return json_encode($arr);
    }

    /**
     * @param mixed $super_bullet
     *
     * @return Tank
     */
    public function setSuperBullet($super_bullet)
    {
        $this->super_bullet = $super_bullet;
        return $this;
    }

    /**
     * @return mixed
     */
    public function &getSuperBullet()
    {
        return $this->super_bullet;
    }

    /**
     * 获取是否可以射击特殊子弹
     */
    public function getCanShootSuperBullet()
    {
        if (!$this->can_shoot_super_bullet) {
            echo "因为属性值";
        }
        if (!$this->getSuperBullet()) {
            echo "因为没有特殊子弹";
        }

        return $this->can_shoot_super_bullet
        && $this->getSuperBullet()
        && $this->getSuperBullet()["num"] > 0
        && getMillisecond() > $this->getLastSuperShootTime() + $this->getSuperShootInterval();
    }

    /**
     * 获取是否可以射击跟踪子弹
     */
    public function getCanShootPayFollowBullet()
    {

        if (!$this->can_shoot_super_bullet) {
            echo "因为属性值";
        }
        if (($num = $this->getPayFollowBulletNum()) <= 0) {
            echo "因为没有跟踪子弹";
        }

        return $this->can_shoot_super_bullet
        && $num > 0
        && getMillisecond() > $this->getLastSuperShootTime() + $this->getSuperShootInterval();
    }

    public function getCanSetPayMine(){
        if (!$this->can_shoot_super_bullet) {
            echo "因为属性值";
        }
        if (!($num = $this->getPayMineNum())) {
            echo "因为没有地雷堆";
        }

        return $this->can_shoot_super_bullet
        && $num > 0
        && getMillisecond() > $this->getLastSuperShootTime() + $this->getSuperShootInterval();
    }

    /**
     * @param boolean $can_shoot_super_bullet
     *
     * @return Tank
     */
    public function setCanShootSuperBullet(bool $can_shoot_super_bullet): Tank
    {
        $this->can_shoot_super_bullet = $can_shoot_super_bullet;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLastSuperShootTime()
    {
        return $this->last_super_shoot_time;
    }

    /**
     * @param mixed $last_super_shoot_time
     */
    public function setLastSuperShootTime($last_super_shoot_time)
    {
        $this->last_super_shoot_time = $last_super_shoot_time;
    }

    /**
     * @param int $super_shoot_interval
     *
     * @return Tank
     */
    public function setSuperShootInterval(int $super_shoot_interval): Tank
    {
        $this->super_shoot_interval = $super_shoot_interval;
        return $this;
    }

    /**
     * @param $mine_bullet MineBullet
     */
    public function addMineSlowDownTimer($mine_bullet)
    {
//        动态变量：减速定时器何时结束
        $this->mine_slow_down_end_time = getMillisecond() + $mine_bullet->getSpeedSlowTime();

//        如果不正在减速中则，否则啥也不做（定时器内不会动态改变定时器结束时间）
        if (!$this->mine_slow_down_timer) {
            $this->mine_slow_down_timer = \Workerman\Lib\Timer::add(
                0.1, function ($tan, $speed_should_slow) {
                /** @var Tank $tank */
                $tank = $tan;
//                如果超过了时间了
                if (isset($tank->mine_slow_down_end_time) && getMillisecond() > $tank->mine_slow_down_end_time) {
                    unset($tank->mine_slow_down_end_time);
                    \Workerman\Lib\Timer::del($tank->getMineSlowDownTimer());
                    if (isset($is_slow_it_down) && $is_slow_it_down == true) {
                        $tank->moveUp($speed_should_slow);
                    }
                    return null;
                } else {
                    static $is_slow_it_down = false;
                    if (!$is_slow_it_down) {
                        $tank->moveUp(-1 * $speed_should_slow);
                    }
                    $is_slow_it_down = true;
                }
            }, array($this, $mine_bullet->getSpeedSlow()));
        }
    }

    /**
     * @return mixed
     */
    public function getMineSlowDownTimer()
    {
        return $this->mine_slow_down_timer;
    }

    /**
     * @param mixed $mine_slow_down_timer
     */
    public function setMineSlowDownTimer($mine_slow_down_timer)
    {
        $this->mine_slow_down_timer = $mine_slow_down_timer;
    }

    /**
     *  注册超级子弹进超级子弹列表
     *
     * @param $bullet PayFollowBullet
     */
    public function registerFollowBullets($bullet)
    {
        $this->getFollowBulletsArray()[$bullet->get_Id()] = $bullet;
    }

    /**
     * @return array
     */
    public function &getFollowBulletsArray(): array
    {
        return $this->follow_bullets_array;
    }

    /**
     * 告诉跟踪自己的每一个跟踪弹，自己移动了
     *
     * @param $po int
     */
    public function tellFollowBulletIMoved($po)
    {
        /** @var PayFollowBullet $each_bullet */
        foreach ($this->getFollowBulletsArray() as $each_bullet) {
            $each_bullet->tankMoved($po);
        }
    }

    public function getPayFollowBulletNum()
    {
        return $this->num_pay_follow_bullet;
    }



    /**
     * @param int $num_pay_follow_bullet
     */
    public function setNumPayFollowBullet(int $num_pay_follow_bullet)
    {
        $this->num_pay_follow_bullet = $num_pay_follow_bullet;
    }

    /**
     * @return int
     */
    public function getPayMineNum()
    {
        return $this->num_pay_mine;
    }

    /**
     * @param int $num_pay_mine
     * @return Tank
     */
    public function setNumPayMine($num_pay_mine)
    {
        $this->num_pay_mine = $num_pay_mine;
        return $this;
    }




}

