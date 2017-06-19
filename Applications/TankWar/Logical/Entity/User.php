<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/15
 * Time: 16:35
 */
use AnalysisQues\Protocol;
use DbOperate\RedisInstance;
use GatewayWorker\Lib\Gateway;

/*
 * user_session:
 * {
 * "1321231654":1
 *
 * }
 * user_info_abc:
 * {
 *      "id_card":"asdasdasd",
 *      "nick":"asdasda",
 *      "ge":"1\0"
 *      "time":"123123123",
 *      "scene":"1",
 *      "session":123123123,
 *
 * }
 * user_table_abc:
 * {
 *      "hall":"1",
 *      "num":"1",
 *      "tb_st":"1(gamer)\2(visitor)"
 *      "gaming":1,
 *      "pre":1
 *      "room_host":0,
 *      "pl":1,
 * }
 */

class User
{

    private $uid = null;
    const KEY_RIGHT = 1;
    const KEY_WRONG_OR_ID_NOT_IN_PLAT = 2;

    public function getRedis():Redis
    {
        return RedisInstance::get();
    }

    public function __construct($uid)
    {
        $this->uid = $uid;
    }

    public function createUser(array $user_info)
    {
        $redis = $this->getRedis();
//        ①
        $redis->watch("all_id_cards");
        $redis->watch("all_users");
        if ($this->hasRegistered()) {
            throw new Exception("", $code = consts::ERROR_RIGIST_REPETED_UID);
        }

        if ($redis->sIsMember("all_id_cards",$user_info["id"])) {
            throw new Exception("", $code = consts::ERROR_REPEAT_ID_CARD);
        }
//        ②
        $redis->multi();
//        补入Set中
        $this->_setUidInSET();
        $this->_setIdCardInSET($user_info["id"]);

//        录入基本信息
        $this->_setIdCard($user_info["id"]);
        /**身份证**/
        $this->setNickName($user_info["nick"]);
        /**昵称**/
        $this->setGender($user_info["ge"]);
        /**设置性别**/
        $this->_setCreateTime($user_info["time"]);
        /**设置创建时间**/
        $this->setScene(consts::SCENE_HAS_LOGIN);
        /**设置场景**/
       // Market::getMarketInstance()->savePlayerGameMoney($user_info["id"],500);
//        ③
        if (!$redis->exec()) {
            throw new Exception("", $code = consts::ERROR_TRY_AGAIN);
        }
        return consts::SUCCESS;
    }

//    获取场景
    public function getScene()
    {
        $scene = (int)$this->getRedis()->hGet("user_info_" . $this->uid, "scene");
        return $scene;
    }

//    增加身份证到身份证索引
    private function _setIdCardInSET($id_card)
    {
        $this->getRedis()->sAdd("all_id_cards", $id_card);
    }

//    增加uid到uid索引
    private function _setUidInSET()
    {
        $this->getRedis()->sAdd("all_users", $this->uid);
    }

//    设置身份证号码
    private function _setIdCard($id_card)
    {
        $this->getRedis()->hSet("user_info_" . $this->uid, "id_card", $id_card);
    }

//    设置昵称
    public function setNickName($name)
    {
        $this->getRedis()->hSet("user_info_" . $this->uid, "nick", $name);
    }
//    获取昵称
    public function getNickName()
    {
        return $this->getRedis()->hGet("user_info_" . $this->uid, "nick");
    }

//    设置性别
    public function setGender($gender)
    {
        $this->getRedis()->hSet("user_info_" . $this->uid, "ge", $gender);
    }
    public function setImage($image)
    {
        $this->getRedis()->hSet("user_table_" . $this->uid, "im", $image);
    }

//    获取性别
    public function getGender()
    {
        return (int)$this->getRedis()->hGet("user_info_" . $this->uid, "ge");
    }

//    设置创建时间
    private function _setCreateTime($time)
    {
        $this->getRedis()->hSet("user_info_" . $this->uid, "time", $time);
    }

//    设置场景
    public function setScene($scene)
    {
        $this->getRedis()->hSet("user_info_" . $this->uid, "scene", $scene);
    }

//    是否已经注册了这个账号
    public function hasRegistered()
    {
        return $this->getRedis()->sIsMember("all_users", $this->uid);
    }

//    密码校验
    public static function checkKey($plat_name, $user_id, $ssid)
    {
        $inner_key = "123456";
        $date_str = ((int)(date('G') / 5) + 1) * 5;
        $ssid = trim($ssid);
        $new_ssid = md5($date_str . $inner_key . $plat_name, $user_id);
        if ($ssid != $new_ssid) {
            throw new Exception("", $code = consts::ERROR_WRONG_KEY_OR_PWD);
        }
    }

//    判断对方是否是自己的好友
    public function isHimMyFriend(string $target_uid)
    {
        $redis = self::getRedis();
        $result = $redis->zScore("user_friends_" . $this->uid, $target_uid);
        if ($result) {
            return true;
        }
        return false;
    }

//    判断是否已经请求过好友但对方未收到
    public function alreadyHasFriendMessage($request_id)
    {
        $redis = self::getRedis();
        return $redis->hExists("friends_request_" . $this->uid, "time_" . $request_id);
    }

