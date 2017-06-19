<?php

use Workerman\Lib\Timer;
use \DbOperate\RedisInstance;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/8
 * Time: 11:57
 */
class Map extends GameObj
{
//    静态属性
    protected static $width;
    protected static $length;
//    实体间关系(父对象、从属对象容器)
    protected $game;
    protected $all_obj = array();
    protected $home_stone;
    protected $player_tanks = array();
    protected $bullets = array();
    protected $mines = array();
    protected $pc_tanks = array();
    protected $matrix = array();
    protected $items = array();
    protected $timer_ids = array();
    protected $fire_bullet_hurt_area = array(array());
//    计数
    protected $start = 0; // 是否开启定时器（是否所有玩家都加载完成）
    protected $finish = 0;// 是否已通关
    protected $level = 0;
    protected $tanks_max_show_in_map = 0;
    protected $pc_tank_num_all;// 这关一共的电脑坦克数量
    protected $killed_tank_num_array = array();// array(uid1=>array(tank_type=>num));
    protected $all_tank_killed_num = 0;//当前总共死亡的坦克数
    protected $max_level; //最大关卡数
    protected $max_level_get_time; //最大关卡获取时间
    protected $max_level_cache_time = 300000; // 最大关卡数缓存时间


    /**
     * 静态方法获取地图宽度
     * @return mixed
     */
    public static function getWidthStatic()
    {
        return static::$width;
    }

    /**
     * 静态方法获得地图长度
     * @return mixed
     */
    public static function getLengthStatic()
    {
        return static::$length;
    }

    /**
     * 构造
     *
     * @param $attr array
     *              array $all_obj 所有对象数组（除了坦克，子弹，因为初始化的时候没有坦克和子弹）
     *              $game    Game 所对应的一场游戏实例
     */
    function __construct($attr)
    {
        parent::__construct($attr);
        if (!empty($attr["no_static"]["all_obj"])) {
            /** @var GameObj $obj */
            foreach ($attr["no_static"]["all_obj"] as $obj) {
                if ($obj->getId() == HomeStone::getId()) {
                    $this->setHomeStone($obj);
                }
                $obj->setMap($this);
                $this->addObjToMatrix($obj, $obj->getLength(), $obj->getWidth(), $obj->getX(), $obj->getY(), $obj->getConcept());
            }
        }
    }

    /**
     * 获得现场存活的PC坦克的数量
     * @return int
     */
    public function getAlivePcTankNum()
    {
        return sizeof($this->pc_tanks);
    }

    /**
     * 获得矩阵
     * @return array
     */
    public function getMatrix()
    {
        return $this->matrix;
    }

    /**
     * 返回一个地图中的所有对象
     * @return array
     */
    public function getAllObj(): array
    {
        return $this->all_obj;
    }

    /**
     * 获得所有道具对象
     * @return array
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * 获取当前地图所在的游戏实例
     * @return Game
     */
    public function getGame():Game
    {
        return $this->game;
    }

    /**
     * 通过id获取到一个坦克对象
     * @param $id
     *
     * @return Tank
     * 通过坦克id找到坦克对象
     */
    function tank($id)
    {

        if ($tank = $this->player_tanks[$id]) ;
        else
            $tank = $this->pc_tanks[$id];
        return $tank;

    }

    /**
     * 把对象添加到矩阵
     *
     * @param $obj     GameObj 添加进矩阵的对象
     * @param $length  int 长（未必是对象的长，下同）
     * @param $width   int 宽
     * @param $x       int x坐标
     * @param $y       int y坐标
     * @param $concept int 层
     */
    public function addObjToMatrix($obj, $length, $width, $x, $y, $concept)
    {
        for ($i = 0; $i < $width; $i++) {
            for ($j = 0; $j < $length; $j++) {
                $this->matrix[$concept][$i + $x][$j + $y] = $obj;
            }
        }
    }

