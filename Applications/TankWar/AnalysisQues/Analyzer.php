<?php
namespace AnalysisQues;

use Channel\Client;
use consts;
use DbOperate\RedisInstance;
use GatewayWorker\Lib\Gateway;
use Hall;
use User;
use Workerman\Worker;

class Analyzer
{


    /**
     * 解析入口
     * @return Protocol|null|void
     * @throws \Exception
     */
    public static function analyze()
    {
        $protocol = Protocol::getCurrentProtocol();
        $protocol->checkContent();
        $total_class = $protocol->getTotalClass();
        $little_class = $protocol->getLittleClass();
        //       绑定uid
        $require1 = array_key_exists("uid", Gateway::getSession($_SERVER["GATEWAY_CLIENT_ID"]));
        //echo "require1 == ".intval($require1);
        //       大类是用户
        $require2 = $total_class == \consts::REQUEST_CLASS_USER;
        //echo "require2 == ".intval($require2);
        //       小类不登录或注册
        $require3 = $little_class == \consts::REQUEST_CLASS_USER_REGIST || $little_class == \consts::REQUEST_CLASS_USER_LOGIN;
        //echo "require3 == " . intval($require3) . "\n";
        //       如果有已经有uid或者是登录操作或注册操作

        if ($require1 || ($require2 && $require3) || 1) {
            switch ($protocol->getTotalClass()) {
                case consts::REQUEST_CLASS_USER:
                    return self::doUserBusiness();
                case consts::REQUEST_CLASS_Friend:
                    return self::doFriendBusiness();
                case consts::REQUEST_CLASS_HALL:
                    return self::doHallBusiness();
                case consts::CLASS_GAME:
                    return self::dealGameBusiness();
                default:
                    throw new \Exception(consts::CLOSE_CONNECTION, $code = \consts::ERROR_HAS_NO_BIG_CLASS);
            }
        } else {
            throw new \Exception(consts::CLOSE_CONNECTION, consts::ERROR_LOGIN_PLEASE);
        }
    }

    /**
     * 处理用户大类
     * @return Protocol|null|void
     * @throws \Exception
     */
    public static function doUserBusiness()
    {

        $protocol = Protocol::getCurrentProtocol();
        $little_class_id = $protocol->getLittleClass();
        switch ($little_class_id) {
            case \consts::REQUEST_CLASS_USER_LOGIN:
                return self::dealLogin();
            case \consts::REQUEST_CLASS_USER_REGIST:
                return self::dealRegister();
            case \consts::REQUEST_CLASS_USER_LOGOUT:
                return self::dealLogout();
            case \consts::REQUEST_CLASS_USER_SEARCH_BY_UID:
                return self::dealSearch();
            case \consts::REQUEST_CLASS_USER_MY_INFO:
                return self::dealMyInfo();
            case \consts::REQUEST_CLASS_USER_SET_INFO:
                return self::dealSetMyInfo();
            default:
                throw new \Exception(consts::CLOSE_CONNECTION, consts::ERROR_HAS_NO_LITTLE_CLASS);
        }
    }

    /**
     * 处理好友大类
     * @return Protocol|null
     * @throws \Exception
     */
    public static function doFriendBusiness()
    {
        $protocol = Protocol::getCurrentProtocol();
        $little_class_id = $protocol->getLittleClass();

        switch ($little_class_id) {
//            请求添加好友
            case \consts::REQUEST_CLASS_Friend_ADD:
                return self::dealFriendAdd();
//            对添加好友请求响应
            case \consts::REQUEST_CLASS_Friend_REPLY_ADD;
                return self::dealReplyAdd();
//            请求好友数据
            case \consts::REQUEST_CLASS_Friend_List_Request:
                return self::dealFriendList();
            case \consts::REQUEST_CLASS_FRIEND_TALK:
                return self::dealTalkToFriend();
            default:
                throw new \Exception(consts::CLOSE_CONNECTION, consts::ERROR_HAS_NO_LITTLE_CLASS);
        }
    }

    /**
     * 处理大厅大类
     * @return Protocol|null
     * @throws \Exception
     */
    private static function doHallBusiness()
    {
        $protocol = Protocol::getCurrentProtocol();
        $little_class_id = $protocol->getLittleClass();

        switch ($little_class_id) {
            case \consts::REQUEST_CLASS_HALL_CURRENT_INFO:
                return self::dealHallCurrentInfo();
            case \consts::REQUEST_CLASS_HALL_CHOOSE_HALL:
                return self::dealChooseHall();
            case consts::CLASS_HALL_CHOOSE_TABLE:
                return self::dealChooseTable();
            case consts::CLASS_HALL_USER_LEAVE_TABLE:
                return self::dealLeaveTable();
            case consts::CLASS_HALL_USER_PREPARE:
                return self::doUserPrepare();
            case consts::CLASS_HALL_USER_ANTI_PREPARE:
                return self::doUserAntiPrepare();
            case consts::CLASS_HALL_KICK_OUT_PEOPLE:
                return self::dealHostKickPlayer();
            case consts::CLASS_HALL_NEW_VISITOR_IN:
                return self::dealObserveGame();
            default:
                throw new \Exception(consts::CLOSE_CONNECTION, consts::ERROR_HAS_NO_LITTLE_CLASS);
        }
    }

