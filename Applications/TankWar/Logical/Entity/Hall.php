<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/28
 * Time: 13:19
 */
use DbOperate\RedisInstance;

/*
 * hall_info_1(全部的uid) {123:1,12332:0,412:1,4123124:0}
 * hall_1_table_1_visitors(具体座位数据) （集合）{a，b}（数字表示座位）
 * hall_1_table_1_info -> {"gaming":1,uid1:123,uid2:234,host:123}
 * user_table_abc:
 * {
 *      "hall":"1",
 *      "num":"1",
 *      "tb_st":"1(gamer)\0(visitor)"
 *      "gaming":1,
 *      "pre":1,
 *      "room_host":0,
 *      "pl":"1",
 * }
 */

class Hall
{

    private $hid = null;

    static private $hall_count = 5;
    static private $full_number = 80;
    static private $table_number = 30;
    static private $table_full_visitor_number = 3;


    /**
     * @return null
     */
    public function getHid()
    {
        return $this->hid;
    }

    /**
     * @return int
     */
    public static function getTableFullVisitorCount()
    {
        return self::$table_full_visitor_number;
    }

    /**
     * @return int
     */
    public static function getTableCount()
    {
        return self::$table_number;
    }

    /**
     * @return int
     */
    public static function getFullCount()
    {
        return self::$full_number;
    }

    /**
     * @return int
     */
    public static function getHallCount()
    {
        return self::$hall_count;
    }


    public function __construct($hid)
    {
        if ($hid > self::$hall_count) {
            throw new Exception("", $code = consts::ERROR_WRONG_HID);
        }
        $this->hid = $hid;
    }

    public function getRedis()
    {
        return RedisInstance::get();
    }

//    获取当前大厅在线人数
    public function getOnlineNumbers()
    {
        $redis = $this->getRedis();
        return intval($redis->zCard("hall_info_" . $this->hid));
    }

//    大厅是否满了
    public function isFull()
    {
        return $this->getOnlineNumbers() >= self::$full_number;
    }

    /*
     * tables:[{
     *      "tid":"1",
     *      "gaming":0,
     *      "players":[{"uid":"123","nick":"abc","ge":1,"ol":1}],
     *      "vistors":[{"uid":"123","nick":"abc","ge":1,"ol":1},{"uid":"123","nick":"abc","ge":1,"ol":1},{"uid":"123","nick":"abc","ge":1,"ol":1}](游戏开始前不允许有观战者)
     * }，{
     *      "tid":"2",
     *      "gaming":0,
     *      "players":[{"uid":"123","nick":"abc","ge":1,"ol":1}],
     *      "vistors":[{"uid":"123","nick":"abc","ge":1,"ol":1},{"uid":"123","nick":"abc","ge":1,"ol":1},{"uid":"123","nick":"abc","ge":1,"ol":1}](游戏开始前不允许有观战者)
     * }，{
     *      "tid":"3",
     *      "gaming":0,
     *      "players":[{"uid":"123","nick":"abc","ge":1,"ol":1}],
     *      "vistors":[{"uid":"123","nick":"abc","ge":1,"ol":1},{"uid":"123","nick":"abc","ge":1,"ol":1},{"uid":"123","nick":"abc","ge":1,"ol":1}](游戏开始前不允许有观战者)
     * }]
     *
     * stand:[{"uid":"123","nick":"abc","ge":1,"ol":1},{"uid":"123","nick":"abc","ge":1,"ol":1},{"uid":"123","nick":"abc","ge":1,"ol":1},]
     */
    public function currentHallInfo()
    {
        $info = array();
        $redis = $this->getRedis();
        $all_users = $redis->zRange("hall_info_" . $this->hid, 0, -1);
        foreach ($all_users as $uid) {
            $user = new User($uid);
            $info[] = $user->getAllInfo();
        }
        return $info;
//        for ($i = 1; $i <= self::$table_number; $i++) {
//            $each_table_info = $this->table_info($i);
//            $info["table"][] = $each_table_info;
//        }
//        foreach ($this->get_hall_free_users() as $uid) {
//            $user = new User($uid);
//            $user_info = $user->getAllInfo();
//            $info["stand"][] = $user_info;
//        }
//        $info["hid"] = \AnalysisQues\Protocol::getCurrentProtocolContent()["hid"];
//        return $info;
    }

//    获取所有不在桌位上的用户ID
    public function get_hall_free_users()
    {
        $redis = $this->getRedis();
        return $redis->zRangeByScore("hall_info_" . $this->hid, 0, 0);
    }