    /**
     * 把对象从矩阵中移除
     *
     * @param      $obj GameObj
     * @param null $detail_attr
     */
    public function setObjectOutOfMatrix($obj, $detail_attr = null)
    {
        if (empty($detail_attr)) {
            for ($i = 0; $i < $obj->getWidth(); $i++) {
                for ($j = 0; $j < $obj->getLength(); $j++) {
                    if (isset($this->matrix[$obj->getConcept()][$i + $obj->getX()][$j + $obj->getY()])) {
//                        if ("$obj" == (string)$this->matrix[$obj->getConcept()][$i + $obj->getX()][$j + $obj->getY()]) {
                        //$this->matrix[$obj->getConcept()][$i + $obj->getX()][$j + $obj->getY()] = null;
                        unset($this->matrix[$obj->getConcept()][$i + $obj->getX()][$j + $obj->getY()]);
//                        }
                    }
                }
            }
        } else {
            for ($i = 0; $i < $detail_attr["width"]; $i++) {
                for ($j = 0; $j < $detail_attr["length"]; $j++) {
                    //$this->matrix[$concept][$i+$x][$j+$y] = null;
                    unset($this->matrix[$detail_attr["concept"]][$i + $detail_attr["x"]][$j + $detail_attr["y"]]);
                }
            }
        }
    }

//    索引操作

    /**
     * 添加对象到所有对象容器
     *
     * @param $obj GameObj
     */
    public function addObjToAllObjContainer(&$obj)
    {
        $this->all_obj[$obj->get_Id()] = $obj;
    }

    /**
     * 添加PC坦克到到PC坦克索引
     *
     * @param $tank Tank
     */
    public function addObjToPcTankContainer(&$tank)
    {
        $this->pc_tanks[$tank->get_Id()] = $tank;
    }

    /**
     * 添加子弹加到子弹索引
     *
     * @param $bullet Bullet
     */
    public function addObjToBulletContainer(&$bullet)
    {
        $this->bullets[$bullet->get_Id()] = $bullet;
    }

    /**
     * 添加地雷加到地雷索引
     *
     * @param $mine_bullet MineBullet
     */
    public function addObjToMineContainer($mine_bullet)
    {
        $this->mines[$mine_bullet->get_Id()] = $mine_bullet;
    }

    /**
     * 添加玩家坦克加到玩家坦克索引
     *
     * @param $tank Tank
     */
    public function addObjToPlayerTankContainer(&$tank)
    {
        $this->player_tanks[$tank->get_Id()] = $tank;
    }

    /**
     * 删除电脑坦克的电脑坦克索引
     *
     * @param $pc_tank_obj Tank
     */
    public function deletePcTankIndex($pc_tank_obj)
    {
        unset($this->pc_tanks[$pc_tank_obj->get_Id()]);
    }

    /**
     * @param $game_obj GameObj 从包含所有物体的容器里移除一个游戏元素
     */
    public function deleteAllObjIndex($game_obj)
    {
        $this->all_obj[$game_obj->get_Id()] = null;
        unset($this->all_obj[$game_obj->get_Id()]);
    }

    /**
     * 从玩家坦克索引中移除玩家坦克
     *
     * @param $player_tank_obj Tank
     */
    public function deletePlayerTankIndex($player_tank_obj)
    {
        unset($this->player_tanks[$player_tank_obj->get_Id()]);
    }

    /**
     * @param $item Item
     */
    public function deleteItemIndex($item)
    {
        $this->items[$item->get_Id()] = null;
        unset($this->items[$item->get_Id()]);
    }

    /**
     * 地雷索引中移除地雷
     *
     * @param $mine_bullet MineBullet
     */
    public function deleteMineIndex($mine_bullet)
    {
        $this->mines[$mine_bullet->get_Id()] = null;
        unset($this->mines[$mine_bullet->get_Id()]);
    }

    /**
     * 从子弹索引中移除子弹
     *
     * @param $bullet_obj Bullet
     */
    public function deleteBulletIndex($bullet_obj)
    {
        unset($this->bullets[$bullet_obj->get_Id()]);
    }

//    逻辑

    /**
     * 调试
     */
    public function varDumpMap()
    {
        echo "\n";
        for ($j = 51; $j >= 0; $j--) {
            for ($i = 0; $i <= 51; $i++) {
                if (isset($this->matrix[3][$i][$j])) {
                    echo "x";
                } else
                    echo " ";
            }
            echo "\n";
        }
    }