    public function receiveAFriendRequest(string $request_id, string $saying, $time)
    {
        $redis = $this->getRedis();
        $key = "friends_request_" . $this->uid;
        $redis->hMset($key, array("time_" . $request_id => $time, "content_" . $request_id => $saying));
        //$redis->expire($key,10*3600*24);
    }


    public function clearHisFriendRequest($request_id)
    {
        $redis = $this->getRedis();
        $redis->hDel("friend_request_" . $this->uid, "time_" . $request_id);
        $redis->hDel("friend_request_" . $this->uid, "content_" . $request_id);
    }

//    设置好友
    public function setHimMyFriend($request_uid)
    {
        $redis = $this->getRedis();
        $redis->zAdd("user_friends_" . $this->uid, time(), $request_uid);
    }

//    存储下线用户被回应
    public function receiveAFriendReply($target_uid, $y_n)
    {
        $redis = $this->getRedis();
        $redis->hSet("friends_reply_" . $this->uid, $target_uid, $y_n);
    }

//    获取全部的好友
    public function getAllFriends()
    {
        $redis = $this->getRedis();
        $all_friends_id = $redis->zRange("user_friends_" . $this->uid, 0, -1);
        $friends_array = array();
        if (is_array($all_friends_id)) {
            foreach ($all_friends_id as $f_id) {
                $user_friend = new User($f_id);
                $tmp = $user_friend->getAllInfo();
                $friends_array[] = $tmp;
            }
        }
        return $friends_array;
    }

//    上线时系统是否有消息推送
    public function hasMessage()
    {
        $friends_request = $this->hasFriendRequestMessage();
        $friends_response = $this->hasFriendsReplyMessage();
        return $friends_request || $friends_response;
    }

//    是否有好友请求推送
    public function hasFriendRequestMessage()
    {
        $redis = $this->getRedis();
        if ($redis->hLen("friends_request_" . $this->uid)) {
            return true;
        }
        return false;
    }

//    是否有加好友回应推送
    public function hasFriendsReplyMessage()
    {
        $redis = $this->getRedis();
        if ($redis->hLen("friends_reply_" . $this->uid)) {
            return true;
        }
        return false;
    }

//    处理自己上线时系统转发给自己的消息
    public function dealMessage()
    {
        $this->dealFriendRequestMessage();
        $this->dealFriendReplyMessage();
        return null;
    }

//    处理离线期间别人就自己加好友的请求
    public function dealFriendRequestMessage()
    {

        $redis = $this->getRedis();
        $all_messages = $redis->hGetAll("friends_request_" . $this->uid);
        /*
         * formatted_message:格式化后的消息
         * [
         * [111111]=>[[time]=>12313213,[content]=>asdasdasd,[uid]=>[111111]],
         * [222222]=>[[time]=>12313213,[content]=>asdasdasd,[uid]=>[222222]],
         * [333333]=>[[time]=>12313213,[content]=>asdasdasd,[uid]=>[333333]],
         * [444444]=>[[time]=>12313213,[content]=>asdasdasd,[uid]=>[444444]],
         * ]
         *
         */
        $formatted_message = array();
        $protocol = Protocol::getCurrentProtocol();
        foreach ($all_messages as $item => $value) {
            $array_item = explode("_", $item);
            $formatted_message[$array_item[1]][$array_item[0]] = $value;
            $user = new User($array_item[1]);
            $formatted_message[$array_item[1]]["user"] = $user->getAllInfo();
        }
        /*
         * $request_content:转发给current_client的content部分
         * [
         * [time]=>12313213,
         * [content]=>asdasdasd,
         * [uid]=>[111111]
         * ]
         */
        foreach ($formatted_message as $request_content) {
            $to_protocol = Protocol::createToProtocol($protocol, $request_content);
            $to_protocol->setTotalClass(consts::REQUEST_CLASS_Friend);
            $to_protocol->setLittleClass(consts::REPLY_CLASS_Friend_ADD);
            Gateway::sendToUid($this->uid, $to_protocol->encode_to_json());
        }
        /*
         * 如果该用户收到消息后不处理，这样
         * ①消息会一直堆积在redis中，
         * ②下次他再次上线后会再次推送给他
         *
         * 解决方法就是消息发送完500秒后强行把redis数据删除
         */
        \Workerman\Lib\Timer::add(500, function (User $user) {
            $user->getRedis()->del("friends_request_" . $this->uid);
        }, array($this), false);
        return null;
    }

//    处理离线期间别人对自己发出加好友请求的回应
    public function dealFriendReplyMessage()
    {
        $redis = $this->getRedis();
        /*
         * 内部key：回应者id
         * 内部value：回应者同意或者不同意
         * friends_reply_123456 =>{"1000"=>"1","1001"=>"2"}
         */
        $all_messages = $redis->hGetAll("friends_reply_" . $this->uid);
        $protocol = Protocol::getCurrentProtocol();
        foreach ($all_messages as $reply_person => $y_n) {
            $user1 = new User($this->uid);
            $user2 = new User($reply_person);
            $content = array("user1" => $user1->getAllInfo(), "y_n" => $y_n, "user2" => $user2->getAllInfo());
            $to_protocol = Protocol::createToProtocol($protocol, $content);
            $to_protocol->setTotalClass(consts::REQUEST_CLASS_Friend);
            $to_protocol->setLittleClass(consts::REPLY_CLASS_Friend_REPLY_ADD);
            Gateway::sendToUid($this->uid, $to_protocol->encode_to_json());
        }
        $redis->del("friends_reply_" . $this->uid);
        return null;
    }

//    搜索用户
    public function searchUserById($uid)
    {
        $target_user = new User($uid);
        if (!$target_user->hasRegistered()) {
            return consts::ERROR_TARGET_ID_NOT_EXISTS;
        }
        $user_info = $target_user->getAllInfo();
        return $user_info;
    }

