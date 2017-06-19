<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/11/12
 * Time: 18:25
 */
use AnalysisQues\Analyzer;
use \GatewayWorker\Lib\Gateway;

class Game
{
//    实体间从属关系(静态自身容器,从属对象)
    public static $all_games_index_id;
    public static $all_games_index_player_id;
    private $map;
    protected $timer_ids = array();


    private $id = "";
    private $group_id = "";
    private $players = array();
    private $visitors = array();
    private $update_message = array();

    /**
     * Game constructor.
     *
     * @param $uid_array array 玩家列表
     * @param $group_id  mixed 广播组id
     * @param $level     int 关卡
     */
    public function __construct($uid_array, $group_id, $level)
    {
        $this->id = "";
        foreach ($uid_array as $uid) {
            $this->id .= (string)$uid;
            $this->players[$uid] = array();
            static::$all_games_index_player_id[$uid] = $this;
        }
        $this->group_id = $group_id;
        $this->visitors = array();
        self::$all_games_index_id[$this->id] = $this;
        $this->setMap(
            IniParser::getInstance()->createDefault("Entity", "Map", array(
                    "all_obj" => IniParser::getInstance()->fromMapInfoCreateAllMapObj((4 % $level) + 1),
                    "game" => $this,
                    "level" => $level,
                    "tanks_max_show_in_map" => Strategy::getMaxTankInMap($level),
                    "pc_tank_num_all" => Strategy::getLevelTankNum($level)
                )
            ));
        $this->getMap()->createPlayerTankFromRecord();
    }

    function registerInfoFlusher()
    {
        $this->timer_ids["info_flusher"] = \Workerman\Lib\Timer::add(0.1, array($this, "flushUpdateInfo"));
    }

    public function loop()
    {
        $this->registerInfoFlusher();
    }

    public function addVisitor($uid){
        if (!in_array($uid,$this->getVisitors())){
            $this->visitors[] = $uid;
        }
    }

    /**
     * 获取另一个玩家的uid
     * @param $uid
     *
     * @return string
     */
    public static function getOtherPlayer($uid)
    {
        $game = static::findGameByPlayerId($uid);
        $other_player_arr = array_filter($game->getPlayerIds(), function ($value) use ($uid) {
            return $value != $uid;
        });
        return $other_player_arr[0];
    }

    /**
     * 获得所有玩家
     * @return mixed
     */
    public function &getPlayers():array
    {
        return $this->players;
    }


    public function getPlayerIds()
    {
        $result = array();
        foreach ($this->getPlayers() as $each_player_id => $each_player_info) {
            $result[] = $each_player_id;
        }
        return $result;
    }

    /**
     * 获得所有游戏对象的容器（索引是游戏id）
     * @return mixed
     */
    public static function getAllGamesIndexId()
    {
        return self::$all_games_index_id;
    }

    /**
     * 获得游戏id
     * @return int|string
     */
    public function getGameId()
    {
        return $this->id;
    }

    /**
     * 获得广播组的id
     * @return int
     */
    public function getGroupId()
    {
        return $this->group_id;
    }

    /**
     * 获得当前的地图
     * @return Map
     */
    public function getMap(): Map
    {
        if (is_string($this->map)){
            echo "map是 string:" . $this->map;
        }
        return $this->map;
    }

    /**
     * 根据游戏的id号找到游戏实例
     *
     * @param $id
     *
     * @return Game
     * @throws Exception
     */
    public static function findGameById($id)
    {
        $game = self::$all_games_index_id[$id];
        if (!$game) {
            throw new Exception("No such a game!\n");
        }
        return $game;
    }

    /**
     * 根据玩家的id号找到游戏实例
     *
     * @param $uid
     *
     * @return Game
     * @throws Exception
     */
    public static function findGameByPlayerId($uid)
    {
        $game = "";
        if (isset(static::$all_games_index_player_id[$uid])) {
            $game = static::$all_games_index_player_id[$uid];
        }
        if (!$game) {
            echo "没找到{$uid}的game\n";
        }
        return $game;
    }

    /**
     * 将对象【信息（不是引用）】加到准备更新队列中
     *
     * @param $obj GameObj
     */
    public function addUpdateInfo($obj)
    {
        if ($obj) {
            $this->update_message[] = $obj->currentInfoArray();
        }
    }

    /**
     * 清空update数据并传送信息
     *
     * @param null $exclude 除了谁不发送
     */
    public function flushUpdateInfo($exclude = null)
    {
        if ($this->update_message) {
            Gateway::sendToGroup($this->group_id,\AnalysisQues\Protocol::createToNewProtocol(consts::CLASS_GAME, consts::GAME_INFO_FLUSH, $this->update_message)->encode_to_json(), $exclude);
            $this->update_message = array();
        }
        //echo "没有发更新包\n";
    }

    /**
     * 发送当前所有信息给玩家
     */
    public function flushAllInfo()
    {
        Gateway::sendToGroup($this->getGroupId(), \AnalysisQues\Protocol::createToNewProtocol(consts::CLASS_GAME, consts::GAME_CURRENT_ALL_INFO, $this->currentInfoArray())->encode_to_json());
    }

    public function flushPlayersPrepared()
    {
        \GatewayWorker\Lib\Gateway::sendToGroup($this->getGroupId(), \AnalysisQues\Protocol::createToNewProtocol(consts::CLASS_GAME, consts::CLASS_GAME_ONE_PLAYER_PREPARE_GAME, array())->encode_to_json());
    }