    /**
     * @param int   $x       物体被期望的x
     * @param int   $y       物体被期望的y
     * @param       $width   int 物体期望被理解的宽度
     * @param       $length  int 物体期望被理解的长度
     *
     * @param       $concept int 层
     *
     * @return array 碰撞检测
     * 碰撞检测
     */
    public function computeCollision($x, $y, $width, $length, $concept)
    {
        $arr = array();
        for ($i = 0; $i < $width; $i++) {
            for ($j = 0; $j < $length; $j++) {
                if (!empty($this->matrix[$concept][$i + $x][$j + $y]))
                    $arr[] = $this->matrix[$concept][$i + $x][$j + $y];
            }
        }
        return array_unique($arr);
    }

    public function debugGetAliveBrick()
    {
        /** @var GameObj $obj */
        $i = 0;
        foreach ($this->all_obj as $obj) {
            if (get_class($obj) == Brick::class) {
                $i++;
            }
        }
        return $i;
    }

    public function debugGetAliveBullet()
    {
        return sizeof($this->bullets);
    }

    /**
     * 生成PC坦克，有障碍物则不生成，如果3个生成点都有阻碍物就返回false
     *
     * @param $str
     *
     * @return bool|Tank
     */
    public function createPcTank($str = "LightTank")
    {
        /** @var Tank $obj_tank */

        // 0,48 24,48 48,48 (3个出生坐标)
        $position = array(array(50, 48), array(0, 48), array(100, 48));
        shuffle($position);

        for ($i = 0; $i < sizeof($position); $i++) {
            if (!Map::computeCollision($position[$i][0], $position[$i][1], 4, 4, 3)) {
                $obj_tank = IniParser::getInstance()->createDefault(
                    "Entity",
                    $str,
                    array(
                        "x" => $position[$i][0],
                        "y" => $position[$i][1],
                        "map" => $this,
                        "ai_type" => Strategy::getCurrentTankAiTypeByProbability($this->getLevel()),
                        "way_to_base" => FindWay::getWayToBase($position[$i][0], $this->getLevel()),
                        "p_shoot" => Strategy::getShootProbability($str, $this->getLevel())));
                $obj_tank->objAddedToMap($this)->updateMe();
                return $obj_tank;
            }
        }
        return false;
    }

    /**
     * 生成玩家坦克
     *
     * @param int $id 手动设置id（默认使用程序随机生成的id）
     * @return Tank
     */
    function createPlayerTank($id = 0)
    {
        /** @var PlayerTank $tank */
        $tank = IniParser::getInstance()->createDefault("Entity", "PlayerTank" . (sizeof($this->player_tanks) + 1), array("_id" => !$id ? uniqid() : $id, "map" => $this));
        $tank->setX($tank->getRebornPosition()[0][0])->setY($tank->getRebornPosition()[0][1])->objAddedToMap($this);
        $tank->addBuff("NoEnemyBuff")->active();
        return $tank;
    }

    /**
     * 获得当场所有的子弹的数组
     * @return array
     */
    public function getBullets(): array
    {
        return $this->bullets;
    }

    /**
     * 获得所有的PC坦克的数组
     * @return array
     */
    public function getPcTanks(): array
    {
        return $this->pc_tanks;
    }

    /**
     * 注册定时器
     */
    public function loop()
    {
//        注册地图对象管理器(生成坦克,道具等)
        $this->registerMapCreateObjMgr();
//        注册子弹定时器
        $this->registerBulletsTimer();
//        注册AI坦克定时器
        $this->registerPcTanksTimer();
//        注册道具图标定时器
        $this->registerItemTimer();
//        注册地雷监听定时器
        $this->registerMineBulletsTimer();
//        注册火焰区域检测定时器
        $this->registerFireBulletHurtAreaTimer();
    }

    /**
     * 注册子弹移动定时器
     */
    function registerBulletsTimer()
    {
        $this->timer_ids["bullets"] = Timer::add(0.2, array($this, "allBulletsMove"));
    }

    /**
     * 注册地雷监听定时器
     */
    function registerMineBulletsTimer(){
        $this->timer_ids["mine_bullets"] = Timer::add(0.1, array($this, "allMineBulletsLoop"));
    }

    /**
     * 注册PC坦克移动定时器
     */
    function registerPcTanksTimer()
    {
        $this->timer_ids["pc_tanks"] = Timer::add(0.3, array($this, "allPcTanksMove"));
    }