    /**
     * 获取同桌的另一个玩家
     */
    public function getOtherTablePlayer()
    {
        $tid = $this->getTableNum();
        $hid = $this->getHall();
        $hall = new Hall($hid);
        $other_player_id = $hall->table_uid1($tid);
        if ($other_player_id == $this->getUid()) {
            $other_player_id = $hall->table_uid2($tid);
        }
        return $other_player_id;
    }

//    获取用户全部信息
    public function getAllInfo():array
    {
        $tmp = array();
        $tmp["uid"] = (string)$this->uid;
        /**用户账号**/
        $tmp["nick"] = (string)$this->getNickName();
        /**昵称**/
        $tmp["ge"] = (int)$this->getGender();
        /**性别**/
        $tmp["ol"] = (int)Gateway::isUidOnline($this->uid);
        /**是否在线**/
        $tmp["im"] = (int)$this->getImage();
        /**头像**/

        $tmp["hall"] = $this->getHall() ? $this->getHall() : "0";
        /**大厅号**/
        $tmp["tb"] = (string)$this->getTableNum();
        /**桌号**/
        $tmp["tb_st"] = (int)$this->getTableStatus();
        /**桌位上的身份（玩家OR观看者）**/
        $tmp["is_gaming"] = (int)$this->getIsGaming();
        /**是否在游戏中**/
        $tmp["prepared"] = (int)$this->getIsPrepared();
        /**是否准备**/
        $tmp["pl"] = (string)$this->getPlayerNum();
        /**玩家几**/
        $tmp["host"] = (int)$this->isRoomHost();

        return $tmp;
    }