    /**
     * 处理游戏大类
     * @return null
     */
    private static function dealGameBusiness()
    {
        $protocol = Protocol::getCurrentProtocol();
        $little_class = $protocol->getLittleClass();
        $content = $protocol->getContent();

        switch ($little_class) {
            case consts::CLASS_GAME_LEVEL: {
                return Client::publish(consts::CLASS_GAME_LEVEL, self::dealGameLevelChoose());
            }
            case consts::CLASS_GAME_MOVE: {
                $content["id"] = $_SESSION["uid"];
                return Client::publish(consts::CLASS_GAME_MOVE, $content);
            }
            case consts::CLASS_GAME_SHOOT_BULLET: {
                return Client::publish(consts::CLASS_GAME_SHOOT_BULLET, $_SESSION["uid"]);
            }
            case consts::CLASS_GAME_SHOOT_SUPER_BULLET: {
                return Client::publish(consts::CLASS_GAME_SHOOT_SUPER_BULLET, $_SESSION["uid"]);
            }
            case consts::CLASS_GAME_SHOOT_PAY_FOLLOW_BULLET: {
                return Client::publish(consts::CLASS_GAME_SHOOT_PAY_FOLLOW_BULLET, $_SESSION["uid"]);
            }
            case consts::CLASS_GAME_SHOOT_PAY_MINE_BULLET: {
                return Client::publish(consts::CLASS_GAME_SHOOT_PAY_MINE_BULLET, $_SESSION["uid"]);
            }
            case consts::CLASS_GAME_CHANGE_POSITION: {
                $content["id"] = $_SESSION["uid"];
                return Client::publish(consts::CLASS_GAME_CHANGE_POSITION, $content);
            }
            case consts::CLASS_GAME_DECIDE_NEXT_LEVEL: {
                $content["id"] = $_SESSION["uid"];
                return Client::publish(consts::CLASS_GAME_DECIDE_NEXT_LEVEL, $content);
            }
            case consts::CLASS_GAME_ONE_PLAYER_PREPARE_GAME: {
                $content["id"] = $_SESSION["uid"];
                return Client::publish(consts::CLASS_GAME_ONE_PLAYER_PREPARE_GAME, $content);
            }
            case consts::CLASS_GAME_VOICE_BROADCAST: {
                $detail_content["id"] = $_SESSION["uid"];
                $detail_content["content"] = $content;
                $detail_content["client_id"] = $_SERVER["GATEWAY_CLIENT_ID"];
                return Client::publish(consts::CLASS_GAME_VOICE_BROADCAST, $detail_content);
            }
            case consts::CLASS_GAME_RECONNECT: {
                return self::dealGameReconnect();
            }
            case consts::CLASS_GAME_MARKET_INFO: {
                return self::dealGameMarketInfo();
            }
            case consts::CLASS_GAME_MARKET_BUY_ITEM: {
                return self::dealGameMarketBuyItem();
            }
            case consts::CLASS_GAME_MARKET_MY_BUY_ITEM_INFO: {
                return self::dealGameMarketMyBuyItemInfo();
            }
            case consts::CLASS_GAME_MARKET_CHARGE:{
                return self::dealGameMarketCharge();
            }
            case consts::CLASS_GAME_MARKET_MY_CHARGE_IN_PLAT:{
                return self::dealGameRMBChargeInPlat();
            }
        }
        return null;
    }