    /**
     * 注册火焰弹燃烧伤害以及草蔓延定时器
     */
    function registerFireBulletHurtAreaTimer(){
        $this->timer_ids["fire_bullet_area"] = \Workerman\Lib\Timer::add(FireBullet::getFireInterval()/1000, array($this, "allFireBulletHurtAreaListen"));
    }

    /**
     * 注册道具消失定时器
     */
    function registerItemTimer()
    {
        $this->timer_ids["items"] = \Workerman\Lib\Timer::add(1, array($this, "allItemsShow"));
    }

    /**
     * 注册生成道具、生成AI坦克定时器
     */
    function registerMapCreateObjMgr()
    {

        $this->timer_ids["pc_tanks_mgr"] = Timer::add(2, array($this, "createPcTankMgr"));
        $this->timer_ids["items_mgr"] = Timer::add(1, array($this, "createItemMgr"));
    }

    /**
     * 生成AI坦克
     */
    function createPcTankMgr()
    {
        echo "这关的坦克数:" . $this->pc_tank_num_all . "\n";
        echo "已经击杀的坦克数目:" . $this->all_tank_killed_num . "\n";
        if ($this->all_tank_killed_num + sizeof($this->pc_tanks) == $this->pc_tank_num_all) {
            return;
        }
        if (sizeof($this->pc_tanks) == $this->getTanksMaxShowInMap()) {
            return;
        }
//        通过策略来生成决定生成Pc坦克的类型
        $this->createPcTank(Strategy::getCurrentTankTypeByProbability());
    }

    /**
     * 所有子弹移动
     */
    function allBulletsMove()
    {
        for ($i = 0; $i < 4; $i++) {
            /** @var Bullet $bullet */
            foreach ($this->getBullets() as $bullet) {
                $bullet->move($bullet->getFaceTo());
            }
        }
    }

    /**
     * 所有地雷监听
     */
    function allMineBulletsLoop(){
        /** @var MineBullet $each_mine */
        foreach ($this->mines as $each_mine){
            $each_mine->loop();
        }
    }

    /**
     * 所有敌方坦克移动
     */
    function allPcTanksMove()
    {
        /** @var PcTank $pc_tank */
        foreach ($this->getPcTanks() as $pc_tank) {
            $pc_tank->moveByAiType();
        }
    }

    /**
     * 所有道具消失判断
     */
    function allItemsShow()
    {
        /** @var Item $item */
        foreach ($this->getItems() as $item) {
            $item->checkAlive();
        }
    }

    /**
     * 通过概率判断是否生成道具、生成什么道具
     */
    function createItemMgr()
    {
        if (random_int(1, 90) > 40) {
            $attr = array("str" => ($all_items = Item::getAllItems())[array_rand($all_items, 1)]);
            //$attr = array("str" => "MoonToothBuffItem");
            // 以下代码是调试代码
            $class_name = @file_get_contents(__DIR__."/../../super_bullet.conf");
            if ($class_name)
                $attr = array("str" => (trim($class_name)."Item"));
            // 以上代码是调试代码
            $this->createItem($attr);
        }
    }

    /**
     * 具体生成道具
     * @param array $attr 具体生成什么道具
     */
    function createItem($attr = array())
    {
        try {
            if (!isset($attr["str"]))
                $attr["str"] = "SpiderNetBuffItem"; // 设置随机道具,打算在IniParser里先把所有在xml中的类declare一边,然后就可以用get_declared_classes()了
            /** @var Item $item */
            if(!$item = IniParser::getInstance()->createDefault("Entity", $attr["str"], array("map" => $this)))
                echo $attr["str"]."道具不存在\n";
            if (!isset($attr["x"]) || !isset($attr["y"]))
                list($attr["x"], $attr["y"]) = array(random_int(0, static::getWidthStatic() - $item->getWidth()), random_int(0, static::$length - $item->getLength()));
            if (!$has_collision = $this->computeCollision($attr["x"], $attr["y"], $item->getWidth(), $item->getLength(), $item->getConcept()))
                $item->setX($attr["x"])->setY($attr["y"])->objAddedToMap($this)->updateMe();
        } catch (Exception $exception) {
            echo $exception->getMessage() . "\n" . $exception->getLine() . "\n" . $exception->getFile() ."\n";
        } catch (Error $error) {
            echo $error->getMessage() . "\n" . $error->getLine() . "\n". $error->getFile() ."\n" ;
        }
    }