    /**
     * 用户close连接的时候清空数据需要做的事
     */
    public function clearInfoOnClose()
    {
        $uid = $this->getUid();
        $hid = $this->getHall();
        $tid = $this->getTableNum();
        $pl = $this->getPlayerNum();
        $this->setScene(consts::SCENE_BEFORE_EVERYTHING);
        if ($hid) {
            Gateway::leaveGroup(Gateway::getClientIdByUid($uid)[0], "hall_" . $hid);
//                    清除用户的大厅数据
            $this->clearHall();
        }
        $hall = new Hall($hid);
//                清除大厅中的用户数据
        $hall->removeOnlineUser($uid);
        $hall->removeTableVisitor($tid, $uid);
        $hall->removeTablePlayer($tid, $uid);
        if ($hall->isTableEmpty($tid)) {
            $hall->clearTable($tid);
        } else {
//                    换房主
            $other_player_id = "ERROR";
            if ($pl == 1) {
                $other_player_id = $hall->getTablePlayer2($tid);
            }
            if ($pl == 2) {
                $other_player_id = $hall->getTablePlayer1($tid);
            }
            $hall->setUserHost($tid, $other_player_id);
            $other_player = new User($other_player_id);
            $other_player->setHallHost();
            $protocol_to = Protocol::createToNewProtocol(consts::REQUEST_CLASS_HALL, consts::CLASS_HALL_PEOPLE_FLUSH, $other_player->getAllInfo());
            Gateway::sendToGroup("hall_" . $hid, $protocol_to->encode_to_json());
        }
        $protocol_to = Protocol::createToNewProtocol(consts::REQUEST_CLASS_HALL, consts::CLASS_HALL_PEOPLE_FLUSH, $this->getAllInfo());
        Gateway::sendToGroup("hall_" . $hid, $protocol_to->encode_to_json());
    }

//    进入大厅
    public function enterHall(Hall $hall)
    {
        /*
       * hall_info_1(全部的uid) {123,12332,412,4123124}
       * hall_free_user_1(不在座位上的用户){123,12332}
       * hall_1_table_1_visitors(具体座位数据) （集合）{a，b}（数字表示座位）
       * hall_1_table_1_info :{"gaming":1,"free_time":100,uid1:123,uid2:234}
       *  user_info_abc:
       * {
       *      "id_card":"asdasdasd",
       *      "nick":"asdasda",
       *      "ge":"1\0"
       *      "time":"123123123",
       *      "scene":"1",
       * }
       * user_table_abc:
       * {
       *      "hall":"1",
       *      "num":"1",
       *      "tb_st":"1(gamer)\2(visitor)"
       *      "gaming":1,
       *      "pre":1
       * }
       */
        $redis = $this->getRedis();
        $redis->multi();
//        设置场景
        $this->setScene(consts::SCENE_HALL);
//        设置用户所在的大厅
        $this->setHall($hall->getHid());
//        添加大厅内的在线用户(并把用户归为闲置用户)
        $hall->addOnlineUser($this->uid);

        $redis->exec();
    }

//    获取所在的大厅号
    public function getHall()
    {
        $redis = $this->getRedis();
        $result = $redis->hGet("user_table_" . $this->uid, "hall");
        return $result;
    }

//    获取用户桌号
    public function getTableNum()
    {
        $redis = $this->getRedis();
        return (int)$redis->hGet("user_table_" . $this->uid, "num");
    }

//    获取用户桌子上的身份（观战者还是玩家）
    public function getTableStatus()
    {
        $redis = $this->getRedis();
        return (int)$redis->hGet("user_table_" . $this->uid, "tb_st");
    }

//    获取用户是否在游戏中
    public function getIsGaming()
    {
        $redis = $this->getRedis();
        return (int)$redis->hGet("user_table_" . $this->uid, "gaming");
    }

//    获取用户是否准备
    public function getIsPrepared()
    {
        $redis = $this->getRedis();
        return (int)$redis->hGet("user_table_" . $this->uid, "pre");
    }

//    设置用户所在的大厅
    public function setHall($hid)
    {
        $this->getRedis()->hSet("user_table_" . $this->uid, "hall", $hid);
    }


    public function tellMyFriendMyInfo()
    {
        $friends = $this->getAllFriendsId();
        if (is_array($friends)) {
            $info = $this->getAllInfo();
            $protocol = Protocol::createToProtocol(Protocol::getCurrentProtocol(), $info);
            $protocol->setTotalClass(consts::REQUEST_CLASS_Friend);
            $protocol->setLittleClass(consts::REPLY_CLASS_FRIEND_INFO_FLUSH);
            foreach ($friends as $uid) {
                if (Gateway::isUidOnline($uid)) {
                    Gateway::sendToUid($uid, $protocol->encode_to_json());
                }
            }
        }

    }

//    获取所有好友的uid
    public function getAllFriendsId()
    {
        $redis = $this->getRedis();
        return $redis->zRange("user_friends_" . $this->uid, 0, -1);
    }

//    将用户从大厅移除
    public function leaveHall()
    {
        /*
       * hall_info_1(全部的uid) {123,12332,412,4123124}
       * hall_free_user_1(不在座位上的用户){123,12332}
       * hall_1_table_1_visitors(具体座位数据) （集合）{a，b}（数字表示座位）
       * hall_1_table_1_info :{"gaming":1,"free_time":100,uid1:123,uid2:234}
       *  user_info_abc:
       * {
       *      "id_card":"asdasdasd",
       *      "nick":"asdasda",
       *      "ge":"1\0"
       *      "time":"123123123",
       *      "scene":"1",
       * }
       * user_table_abc:
       * {
       *      "hall":"1",
       *      "num":"1",
       *      "tb_st":"1(gamer)\0(visitor)"
       *      "gaming":1,
       *      "pre":1
       *      "pl":1(1号player还是2号player)
       * }
       */

        if ($hid = $this->getHall()) {
            $hall = new Hall($hid);
//           大厅移除在线玩家
            $hall->removeOnlineUser($this->uid);
//           用户清除大厅数据
            $this->clearHall();

            $protocol = Protocol::getCurrentProtocol();
            $protocol_to = $protocol->createToProtocol($protocol, $this->getAllInfo());
            $protocol_to->setTotalClass(consts::REQUEST_CLASS_HALL);
            $protocol_to->setLittleClass(consts::CLASS_HALL_PEOPLE_FLUSH);
            Gateway::sendToGroup("hall_" . $hid, $protocol_to->encode_to_json());
        }
    }