    /*
     * {
     *      "tid":"1",
     *      "gaming":0,
     *      "players":[{"uid":"123","nick":"abc","ge":1,"ol":1}],
     *      "vistors":[{"uid":"123","nick":"abc","ge":1,"ol":1},{"uid":"123","nick":"abc","ge":1,"ol":1},{"uid":"123","nick":"abc","ge":1,"ol":1}](游戏开始前不允许有观战者)
     * }
     */
    public function table_info($i)
    {
        $table_info = array();
//        ①
        $table_info["tid"] = (string)$i;
//        ②
        $table_info["gaming"] = $this->isTableGaming($i);
//        ③
        $uid1 = $this->table_uid1($i);
        $user1 = new User($uid1);
        $user1_info = $user1->searchUserById($uid1);
        if (is_array($user1_info)) {
            $table_info["players"][] = $user1_info;
        }
        $uid2 = $this->table_uid2($i);
        $user2 = new User($uid2);
        $user2_info = $user2->searchUserById($uid2);
        if (is_array($user2_info)) {
            $table_info["players"][] = $user2_info;
        }
//        ④
        $vistors_id = $this->table_visitors($i);

        $table_info["visitors"] = array();
        foreach ($vistors_id as $uid) {
            $user = new User($uid);
            $user_info = $user->searchUserById($uid);
            if (is_array($user_info)) {
                $table_info["visitors"][] = $user_info;
            }
        }
        return $table_info;
    }

    /*
     * 判断当前桌子上的观展者是不是已经满员
     */
    public function isTableVisitorFull($tid)
    {
        return count($this->table_visitors($tid)) >= self::getTableFullVisitorCount();
    }

    /*
     * 当前桌子是否正在游戏中
     */
    public function isTableGaming($i)
    {
        $redis = $this->getRedis();
        return (int)$redis->hGet("hall_" . $this->hid . "_table_" . (string)$i . "_info", "gaming");
    }

    /*
     * 1号玩家
     */
    public function table_uid1($i)
    {
        $redis = $this->getRedis();
        return $redis->hGet("hall_" . $this->hid . "_table_" . $i . "_info", "uid1");
    }

    /*
     * 2号玩家
     */
    public function table_uid2($i)
    {
        $redis = $this->getRedis();
        return $redis->hGet("hall_" . $this->hid . "_table_" . $i . "_info", "uid2");
    }

    /*
     * 获取当前桌子的所有观战者
     */
    public function table_visitors($i)
    {
        $redis = $this->getRedis();
        return $redis->sMembers("hall_" . $this->hid . "_table_" . $i . "_visitors");
    }

//    添加大厅内用户
    public function addOnlineUser($uid)
    {
        $this->getRedis()->zAdd("hall_info_" . $this->hid, 0, $uid);
    }

//    移除在线用户
    public function removeOnlineUser($uid)
    {
        $this->getRedis()->zRem("hall_info_" . $this->hid, $uid);
    }

//    移除观战者
    public function removeTableVisitor($tid, $uid)
    {
        $this->getRedis()->sRem("hall_" . $this->hid . "_table_" . $tid . "_visitors", $uid);
    }

    public function removeTablePlayer($tid, $uid)
    {
        $uid1 = $this->getTablePlayer1($tid);
        if ($uid == $uid1) {
            $this->removeTablePlayer1($tid);
        }
        $uid2 = $this->getTablePlayer2($tid);
        if ($uid == $uid2) {
            $this->removeTablePlayer2($tid);
        }
    }

//    获取桌子上一号玩家的id
    public function getTablePlayer1($tid)
    {
        return $this->getRedis()->hGet("hall_" . $this->hid . "_table_" . $tid . "_info", "uid1");
    }

//    获取桌子上二号玩家的id
    public function getTablePlayer2($tid)
    {
        return $this->getRedis()->hGet("hall_" . $this->hid . "_table_" . $tid . "_info", "uid2");
    }

//    移除一号玩家
    public function removeTablePlayer1($tid)
    {
        $this->getRedis()->hDel("hall_" . $this->hid . "_table_" . $tid . "_info", "uid1");
    }

//    移除二号玩家
    public function removeTablePlayer2($tid)
    {
        $this->getRedis()->hDel("hall_" . $this->hid . "_table_" . $tid . "_info", "uid2");
    }

//    设置座位上的玩家
    public function setTablePlayer($tid, $uid, $pl)
    {
        $this->getRedis()->hSet("hall_" . $this->hid . "_table_" . $tid . "_info", "uid" . $pl, $uid);
    }

    public function setTableVisitor($tid, $uid)
    {
        $this->getRedis()->sAdd("hall_" . $this->hid . "_table_" . $tid . "_visitors", $uid);
    }

//    判断当前桌子是否已经满人了
    public function isTableFullPlayer($tid)
    {
        if ($this->getTablePlayer1($tid) && $this->getTablePlayer2($tid)) {
            return true;
        }
        return false;
    }

//    设置在线玩家
    public function setOnlinePeoplePlayer($tid, $uid)
    {
        $this->getRedis()->zAdd("hall_info_" . $this->hid, intval($tid), $uid);
    }

//    设置房主
    public function setUserHost($tid, $uid)
    {
        $this->getRedis()->hSet("hall_" . $this->hid . "_table_" . $tid . "_info", "host", $uid);
    }

//    座位是不是空的
    public function isTableEmpty($tid)
    {
        if ($this->getTablePlayer1($tid) || $this->getTablePlayer2($tid)) {
            return false;
        }
        return true;
    }

//    清空座位数据
    public function clearTable($tid)
    {
        $this->getRedis()->del("hall_" . $this->hid . "_table_" . $tid . "_info");
    }

//    设置桌子上开始游戏
    public function setTableStartGame($tid, $flag = 1)
    {
        $this->getRedis()->hSet("hall_" . $this->hid . "_table_" . $tid . "_info", "gaming", $flag);
    }


}