    /**
     * 添加道具进道具容器
     * @param $item Item
     */
    public function addObjToItemContainer($item)
    {
        $this->items[$item->get_Id()] = $item;
    }

    /**
     * 手动销毁
     * @param string $useless_par
     *
     * @return $this
     */
    public function destroy($useless_par = "")
    {
        gc_collect_cycles();
        foreach ($this->getTimerIds() as $timer_id) {
            \Workerman\Lib\Timer::del($timer_id);
        }
        $this->getGame()->setMap();
        return $this;
    }

    /**
     * 获得定时器数组
     * @return array
     */
    public function getTimerIds(): array
    {
        return $this->timer_ids;
    }

    /**
     * 设置玩家（们）坦克
     * @param array $player_tanks
     *
     * @return Map
     */
    public function setPlayerTanks(array $player_tanks): Map
    {
        $this->player_tanks = $player_tanks;
        return $this;
    }

    /**
     * 获得玩家坦克数组
     * @return array
     */
    public function getPlayerTanks(): array
    {
        return $this->player_tanks;
    }

    /**
     * 获得关卡
     * @return int
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * 设置关卡
     * @param $level
     */
    public function setLevel($level)
    {
        $this->level = $level;
    }

    /**
     * 序列化:把当前地图转成一个数组
     * @return mixed
     */
    function currentInfoArray()
    {
//        关卡
        $content["l"] = $this->getLevel();
//        结束状态
        $content["f"] = $this->getFinish();
//        所有游戏对象
        /** @var GameObj $each_obj */
        foreach ($this->getAllObj() as $each_obj) {
            $content["g"][] = $each_obj->currentInfoArray();
        }
//        消灭几个通关
        $content["t"] = $this->getPcTankNumAll();
        foreach ($this->getKilledTankNumArray() as $uid => $kill_data) {
            foreach ($kill_data as $tank_type => $num) {
                $k_detail[$tank_type] = $num;
            }
            $k_detail["i"] = $uid;// 玩家ID
            $content["k"][] = $k_detail;// 具体击杀电脑坦克的数据
        }
        return $content;
    }

    /**
     * 获得击杀坦克数组数组
     * @return array
     */
    public function getKilledTankNumArray(): array
    {
        return $this->killed_tank_num_array;
    }

    /**
     * 总共死亡的PC坦克数加1
     */
    public function recordATankDeath()
    {
        $this->all_tank_killed_num++;
    }

    /**
     * 记录一次击杀
     * @param $who_kill string 谁杀的（id）
     * @param $who_killed_type string 杀了什么类型的对象
     */
    public function recordAPlayerKill($who_kill, $who_killed_type)
    {
        if (isset($this->killed_tank_num_array[$who_kill][$who_killed_type]))
            $this->killed_tank_num_array[$who_kill][$who_killed_type]++;
        else
            $this->killed_tank_num_array[$who_kill][$who_killed_type] = 1;
    }

    /**
     * 获取结束状态
     * @return int
     */
    public function getFinish(): int
    {
        return $this->finish;
    }

    /**
     * 获得下一关的关卡数（如果已经是最后一关，就重新开始）
     * @return int
     */
    public function getNextLevelNum()
    {
        if ($this->getLevel() + 1 > $this->getLevelMax()) return 1;
        return $this->getLevel() + 1;
    }


    /**
     * 设置结束状态
     * @param int $finish
     *
     * @return  $this
     */
    public function setFinish(int $finish)
    {
        $this->finish = $finish;
        return $this;
    }

    /**
     * 获取开始状态
     * @return int
     */
    public function getStart(): int
    {
        return $this->start;
    }

    /**
     * 设置开始状态
     * @param int $start
     *
     * @return  $this
     */
    public function setStart(int $start)
    {
        $this->start = $start;
        return $this;
    }

    /**
     * 获取家的堡垒对象
     * @return HomeStone
     */
    public function getHomeStone()
    {
        return $this->home_stone;
    }

    /**
     * 设置家的堡垒对象（相当于放进一个容器）
     * @param mixed $home_stone
     *
     * @return  $this
     */
    public function setHomeStone($home_stone)
    {
        $this->home_stone = $home_stone;
        return $this;
    }