    /**
     * 登录
     * @return null|void
     * @throws \Exception
     */
    public static function dealLogin()
    {
        $protocol = Protocol::getCurrentProtocol();
//        限定同一设备只登陆一个账号
        if (isset($_SESSION["uid"])) {
            throw new \Exception(consts::CLOSE_CONNECTION, consts::ERROR_ONLY_ONE_ACCOUNT);
        }

        $content_array = $protocol->getContent();
        $pn = $content_array["pn"];
        $uid = $content_array["uid"];
        $ssid = $content_array["ssid"];

        $user = new User($uid);
//        密码正确
        /**self::checkKey($pn, $uid, $ssid);**/

//            如果已经在别处登录
        if (Gateway::isUidOnline($uid)) {
            Gateway::sendToCurrentClient($protocol->createStatusProtocol(consts::ERROR_USER_LOGIN_OTHER_PLACE)->encode_to_json());
            return;
        }
//        密码正确，注册uid进SESSION里
        $_SESSION["uid"] = $uid;
        //echo "isset:" . intval(isset($_SESSION["uid"])) . "\n";
        Gateway::bindUid($protocol->getClientId(), $uid);
//        如果没有注册过
        if (!$user->hasRegistered()) {
            $user->setScene(consts::SCENE_HAS_LOGIN_NO_REGIST);
            throw new \Exception("", consts::ERROR_HAS_NO_RIGST);
        }
//        注册过且不是突然退出就标记为登陆成功
        if ($user->getScene() != consts::SCENE_GAMING) {
            $user->setScene(consts::SCENE_HAS_LOGIN);
            $to_protocol = self::dealHallCurrentInfo();
            $to_protocol->setTotalClass(consts::REQUEST_CLASS_HALL);
            $to_protocol->setLittleClass(consts::Reply_CLASS_HALL_CURRENT_INFO);
            Gateway::sendToCurrentClient($to_protocol->encode_to_json());
        }

//        推送消息好友消息
        if ($user->hasMessage()) {
            $user->dealMessage();
        }
//        告诉好友我的状态
        $user->tellMyFriendMyInfo();
        return null;
    }

    /**
     * 注册
     * @return Protocol
     * @throws \Exception
     */
    public static function dealRegister()
    {
        $protocol = Protocol::getCurrentProtocol();
        $content = $protocol->getContent();
        $time = getMillisecond();
        $content["time"] = $time;
        if (!isset($_SESSION["uid"])) {
            throw new \Exception(consts::CLOSE_CONNECTION, consts::ERROR_SCENE_ERROR);
        }
        $user = new User($_SESSION["uid"]);
////        场景错误：场景必须是登陆成功且未注册
//        if ($user->getScene() != consts::SCENE_HAS_LOGIN_NO_REGIST) {
//            throw new \Exception(consts::CLOSE_CONNECTION, consts::ERROR_SCENE_ERROR);
//        }
        $user->createUser($content);
        $result = self::dealHallCurrentInfo();
        $result->setTotalClass(consts::REQUEST_CLASS_HALL);
        $result->setLittleClass(consts::Reply_CLASS_HALL_CURRENT_INFO);
        return $result;
    }

    /**
     * 正常退出
     * @return null
     */
    public static function dealLogout()
    {
        Gateway::closeCurrentClient();
        return null;
    }

    /**
     * 搜索用户
     * @return Protocol
     */
    public static function dealSearch()
    {
        $user = new User($_SESSION["uid"]);
        $protocol = Protocol::getCurrentProtocol();
        $content = Protocol::getCurrentProtocolContent();
        $return_result = $user->searchUserById($content["uid"]);
        if (is_int($return_result)) {
            return $protocol->createStatusProtocol($return_result);
        }
        return Protocol::createToProtocol($protocol, $return_result);
    }

    /**
     * 请求加别人为好友
     * @return Protocol|null
     */
    public static function dealFriendAdd()
    {

        $request_id = $_SESSION["uid"];
        $require_user = new User($request_id);
////        场景错误
//        if ($require_user->getScene() < consts::SCENE_HAS_LOGIN) {
//            throw new \Exception(consts::CLOSE_CONNECTION, consts::ERROR_SCENE_ERROR);
//        }
        $protocol = Protocol::getCurrentProtocol();
        $protocol_content = $protocol->getContent();
        $target_uid = $protocol_content["uid"];
//        尝试加自己为好友
        if ($request_id == $target_uid) {
            return $protocol->createStatusProtocol(consts::ERROR_ADD_SELF);
        }
//        id是空的的时候
        if (!$target_uid) {
            return $protocol->createStatusProtocol(consts::ERROR_TARGET_ID_NOT_EXISTS);
        }
//        不存在这个id的时候
        $target_user = new User($target_uid);
        if (!$target_user->hasRegistered()) {
            return $protocol->createStatusProtocol(consts::ERROR_TARGET_ID_NOT_EXISTS);
        }
//        对方已是好友
        if ($require_user->isHimMyFriend($target_uid)) {
            return $protocol->createStatusProtocol(consts::ERROR_HAS_IT_FRIEND);
        }
//        在对方不在线的情况下，已经发送过了一次了，对方还没收到消息，不能连续发送
        if ($target_user->alreadyHasFriendMessage($request_id)) {
            return $protocol->createStatusProtocol(consts::ERROR_HAS_ADD_REQUEST_ALREADY);
        }

        $saying = $protocol_content["content"];
        $this_time = time();
//        如果对方在线(直接发送给对方)
        $target_user->receiveAFriendRequest($request_id, $saying, $this_time);
        if (Gateway::isUidOnline($target_uid)) {
            $target_user->dealFriendRequestMessage();
        }

        return null;
    }