    public function clearHall()
    {
        $redis = $this->getRedis();
        return $redis->del("user_table_" . $this->uid);
    }

    public function getPlayerNum()
    {
        return (int)$this->getRedis()->hGet("user_table_" . $this->uid, "pl");
    }

    public function getImage()
    {
        return (int)$this->getRedis()->hGet("user_table_" . $this->uid, "im");
    }

//    进入桌子
    public function sitDownAs($tid, $pl, $as)
    {


//        把自己设置成玩家或者观战者
        $this->setSelfAs($as);
//        设置桌号
        $this->setTable($tid);
//        设置自己的座位
        $this->setSittingTablePlace($pl);


        $hall = new Hall($this->getHall());
        $hall->setOnlinePeoplePlayer($tid, $this->uid);
        if ($as) {
            $hall->setTablePlayer($tid, $this->uid, $pl);
        } else {
            $hall->setTableVisitor($tid, $this->uid);
        }

    }


//    设置自己为游戏玩家
    public function setSelfAs($player_or_visitor)
    {
        $this->getRedis()->hSet("user_table_" . $this->uid, "tb_st", $player_or_visitor);
    }

//    设置自己为游戏观战者
    public function setSelfVisitor()
    {
        $this->getRedis()->hSet("user_table_" . $this->uid, "tb_st", 0);
    }

//    设置自己的座位
    public function setSittingTablePlace($pl)
    {
        $this->getRedis()->hSet("user_table_" . $this->uid, "pl", $pl);
    }

//    设置桌号
    public function setTable($tid)
    {
        $this->getRedis()->hSet("user_table_" . $this->uid, "num", $tid);
    }

//    设置session
    public function updateSession($str)
    {
        $this->removeSession();
        $this->getRedis()->multi();
        $this->getRedis()->hSet("user_info_" . $this->uid, "session", $str);
        $this->getRedis()->hSet("user_session", $str, $this->uid);
        $this->getRedis()->exec();
    }

//    清除对方的好友请求
    public function removeOthersFriendRequest($request_uid)
    {
        $this->getRedis()->hDel("friends_request_" . $this->uid, $request_uid);
    }

//    获得连接session
    public function getSession()
    {
        return $this->getRedis()->hGet("user_info_" . $this->uid, "session");
    }

//    清空连接session
    public function removeSession()
    {
        $session = $this->getRedis()->hGet("user_info_" . $this->uid, "session");
        $this->getRedis()->multi();
        $this->getRedis()->hDel("user_info_" . $this->uid, "session");
        $this->getRedis()->hDel("user_session", $session);
        $this->getRedis()->exec();
    }

//    设置房主
    public function setHallHost()
    {
        $this->getRedis()->hSet("user_table_" . $this->uid, "room_host", 1);
    }

    public function isRoomHost()
    {
        return (int)$this->getRedis()->hGet("user_table_" . $this->uid, "room_host");
    }

//    离开座位
    public function leaveTable()
    {
        if ($hid = $this->getHall()) {
            $this->getRedis()->del("user_table_" . $this->uid);
            $this->setHall($hid);
        }
    }

//    设置准备
    public function setPrepare()
    {
        $this->getRedis()->hSet("user_table_" . $this->uid, "pre", 1);
    }

//    设置为游戏状态
    public function setGaming($flag = 1)
    {
        $this->getRedis()->hSet("user_table_" . $this->uid, "gaming", $flag);
    }

//    取消准备
    public function cancelPrepare()
    {
        $this->getRedis()->hSet("user_table_" . $this->uid, "pre", 0);
    }

    /**
     * @return null
     */
    public function getUid()
    {
        return $this->uid;
    }


}