    /**
     * 将当前关卡（两个人对战）的记录写在Redis里
     */
    public function saveData()
    {
        $user_arr = $this->getGame()->getPlayerIds();
        sort($user_arr);
//        设置最高通关关卡
        if (GameDataSaver::getMaxLevel($user_arr) < $this->getLevel()) {
            GameDataSaver::setMaxLevel($user_arr, $this->getLevel());
        }
        foreach ($user_arr as $user) {
            GameDataSaver::setTankLevelInfo($user_arr, $user, $this->getLevel(), $this->getPlayerTanks()[$user]->getSaveData());
        }
    }

    /**
     * 从Redis获取数据
     */
    public function createPlayerTankFromRecord()
    {
        $user_arr = $this->getGame()->getPlayerIds();
        sort($user_arr);
        foreach ($user_arr as $user_id) {
            /** @var PlayerTank $tank */
            $tank = $this->createPlayerTank($user_id);
            if ($data = GameDataSaver::getTankLevelInfo($user_arr, $user_id, $this->getLevel())) {

                $data = json_decode($data, true);
                if ($data["lb"]) {
                    $tank->addBuff($data["lb"]["class"]);
                }
                $tank->setHp($data["hp"]);
                $tank->setArmor($data["ar"]);
                $tank->setSpeed($data["sp"]);
                $tank->setNormalShootInterval($data["cold"]);
                $tank->setSuperBullet($data["su"]);
            }

            $tank->setNumPayFollowBullet($tank->getPayItemNumFromRedis(PayFollowBullet::class));
            $tank->setNumPayMine($tank->getPayItemNumFromRedis(PayMine::class));

            if ($user_id == "1" || $user_id == "2")
            {
                $tank->setNumPayFollowBullet(30000);
                $tank->setNumPayMine(30000);
            }
        }

    }

    /**
     * 获得当前总共死亡的坦克数
     * @return int
     */
    public function getAllTankKilledNum()
    {
        return $this->all_tank_killed_num;
    }

    /**
     * 获得这关一共的电脑坦克数量
     * @return mixed
     */
    public function getPcTankNumAll()
    {
        return $this->pc_tank_num_all;
    }

    /**
     * 设置Game对象，相当于map的上层节点
     * @param mixed $game
     *
     * @return Map
     */
    public function setGame($game)
    {
        $this->game = $game;
        return $this;
    }

    /**
     * 判断这个地方有没有地雷
     * @param $x
     * @param $y
     *
     * @return bool
     */
    public function hereHasMine($x, $y)
    {
        /** @var MineBullet $each_mine */
        foreach ($this->mines as $each_mine){
            if ($each_mine->getX() == $x && $each_mine->getY() == $y)
                return true;
        }
        return false;
    }

    /**
     * 将 燃烧位置（x，y）和燃烧弹属性（燃烧次数，伤害）注册进容器，该容器会被计时器操作。
     * 定时对容器内位置（如果在位置上有坦克）造成伤害，并确保重叠区域只一次，
     * 支持不同firebullet有不同的伤害（不支持不同间隔时间），每次取伤害最大值。
     * @param $x
     * @param $y
     * @param $fire_bullet FireBullet|array
     */
    public function addPositionToFireBulletHurtArea($x, $y, $fire_bullet)
    {
        if (is_a($fire_bullet,FireBullet::class)){
            $attack = $fire_bullet->getAttack();
            $times = $fire_bullet->getTimes();
            $id = $fire_bullet->get_Id();
        }
        else if (is_array($fire_bullet)){
            $attack = $fire_bullet["attack"];
            $times = $fire_bullet["times"];
            $id = (string)uniqid();
        }
        else{
            $attack = 0;
            $times = 0;
            $id = (string)uniqid();
        }
//        燃烧弹伤害范围数组
        $this->fire_bullet_hurt_area[$x][$y][$id] = array("attack"=>$attack,"times"=>$times);
    }