    /**
     * 响应其他人加好友的请求
     * @return Protocol|null
     */
    public static function dealReplyAdd()
    {
//        $target_uid:被加的人
//        $request_uid:加人的人
        $protocol = Protocol::getCurrentProtocol();
        $content = $protocol->getContent();

        $request_uid = $content["uid"];
        $target_uid = $_SESSION["uid"];

        $target_user = new User($target_uid);
        $request_user = new  User($request_uid);

//        如果别人没有这个“我回应的人”没有请求我，就返回错误
        if (!$target_user->alreadyHasFriendMessage($request_uid)) {
            return $protocol->createStatusProtocol(consts::ERROR_NO_SUCH_A_PERSON_ADD_YOU_OR_IT_EXPIRED);
        }
        $target_user->clearHisFriendRequest($request_uid);
        $y_n = $content["y_n"];
        $protocol_to = $protocol->createToProtocol($protocol, array("uid" => $request_uid, "y_n" => $y_n, "uid2" => $target_uid));
        $protocol_to->setLittleClass(consts::REPLY_CLASS_Friend_REPLY_ADD);
//        告诉自己加好友的结果
        Gateway::sendToCurrentClient($protocol_to->encode_to_json());
        if ($y_n) {
            $target_user->setHimMyFriend($request_uid);
            $request_user->setHimMyFriend($target_uid);
        }

        $request_user->receiveAFriendReply($target_uid, $y_n);
//            对方在线
        if (Gateway::isUidOnline($request_uid)) {
            $request_user->dealFriendReplyMessage();
        }
        $target_user->removeOthersFriendRequest($request_uid);
        return null;
    }

    /**
     * 获取好友列表
     * @return Protocol
     */
    public static function dealFriendList()
    {
        $user = new User(Gateway::getSession($_SERVER["GATEWAY_CLIENT_ID"])["uid"]);
        $friends_list = $user->getAllFriends();
        $protocl = Protocol::getCurrentProtocol();
        return $protocl->createToProtocol($protocl, $friends_list);
    }

    /**
     * 进入大厅总体数据界面
     * @return Protocol
     */
    public static function dealHallCurrentInfo()
    {
        $user = new \User($_SESSION["uid"]);
        $protocol = Protocol::getCurrentProtocol();
//        如果场景在登陆之前或是在战斗中
//        if ($user->getScene() != \consts::SCENE_HAS_LOGIN && $user->getScene() != consts::SCENE_HALL) {
//            echo "因为场景错误不能进入大厅";
//            throw new \Exception("", consts::ERROR_SCENE_ERROR);
//        }
        $user->setScene(consts::SCENE_HAS_LOGIN);
        $user->leaveHall();
        $content = array();
//        遍历所有大厅
        for ($i = 1; $i <= Hall::getHallCount(); $i++) {
            $tmp = array();
            $hall = new Hall($i);
//            获取大厅id
            $tmp["id"] = (string)$i;
//            获取大厅在线人数
            $tmp["number"] = $hall->getOnlineNumbers();
            $content[] = $tmp;
        }

        $new_array = array();
        $new_array["session"] = uniqid();
        $user->updateSession($new_array["session"]);
        $protocol_to = $protocol->createToProtocol($protocol, $new_array);
        $protocol_to->setTotalClass(consts::CLASS_SESSION);
        $protocol_to->setLittleClass(consts::CLASS_SESSION_SET_SESSION);
        Gateway::sendToCurrentClient($protocol_to->encode_to_json());
        return Protocol::createToProtocol($protocol, $content);
    }

    /**
     * 进入游戏大厅
     * @return Protocol
     * @throws \Exception
     */
    public static function dealChooseHall()
    {
        $user = new \User($_SESSION["uid"]);
        $protocol = Protocol::getCurrentProtocol();
//        场景错误
        if ($user->getScene() != \consts::SCENE_HAS_LOGIN && $user->getScene() != consts::SCENE_HALL) {
            throw new \Exception("因为场景错误不能选择大厅", consts::ERROR_SCENE_ERROR);
        }
        $content = $protocol->getContent();
        $hid = $content["hid"];
        $hall = new \Hall($hid);
//        如果人数满了
        if ($hall->isFull()) {
            return $protocol->createStatusProtocol(\consts::ERROR_HALL_IS_FULL);
        }
//        这里是单纯的数据库写操作
        $user->enterHall($hall);
//        转发给大厅里所有人
        Gateway::joinGroup($_SERVER["GATEWAY_CLIENT_ID"], "hall_" . $hall->getHid());
        $to_all_protocol = $protocol->createToProtocol($protocol, $user->getAllInfo());
        $to_all_protocol->setLittleClass(consts::CLASS_HALL_PEOPLE_FLUSH);
        Gateway::sendToGroup("hall_" . $hall->getHid(), $to_all_protocol->encode_to_json());
        return Protocol::createToProtocol($protocol, $hall->currentHallInfo());
    }