    /**
     * 当前游戏数据
     * @return array
     */
    public function currentInfoArray()
    {
        $content = array("p" => $this->getPlayerIds(), "v" => $this->visitors, "m" => $this->getMap()->currentInfoArray());
        return $content;
    }

    function destroy()
    {
//        2表示游戏结束
        $this->getMap()->setFinish(2);
        $this->flushAllInfo();
        $this->getMap()->destroy();
        foreach ($this->timer_ids as $timer_id) {
            \Workerman\Lib\Timer::del($timer_id);
        }
        unset(static::$all_games_index_id[$this->getGameId()]);
        foreach ($this->getPlayerIds() as $id) {
            unset(static::$all_games_index_player_id[$id]);
            $user = new User($id);
            $user->setScene(consts::SCENE_HALL);
            $user->setGaming(0);
            $user->cancelPrepare();


            $hall = new Hall($user->getHall());
            $hall->setTableStartGame($user->getTableNum(), 0);

            $to_protocol = \AnalysisQues\Protocol::createToNewProtocol(
                consts::SCENE_HALL,
                consts::CLASS_HALL_PEOPLE_FLUSH,
                $user->getAllInfo());
            Gateway::sendToGroup("hall_" . $user->getHall(), $to_protocol->encode_to_json());

            if (!Gateway::isUidOnline($id)){
                $_SESSION["uid"] = $id;
                Analyzer::dealUserClose();
                unset($_SESSION["uid"]);
            }
            echo "在Game里,设置不再游戏,设置取消准备\n";
        }
        foreach ($this->getVisitors() as $id){
            \AnalysisQues\Analyzer::clearVisitor($id);
        }
//        解散广播组
        $group_users = Gateway::getClientInfoByGroup($this->getGroupId());
        foreach ($group_users as $each_one => $each_one_info) {
            Gateway::leaveGroup($each_one, $this->getGroupId());
        }

        return $this;
    }

    /**
     * @param null|string $map
     *
     * @return Game
     */
    public function setMap($map = "")
    {
        $this->map = $map;
        return $this;
    }

    public function changeToNextLevel($level = "")
    {
//        在old_map调用destroy，全局唯一的old_map引用为destroy的返回值。
        $old_map = $this->getMap()->destroy();
        $player_tanks = $old_map->getPlayerTanks();
//        重生死亡坦克
        if (sizeof($player_tanks) != 2) {
            foreach ($this->getPlayerIds() as $playerId) {
                if (!isset($player_tanks[$playerId])) {
                    $old_map->createPlayerTank($playerId);
                }
            }
        }

        /** @var Map $new_map */
        $level = $level ? $level : $old_map->getNextLevelNum();
        $new_map = IniParser::getInstance()->createDefault("Entity", "Map", array(
            "all_obj" => IniParser::getInstance()->fromMapInfoCreateAllMapObj((4 % $level) + 1),
            "game" => $this,
            "player_tanks" => ($player_tanks = $old_map->getPlayerTanks()),
            "level" => $level,
            "tanks_max_show_in_map" => Strategy::getMaxTankInMap($level),
            "pc_tank_num_all" => Strategy::getLevelTankNum($level)
        ));
        echo "进入第".$level."关\n";
        /** @var Tank $player_tank */
        foreach ($player_tanks as $player_tank) {
            $player_tank->setMap($new_map)->addBuff("NoEnemyBuff")->active();
            $new_map->addObjToAllObjContainer($player_tank);
            if (!$new_map->computeCollision(44, 0, $player_tank->getWidth(), $player_tank->getLength(), $player_tank->getConcept())) {
                $player_tank->setX(44)->setY(0);
            } else
                $player_tank->setX(56)->setY(0);
            $new_map->addObjToMatrix($player_tank, $player_tank->getLength(), $player_tank->getWidth(), $player_tank->getX(), $player_tank->getY(), $player_tank->getConcept());
        }
        $this->setMap($new_map);
        $this->flushAllInfo();

    }
    /**
     * 给一个玩家设置某个属性为true
     *
     * @param $id
     *
     * @param $detail_info
     *
     * @return $this
     */
    public function registerPlayerOneDetailInfo($id, $detail_info)
    {
        if (key_exists($id, $this->getPlayers())) {
            $this->getPlayers()[$id][$detail_info] = true;
        }
        return $this;
    }

    /**
     * 是否所有玩家的某个属性都未true
     *
     * @param $detail_info
     *
     * @return bool
     */
    public function allPlayersDetailInfoRegistered($detail_info)
    {
        $players_info = $this->getPlayers();
        foreach ($players_info as $uid => $each_player_info) {
//            在线且没有决定下一关
            if (Gateway::isUidOnline($uid) && empty($each_player_info[$detail_info])) {
                return false;
            }
        }
        return true;
    }

    /**
     * 将所有玩家的某个属性都设为false
     *
     * @param $detail_info
     *
     * @return $this
     */
    public function clearAllPlayerOneDetailInfo($detail_info)
    {
        foreach ($this->players as &$each_player_info) {
            $each_player_info[$detail_info] = false;
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getVisitors(): array
    {
        return $this->visitors;
    }

    /**
     * @param array $update_message
     */
    public function addUpdateMessage(array $update_message)
    {
        $this->update_message[] = $update_message;
        $this->flushUpdateInfo();
    }


}