    /**
     * 所有燃烧弹作用范围监听
     */
    public function allFireBulletHurtAreaListen(){
//        初始化数组

//        有冗余数据的待处理的坦克受伤数组array(123=>[1,2]) ： id =》 array（不同伤害数值）
        $tmp_tank_hurt_array = array();
//        坦克id索引坦克对象数组
        $tmp_tank_id_obj_array = array();
//        最终结论数组，使用该数组来让数组内的坦克伤害array(123=>1)
        $tank_hurt_array = array();
//        获取已存的燃烧弹伤害范围数组
        $area_array = &$this->fire_bullet_hurt_area;
        foreach ($area_array as $x => $x_content){
            foreach ($x_content as $y => $y_content){
//                这个xy是否已经不在任何一个燃烧弹的范围内
                $delete_x_y = true;
//                草地燃烧
                $all_masks = $this->computeCollision($x,$y,1,1,Grass::getConcept());
//                如果有遮罩 且 是草地 就蔓延
                if ($all_masks && is_a($all_masks[0],Grass::class)){
                    /** @var Grass $grass */
                    $grass = $all_masks[0];
//                    如果没有燃烧过
                    if ($grass->getFireAl() == 0){
                        $fire_times = 0;
                        $attack = 0;
                        foreach ($y_content as $bullet_id => $bullet_attr){
                            if ($bullet_attr["times"] > $fire_times){
                                $fire_times = $bullet_attr["times"];
                                $attack = $bullet_attr["attack"];
                            }
                        }
//                        如果还有燃烧次数
                        if ($fire_times){
//                            把属于这个草的其他的坐标（一个草有4*4，就意味着还有15个坐标）加到监听器历来，燃烧次数设置成当前最大燃烧次数减一
//                            设置自己正在燃烧
                            $grass->setFireAl(1)->updateMe();
//                            获得除了x，y外这块草的所有点，返回值是一个x，y的矩阵元素值为1
                            $matrix = $grass->getAllPoint($x,$y);
                            foreach ($matrix as $each_x_value => $each_x_content){
                                foreach ($each_x_content as $each_y_value => $each_y_content){
                                    $this->addPositionToFireBulletHurtArea($each_x_value,$each_y_value,array("times"=>$fire_times - 1,"attack"=>$attack));
                                    //                            把【上下左右】没有燃烧的草的坐标，加入下一次定时器要检查的坐标集合中
                                    $up_down = array(1,-1);
                                    foreach ($up_down as $each_1){
                                        foreach ($up_down as $each_2){
                                            $a = $each_x_value + $each_1; $b = $each_y_value + $each_2;
//                                    如果超出边界
                                            if (!Map::pointInMap($new_x = $each_x_value + $each_1,$new_y = $each_y_value + $each_2))
                                                break;
//                                    没有超出边界
                                            else{
                                                if ($masks = $this->computeCollision($new_x, $new_y, 1, 1, Grass::getConcept())) {
                                                    if (is_a($masks[0],Grass::class))
                                                    {
                                                        /** @var Grass $new_grass */
                                                        $new_grass = $masks[0];
                                                        if ($new_grass->getFireAl() == 0){
                                                            for ($g_x = 0;$g_x < $grass->getWidth();$g_x++){
                                                                for ($g_y = 0; $g_y < $grass->getLength(); $g_y++){
                                                                    $this->addPositionToFireBulletHurtArea($new_x + $g_x,$new_y + $g_y,array("times"=>$fire_times,"attack"=>$attack));
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }


                        }
                    }
                }

//                坦克伤害
                $all_blocks =  $this->computeCollision($x,$y,1,1,Tank::getConcept());
//                如果这个xy上有阻挡 且 是坦克 且 不处于无敌状态
                if ($all_blocks && is_a($all_blocks[0],Tank::class) && !$all_blocks[0]->isNoEnemy())
                {
                    /** @var Tank $tank */
                    $tank = $all_blocks[0];
//                    添加索引
                    $tmp_tank_id_obj_array[$tank->get_Id()] = $tank;
//                    计算所有燃烧（可能有多个燃烧伤害在同一个位置，取最大值）伤害
                    foreach ($y_content as $bullet_id => $bullet_attr){
                        $attack = $bullet_attr["attack"];
                        $tmp_tank_hurt_array[$tank->get_Id()][] = $attack;
                    }
                }
//                消耗(草地燃烧处理)
                foreach ($y_content as $bullet_id => $bullet_attr){
//                    对于这个fire_bullet的燃烧次数减一
                    $area_array[$x][$y][$bullet_id]["times"]--;
//                    这个fire_bullet还有燃烧次数
                    if ($area_array[$x][$y][$bullet_id]["times"] > 0)
                        $delete_x_y = false;
//                    否则
                    else{
                        unset($area_array[$x][$y][$bullet_id]);
                    }
                }
//                如果xy不在任何燃烧弹的范围内，就把这个位置给删除
                if ($delete_x_y)
                    unset($area_array[$x][$y]);

            }
        }

//        处理出伤害最大值
        foreach ($tmp_tank_hurt_array as $id => $hurt_array){
            $tank_hurt_array[$id] = max($hurt_array);
        }
//        ------------------------------------数据初始化完毕，开始设置伤害，烧草发送消息------------------------------
        foreach ($tank_hurt_array as $id => $hurt_num){
            /** @var Tank $tank */
            $tank = $tmp_tank_id_obj_array[$id];
            $tank->hurt($hurt_num,IniParser::getInstance()->getSavedObjects()["Entity"]["FireBullet"]);
            $tank->updateMe();
        }
    }

    /**
     * 判断一个点是否在地图范围内
     * @param $x
     * @param $y
     *
     * @return bool
     */
    public static function pointInMap($x, $y){
        return $x >= 0 && $x < static::getWidthStatic() && $y >= 0 && $y < static::getLengthStatic();
    }

    /**
     * 获得最大关卡数
     * @return int
     */
    public function getLevelMax()
    {
//        缓存时间
        if (isset($this->max_level) && getMillisecond() < $this->getMaxLevelGetTime() + $this->getMaxLevelCacheTime())
            return $this->max_level;
        $this->setMaxLevelGetTime(getMillisecond());

        $xml_array = IniParser::getIni();
        $num_arr = array();
        foreach ($xml_array as $file_name => $file) {
//            level打头
            if (substr($file_name, 0, 5) != "level")
                continue;
            $num = substr($file_name, 5, strlen($file_name) - 5);
//            后面的是整数
            if (!ctype_digit($num))
                continue;
            $num_arr[] = intval($num);
        }
        $this->setMaxLevel($max = max($num_arr));
        return $max;
    }

    /**
     * @return mixed
     */
    public function getMaxLevel()
    {
        return $this->max_level;
    }

    /**
     * @param mixed $max_level
     */
    public function setMaxLevel($max_level)
    {
        $this->max_level = $max_level;
    }

    /**
     * @return mixed
     */
    public function getMaxLevelGetTime()
    {
        return $this->max_level_get_time;
    }

    /**
     * @param mixed $max_level_get_time
     */
    public function setMaxLevelGetTime($max_level_get_time)
    {
        $this->max_level_get_time = $max_level_get_time;
    }

    /**
     * @return mixed
     */
    public function getMaxLevelCacheTime()
    {
        return $this->max_level_cache_time;
    }

    /**
     * @param mixed $max_level_cache_time
     */
    public function setMaxLevelCacheTime($max_level_cache_time)
    {
        $this->max_level_cache_time = $max_level_cache_time;
    }

    /**
     * @return int
     */
    public function getTanksMaxShowInMap(): int
    {
        return $this->tanks_max_show_in_map;
    }

    /**
     * 在x，y处强制产生一个gameObj，如果有阻碍就销毁它
     * @param $x
     * @param $y
     * @param $class string
     *
     * @return GameObj
     */
    public function makeObjectInPointForce($x, $y, $class)
    {
        if ($collisions = $this->computeCollision($x,$y,$class::getWidthStatic(),$class::getLengthStatic(),$class::getConcept())){
            /** @var GameObj $collision_obj */
            foreach ($collisions as $collision_obj)
            {
                if (is_a($collision_obj,PlayerTank::class))
                {
                    $collision_obj->reborn()->setDie()->updateMe();
                    $collision_obj->setDie(false)->updateMe();
                    $collision_obj->addBuff("NoEnemyBuff")->active();
                    continue;
                }
                $collision_obj->destroy(false);
                $arr_to_client = $collision_obj->currentInfoArray();
//                如果不是坦克（是砖块），就不显示爆炸销毁效果
                if (!is_a($collision_obj,Tank::class))
                    $arr_to_client["fo"] = 1;
                $this->getGame()->addUpdateMessage($arr_to_client);
            }
        }
        $game_obj = IniParser::getInstance()->createDefault("Entity",$class,array(
            "map" => $this,
            "x" => $x,
            "y" => $y,
        ));
        $game_obj->objAddedToMap($this)->updateMe();
        return $game_obj;
    }
}