    /**
     * 获取我的信息
     * @return Protocol
     */
    private static function dealMyInfo()
    {
        $user = new User($_SESSION["uid"]);
        $info = $user->getAllInfo();
        $protocol = Protocol::getCurrentProtocol();
        return Protocol::createToProtocol($protocol, $info);
    }

    /**
     * 选择桌子坐下
     * @return null
     * @throws \Exception
     */
    private static function dealChooseTable()
    {
        $user = new \User($_SESSION["uid"]);
        $protocol = Protocol::getCurrentProtocol();
        if ($user->getScene() != consts::SCENE_HALL) {
            throw new \Exception("因为场景错误不能选择桌子", consts::ERROR_SCENE_ERROR);
        }
        $content = $protocol->getContent();
//        桌号
        $tid = $content["tid"];
//        位置
        $pl = $content["pl"];
        $user->leaveTable();
        $hid = $user->getHall();
        $hall = new Hall($hid);
        if ($hall->isTableFullPlayer($tid)) {
            throw new \Exception("", consts::ERROR_TABLE_PLAYER_FULL);
        }
        if ($hall->isTableGaming($tid)) {
            throw new \Exception("", consts::ERROR_TABLE_IS_GAMING);
        }
        $hall->removeTableVisitor($tid, $_SESSION["uid"]);
        $hall->removeTablePlayer($tid, $_SESSION["uid"]);
        switch ($pl) {
            case "1":
                if ($hall->getTablePlayer1($tid)) {
                    throw new  \Exception("", consts::ERROR_TABLE_HAS_PEOPLE);
                }
                break;
            case "2":
                if ($hall->getTablePlayer2($tid)) {
                    throw new  \Exception("", consts::ERROR_TABLE_HAS_PEOPLE);
                }
                break;
        }
        $user->sitDownAs($tid, $pl, 1);
        if (!$hall->isTableFullPlayer($tid)) {
            $user->setHallHost();
            $hall->setUserHost($tid, $_SESSION["uid"]);
        }
//        离开大厅组，进入房间租
        Gateway::joinGroup($_SERVER["GATEWAY_CLIENT_ID"], "hall_" . $hid . "_table_" . $tid);

//        通知大厅更新数据
        $protocol->setTotalClass(consts::SCENE_HALL);
        $protocol->setLittleClass(consts::CLASS_HALL_PEOPLE_FLUSH);
        $to_pro = $protocol->createToProtocol($protocol, $user->getAllInfo());
        Gateway::sendToGroup("hall_" . $hid, $to_pro->encode_to_json());
        return null;
    }

    /**
     * 选择观战
     * @return null
     * @throws \Exception
     */
    private static function dealObserveGame()
    {
        $user = new \User($_SESSION["uid"]);
        $protocol = Protocol::getCurrentProtocol();
        if ($user->getScene() != consts::SCENE_HALL) {
            throw new \Exception("因为场景错误不能观战", consts::ERROR_SCENE_ERROR);
        }
        $content = $protocol->getContent();
//        桌号
        $tid = $content["tid"];
//        位置
        $pl = $content["pl"];

        $or_tid = $user->getTableNum();
        $user->leaveTable();
        $hid = $user->getHall();


        $hall = new Hall($hid);
        /** @var Hall $hall */
        $hall->removeTablePlayer($or_tid, $user->getUid());

        if (!$hall->isTableGaming($tid)) {
            throw new \Exception("", consts::ERROR_TABLE_NOT_GAMING);
        }
        if ($hall->isTableVisitorFull($tid)) {
            throw new \Exception("", consts::ERROR_TABLE_VISITOR_FULL);
        }
        $player_gaming = "";
        switch ($pl) {
            case "1":
                if (!($player_gaming = $hall->getTablePlayer1($tid))) {
                    throw new  \Exception("", consts::ERROR_TABLE_NO_PEOPLE);
                }
                break;
            case "2":
                if (!($player_gaming = $hall->getTablePlayer2($tid))) {
                    throw new  \Exception("", consts::ERROR_TABLE_NO_PEOPLE);
                }
                break;
        }
        //$user->sitDownAs($tid, $pl, 0);
//        离开大厅组，进入房间租
        Gateway::joinGroup($_SERVER["GATEWAY_CLIENT_ID"], "hall_" . $hid . "_table_" . $tid);

//        通知大厅更新数据
        $protocol->setTotalClass(consts::SCENE_HALL);
        $protocol->setLittleClass(consts::CLASS_HALL_PEOPLE_FLUSH);
        $to_pro = $protocol->createToProtocol($protocol, $user->getAllInfo());
        Gateway::sendToGroup("hall_" . $hid, $to_pro->encode_to_json(), $_SERVER["GATEWAY_CLIENT_ID"]);
        echo "observed player:" . $player_gaming . "\n";
//        告知游戏中玩家有新的观战者加入
        Client::publish(consts::CLASS_GAME_NEW_VISITOR_IN, array("uid" => $_SESSION["uid"], "player" => $player_gaming, "client_id" => $_SERVER["GATEWAY_CLIENT_ID"]));
        return null;
    }

    /**
     * 用户连接断开时触发
     *
     * @param string $client_id
     */
    public static function dealUserClose($client_id = "")
    {
        if ($client_id) {
            Protocol::unset_id($client_id);
        }
//        如果用户在redis中存在（用户已经登陆）
        if (isset($_SESSION["uid"])) {
            $uid = $_SESSION["uid"];
            echo $uid . " close !" . "\n";
            $user = new User($uid);
//            如果在游戏中就暂时什么都不做
            if ($user->getScene() == consts::SCENE_GAMING) {
                if (Gateway::isUidOnline($other_uid = $user->getOtherTablePlayer())) {
                    return;
                } else {
                    $two_uid = array($uid, $other_uid);
                    foreach ($two_uid as $uid1) {
                        $user = new User($uid1);
                        $user->clearInfoOnClose();
                    }
                    Client::publish(consts::CLASS_GAME_DESTROY_SIGNAL, array("id" => $uid));
                }
            } //            如果不在游戏中就抹掉所有数据
            else {
                $user->clearInfoOnClose();
            }
        }
    }

    /**
     * 处理用户离开座位
     * @return null
     */
    private static function dealLeaveTable()
    {
        $protocol = Protocol::getCurrentProtocol();
        $uid = $_SESSION["uid"];
        $user = new User($uid);
        $hid = $user->getHall();
        $tid = $user->getTableNum();
        $pl = $user->getPlayerNum();

        //$user->leaveHall();
        //$user->setHall($hid);
        $user->leaveTable();

        Gateway::leaveGroup($_SERVER["GATEWAY_CLIENT_ID"], "hall_" . $hid . "_tid_" . $tid);

        $hall = new Hall($hid);
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
            $protocol_to = $protocol->createToProtocol($protocol, $other_player->getAllInfo());
            $protocol_to->setTotalClass(consts::REQUEST_CLASS_HALL);
            $protocol_to->setLittleClass(consts::CLASS_HALL_PEOPLE_FLUSH);
            Gateway::sendToGroup("hall_" . $hid, $protocol_to->encode_to_json());
        }
        $protocol_to = $protocol->createToProtocol($protocol, $user->getAllInfo());
        $protocol_to->setTotalClass(consts::REQUEST_CLASS_HALL);
        $protocol_to->setLittleClass(consts::CLASS_HALL_PEOPLE_FLUSH);
        Gateway::sendToGroup("hall_" . $hid, $protocol_to->encode_to_json());
        return null;
    }

    /**
     * 用户准备
     * @throws \Exception
     */
    private static function doUserPrepare()
    {
        $uid = $_SESSION["uid"];
        $user = new User($uid);
//        不在游戏
        if ($user->getIsGaming()) {
            throw new \Exception("", consts::ERROR_YOU_ARE_GAMING);
        }
//        不在桌子上
        if (!($tid = $user->getTableNum()) || !($pl = $user->getPlayerNum())) {
            throw new \Exception("", consts::ERROR_NO_IN_TABLE);
        }
//        用户准备
        $user->setPrepare();
//        告知其他用户
        $protocol = Protocol::getCurrentProtocol();
        $content = $user->getAllInfo();
        $protocol_to_hall_user = $protocol->createToProtocol($protocol, $content);
        $protocol_to_hall_user->setTotalClass(consts::REQUEST_CLASS_HALL);
        $protocol_to_hall_user->setLittleClass(consts::CLASS_HALL_PEOPLE_FLUSH);
        $hid = $user->getHall();
        Gateway::sendToGroup("hall_" . $hid, $protocol_to_hall_user->encode_to_json());

//        如果旁边的用户也准备了
        $hall = new Hall($hid);
        if ($pl == "1") {
            $other_uid = $hall->getTablePlayer2($tid);
        } else {
            $other_uid = $hall->getTablePlayer1($tid);
        }
        $other_user = new User($other_uid);
        if ($other_user->getIsPrepared()) {
//            选关
            $choose_content["lm"] = \GameDataSaver::getMaxLevel(array($uid, $other_uid));
            $to_uid = $uid;
            if (!$user->isRoomHost()) {
                $to_uid = $other_uid;
            }
            Gateway::sendToUid($to_uid, Protocol::createToNewProtocol(consts::CLASS_GAME, consts::CLASS_GAME_LEVEL, $choose_content)->encode_to_json());
            return null;
        }
    }

    /**
     * 取消准备
     * @return null
     * @throws \Exception
     */
    private static function doUserAntiPrepare()
    {
        $uid = $_SESSION["uid"];
        $user = new User($uid);
//        不在游戏
        if ($user->getIsGaming()) {
            throw new \Exception("", consts::ERROR_YOU_ARE_GAMING);
        }
//        不在桌子上
        if (!($tid = $user->getTableNum()) || !($pl = $user->getPlayerNum())) {
            throw new \Exception("", consts::ERROR_NO_IN_TABLE);
        }
//        取消准备
        $user->cancelPrepare();
//        告知其他用户
        $protocol = Protocol::getCurrentProtocol();
        $protocol_to_hall_user = $protocol->createToProtocol($protocol, $user->getAllInfo());
        $protocol_to_hall_user->setLittleClass(consts::CLASS_HALL_PEOPLE_FLUSH);
        Gateway::sendToGroup("hall_" . $user->getHall(), $protocol_to_hall_user->encode_to_json());
        return null;
    }

    /**
     * 踢人
     * @return null
     * @throws \Exception
     */
    private static function dealHostKickPlayer()
    {
        $uid = $_SESSION["uid"];
        $user = new User($uid);
        if (!$user->isRoomHost()) {
            throw new \Exception("", consts::ERROR_YOU_ARE_NOT_HOST);
        }
        $protocol = Protocol::getCurrentProtocol();
        $target_uid = $protocol->getContent()["uid"];

        $target_user = new User($target_uid);
        $tid = $user->getTableNum();
        $other_tid = $target_user->getTableNum();
        if ($tid != $other_tid) {
            throw new \Exception("", consts::ERROR_TARGET_ID_AT_TABLE);
        }
        $target_user->leaveTable();
        Gateway::leaveGroup($_SERVER["GATEWAY_CLIENT_ID"], "hall_" . $user->getHall() . "_tid_" . $tid);

        $hid = $user->getHall();
        $hall = new Hall($hid);
        $hall->removeTablePlayer($tid, $target_uid);
        Gateway::sendToUid($target_user, $protocol->encode_to_json());
        $to_all_protocol = $protocol->createToProtocol($protocol, $target_user->getAllInfo());
        $to_all_protocol->setLittleClass(consts::CLASS_HALL_PEOPLE_FLUSH);
        Gateway::sendToGroup("hall_" . $hid, $to_all_protocol->encode_to_json());
        return null;
    }

    /**
     * 关卡选择
     * @return array
     */
    public static function dealGameLevelChoose()
    {
        $user = new User($_SESSION["uid"]);
        $tid = $user->getTableNum();
        $hall = new Hall($hid = $user->getHall());
        if (($other_user_id = $hall->getTablePlayer1($tid)) == $_SESSION["uid"]) {
            $other_user_id = $hall->getTablePlayer2($tid);
        }
        $other_user = new User($other_user_id);
//        设置各自的游戏状态、场景和桌子信息
        $user->setGaming();
        $other_user->setGaming();
        $hall->setTableStartGame($tid);
        $user->setScene(consts::SCENE_GAMING);
        $other_user->setScene(consts::SCENE_GAMING);
//            广播
        $content = $user->getAllInfo();
        $protocol_to_hall_user = Protocol::createToNewProtocol(consts::REQUEST_CLASS_HALL, consts::CLASS_HALL_PEOPLE_FLUSH, $content);
        Gateway::sendToGroup("hall_" . $hid, $protocol_to_hall_user->encode_to_json());
        $content = $other_user->getAllInfo();
        $protocol_to_hall_user = Protocol::createToNewProtocol(consts::REQUEST_CLASS_HALL, consts::CLASS_HALL_PEOPLE_FLUSH, $content);
        Gateway::sendToGroup("hall_" . $hid, $protocol_to_hall_user->encode_to_json());
        $user = new User($_SESSION["uid"]);
        $content = Protocol::getCurrentProtocolContent();
        $tmp = (new Hall($user->getHall()))->table_info($user->getTableNum())["players"];
        foreach ($tmp as $each_player) {
            $content["player_arr"][] = $each_player["uid"];
        }

        $content["group_id"] = $content["player_arr"][0] . $content["player_arr"][1];
        return $content;
    }

    /**
     * 获取自己的游戏币以及道具信息
     * @return Protocol
     */
    private static function dealGameMarketMyBuyItemInfo()
    {
        $uid = $_SESSION["uid"];
        $market = \Market::getMarketInstance();
        $ret = array();
        $protocol = Protocol::getCurrentProtocol();

        $ret["gm"] = $market->getPlayerGameMoney($uid);

        foreach ($market->getItemsHeBought($uid) as $class_name => $item_obj)
        {
            if ($class_id = \GameObj::getClassIdByClassStr($class_name))
            {
                $ret["items"][] = $item_obj;
                $item_obj->cid = $class_name::getId();
            }
        }
        return Protocol::createToProtocol($protocol, $ret);
    }

    /**
     * 获取游戏商城信息
     * @return Protocol
     */
    private static function dealGameMarketInfo()
    {
        $ret = \Market::getMarketInstance()->encode();
        $protocol = Protocol::getCurrentProtocol();
        return Protocol::createToProtocol($protocol, $ret);
    }

    /**
     * 断线重连
     * @throws \Exception
     */
    private static function dealGameReconnect()
    {
        echo "在Analyzer里\n";
        echo "客户端尝试重连\n";
        $protocol = Protocol::getCurrentProtocol();
        $content = $protocol->getContent();

        $_SESSION["uid"] = $uid = $content["id"];
        $session = $content["s"];

        $user = new User($uid);
        if (Gateway::isUidOnline($uid) || !$user->getIsGaming() || $user->getSession() != $session) {
            echo "重连校验失败\n";
            unset($_SESSION["uid"]);
            throw new  \Exception("", consts::ERROR_SESSION_ERROR);
        }
        echo "重连校验成功\n";
        Gateway::bindUid($_SERVER["GATEWAY_CLIENT_ID"], $uid);
        $content["client_id"] = $_SERVER["GATEWAY_CLIENT_ID"];
        Client::publish(consts::CLASS_GAME_RECONNECT, $content);
        return null;
    }

    /**
     * 清除观战者
     * @param $uid
     */
    public static function clearVisitor($uid)
    {
        echo "清除观战者\n";
        $user = new User($uid);
        $hall = new Hall($user->getHall());
        $tid = $user->getTableNum();
        //$user->leaveTable();
        $hall->removeTableVisitor($tid, $uid);
    }

    /**
     * 购买道具
     * @return Protocol
     */
    private static function dealGameMarketBuyItem()
    {
        $uid = $_SESSION["uid"];
        $protocol = Protocol::getCurrentProtocol();
        $content = Protocol::getCurrentProtocolContent();
        $cid = (int)$content["id"];
        $class_name = \GameObj::getClassStrByClassId($cid);
        $ret_status = \Market::getMarketInstance()->buyPayItem($uid, $class_name);

        return $protocol->createStatusProtocol($ret_status);
    }

    /**
     * 设置个人信息
     * @return Protocol
     */
    private static function dealSetMyInfo()
    {
        $user = new User($_SESSION["uid"]);
        $protocol = Protocol::getCurrentProtocol();
        $content = $protocol->getContent();

        if ($nick = $content["nick"])
            $user->setNickName((string)$nick);

        if ($ge = $content["ge"])
            $user->setGender((int)$ge);

        if ($im = $content["im"])
            $user->setImage($im);

        return $protocol->createStatusProtocol(consts::SUCCESS);
    }

    /**
     * 好友聊天
     */
    private static function dealTalkToFriend()
    {
        $uid = $_SESSION["uid"];
        $protocol = Protocol::getCurrentProtocol();
        $content = $protocol->getContent();

        $to = $content["to"];
        $user_message_from = new User($uid);
//        不是好友
        if (!$user_message_from->isHimMyFriend($to))
            throw new \Exception("", consts::ERROR_NO_SUCH_A_FRIEND);
//        好友不在线
        if (!Gateway::isUidOnline($to))
            throw new \Exception("", consts::ERROR_FRIEND_NOT_ONLINE);

        $content["from"] = $uid;
        $content["time"] = (string)date("Y-m-d H:i:s");
        $protocol_to_friend = Protocol::createToNewProtocol(consts::REQUEST_CLASS_Friend,consts::REPLY_CLASS_FRIEND_YOU_GET_A_MESSAGE_FROM_FRIEND,$content);
//        发送给好友
        Gateway::sendToUid($to,$protocol_to_friend->encode_to_json());
//        告诉发送者成功了
        return $protocol->createStatusProtocol(consts::SUCCESS);
    }

    /**
     * 充值（已经在平台充值过的情况下，在支付数据库中扣除数值购买游戏币）
     * @return Protocol
     */
    private static function dealGameMarketCharge()
    {
        $uid = $_SESSION["uid"];
        $protocol = Protocol::getCurrentProtocol();
        $content = $protocol->getContent();
        $num = (int)$content["num"];

        $market = \Market::getMarketInstance();
        $ret = $market->charge($uid,$num);

        $current_num = $market->getPlayerGameMoney($uid);
        $protocol->setContent(["num"=>$current_num]);
        return $protocol->createStatusProtocol($ret);
    }
    /**
     * 获取平台金额
     * @return Protocol
     */
    private static function dealGameRMBChargeInPlat()
    {
        $uid = $_SESSION["uid"];
        $num = \Market::getMarketInstance()->getRMBMoney($uid);
        $protocol = Protocol::getCurrentProtocol();
        $protocol->setContent(['num'=>$num]);
        return $protocol;
    }

}
