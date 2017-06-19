<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/14
 * Time: 11:46
 */

namespace AnalysisQues;


class Protocol
{
    private static $content_keys = array(array());
    private static $keys = array("id", "en", "co", "un", "st", "bc", "lc", "ct");
    private static $id_list = array();


    private $client_id;


    // 包序号（每个客户端再一次长链接生命内有从0开始递增的报序号）
    private $id;
    // 是否加密
    private $encryption;
    // 是否压缩
    private $compressed;
    // 压缩加密顺序
    private $sequence;
    // Unix时间戳
    private $unix_time;
    // 状态（服务端的反馈：成功或失败）
    private $status;
    // 大类
    private $total_class;
    // 小类
    private $little_class;
    // 内容
    private $content;

//    设置content里必须有的Key
    public static function setContentKey()
    {
        $key = self::$content_keys;
//        User
        $key[\consts::REQUEST_CLASS_USER][\consts::REQUEST_CLASS_USER_LOGIN] = array("pn", "uid", "ssid");
        $key[\consts::REQUEST_CLASS_USER][\consts::REQUEST_CLASS_USER_REGIST] = array("id", "nick", "ge");
        $key[\consts::REQUEST_CLASS_USER][\consts::REQUEST_CLASS_USER_LOGOUT] = array();
        $key[\consts::REQUEST_CLASS_USER][\consts::REQUEST_CLASS_USER_SEARCH_BY_UID] = array("uid");
        $key[\consts::REQUEST_CLASS_USER][\consts::REQUEST_CLASS_USER_MY_INFO] = array();
        $key[\consts::REQUEST_CLASS_USER][\consts::REQUEST_CLASS_USER_SET_INFO] = array("nick","ge","im");

//        Friend
        $key[\consts::REQUEST_CLASS_Friend][\consts::REQUEST_CLASS_Friend_ADD] = array("uid", "content");
        $key[\consts::REQUEST_CLASS_Friend][\consts::REQUEST_CLASS_Friend_REPLY_ADD] = array("uid", "y_n");
        $key[\consts::REQUEST_CLASS_Friend][\consts::REQUEST_CLASS_Friend_List_Request] = array();
        $key[\consts::REQUEST_CLASS_Friend][\consts::REQUEST_CLASS_FRIEND_TALK] = array("ct","ty","to");


//        Hall        
        $key[\consts::REQUEST_CLASS_HALL][\consts::REQUEST_CLASS_HALL_CURRENT_INFO] = array();
        $key[\consts::REQUEST_CLASS_HALL][\consts::REQUEST_CLASS_HALL_CHOOSE_HALL] = array("hid");
        $key[\consts::REQUEST_CLASS_HALL][\consts::CLASS_HALL_CHOOSE_TABLE] = array("tid");
        $key[\consts::REQUEST_CLASS_HALL][\consts::CLASS_HALL_USER_LEAVE_TABLE] = array();
        $key[\consts::REQUEST_CLASS_HALL][\consts::CLASS_HALL_USER_PREPARE] = array();
        $key[\consts::REQUEST_CLASS_HALL][\consts::CLASS_HALL_USER_ANTI_PREPARE] = array();
        $key[\consts::REQUEST_CLASS_HALL][\consts::CLASS_HALL_KICK_OUT_PEOPLE] = array("uid");
        $key[\consts::REQUEST_CLASS_HALL][\consts::CLASS_HALL_NEW_VISITOR_IN] = array("pl","tid");

        $key[\consts::CLASS_GAME][\consts::CLASS_GAME_LEVEL] = array("level");



        $key[\consts::CLASS_GAME][\consts::CLASS_GAME_MOVE] = array("po");
        $key[\consts::CLASS_GAME][\consts::CLASS_GAME_SHOOT_BULLET] = array();
        $key[\consts::CLASS_GAME][\consts::CLASS_GAME_SHOOT_SUPER_BULLET] = array();
        $key[\consts::CLASS_GAME][\consts::CLASS_GAME_SHOOT_PAY_FOLLOW_BULLET] = array();
        $key[\consts::CLASS_GAME][\consts::CLASS_GAME_SHOOT_PAY_MINE_BULLET] = array();
        $key[\consts::CLASS_GAME][\consts::CLASS_GAME_CHANGE_POSITION] = array("po");
        $key[\consts::CLASS_GAME][\consts::CLASS_GAME_DECIDE_NEXT_LEVEL] = array();
        $key[\consts::CLASS_GAME][\consts::CLASS_GAME_ONE_PLAYER_PREPARE_GAME] = array();
        $key[\consts::CLASS_GAME][\consts::CLASS_GAME_RECONNECT] = array("id","s");
        $key[\consts::CLASS_GAME][\consts::CLASS_GAME_MARKET_INFO] = array();
        $key[\consts::CLASS_GAME][\consts::CLASS_GAME_MARKET_BUY_ITEM] = array("id");
        $key[\consts::CLASS_GAME][\consts::CLASS_GAME_MARKET_MY_BUY_ITEM_INFO] = array();
        $key[\consts::CLASS_GAME][\consts::CLASS_GAME_MARKET_CHARGE] = array("num");
        $key[\consts::CLASS_GAME][\consts::CLASS_GAME_MARKET_MY_CHARGE_IN_PLAT] = array();

        self::$content_keys = $key;
    }

//    不依据客户端发来的Protocol创造一个新的Protocol
    public static function createToNewProtocol($total_class, $little_class, $content,$compressed = 1):Protocol
    {
        $protocol = new Protocol();
        $protocol->setId(0);
        $protocol->setTotalClass($total_class);
        $protocol->setLittleClass($little_class);
        $protocol->setEncryption(0);
        $protocol->setSequence(0);
        $protocol->setCompressed($compressed);
        $protocol->setContent($content);
        $protocol->setStatus(0);
        $protocol->setUnixTime(time());
        return $protocol;
    }


    /**
     * @return mixed
     */
    public function getClientId()
    {
        return $this->client_id;
    }

    /**
     * @param mixed $client_id
     */
    public function setClientId($client_id)
    {
        $this->client_id = $client_id;
    }
    

    /**
     * @param boolean $bad_request
     */
    public function setBadRequest($bad_request)
    {
        $this->bad_request = $bad_request;
    }


    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return Protocol
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getEncryption()
    {
        return $this->encryption;
    }

    /**
     * @param mixed $encryption
     * @return Protocol
     */
    public function setEncryption($encryption)
    {
        $this->encryption = $encryption;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSequence()
    {
        return $this->sequence;
    }

    /**
     * @param mixed $sequence
     * @return Protocol
     */
    public function setSequence($sequence)
    {
        $this->sequence = $sequence;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCompressed()
    {
        return $this->compressed;
    }

    /**
     * @param mixed $compressed
     * @return Protocol
     */
    public function setCompressed($compressed)
    {
        $this->compressed = $compressed;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getUnixTime()
    {
        return $this->unix_time;
    }

    /**
     * @param mixed $unix_time
     * @return Protocol
     */
    public function setUnixTime($unix_time)
    {
        $this->unix_time = $unix_time;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     * @return Protocol
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTotalClass()
    {
        return $this->total_class;
    }

    /**
     * @param mixed $total_class
     * @return Protocol
     */
    public function setTotalClass($total_class)
    {
        $this->total_class = $total_class;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLittleClass()
    {
        return $this->little_class;
    }

    /**
     * @param mixed $little_class
     * @return Protocol
     */
    public function setLittleClass($little_class)
    {
        $this->little_class = $little_class;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param mixed $content
     * @return Protocol
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    public static function createFromProtocol($client_id, $message = 0):Protocol
    {
//        测试用
//        Gateway::sendToCurrentClient($message);
        $protocol = new Protocol();
        $protocol->client_id = $client_id;
        $array = json_decode($message, true);
        if (!$array || count($array) != 8 || !self::keysRight($array)) {
            throw new \Exception(\consts::IGNORE);
        }
        // 是否是递增id号
        $protocol->id = $array["id"];
        if ($protocol->id != self::getIdBefore($client_id) + 1) {
            //    throw new \Exception("",$code = \consts::ERROR_BAD_REQUEST);
        }
        self::setIdNow($client_id);
        
        $protocol->encryption = $array["en"];
        $protocol->compressed = $array["co"];
        $protocol->unix_time = $array["un"];
        $protocol->status = $array["st"];
        $protocol->total_class = $array["bc"];
        $protocol->little_class = $array["lc"];
        $protocol->content = $array["ct"];
        if (is_array($protocol->content)) {
            return $protocol;
        }
        if ($protocol->compressed) {
            $protocol->content = base64_decode($protocol->content);
            $protocol->content = gzdecode($protocol->content);

        }
        if ($protocol->encryption) {
            $aes = new \aes();
            $protocol->content = $aes->decode($protocol->content);
        }
        $protocol->content = json_decode($protocol->content, true);
        return $protocol;
    }

    public static function createToProtocol(Protocol $protocol_from, $content):Protocol
    {
        $protocol = new Protocol();
        $protocol->id = $protocol_from->getId();
        $protocol->encryption = $protocol_from->getEncryption();
        $protocol->compressed = $protocol_from->getCompressed();
        $protocol->unix_time = time();
        $protocol->status = 0;
        $protocol->total_class = $protocol_from->getTotalClass();
        $protocol->little_class = $protocol_from->getLittleClass();
        $protocol->content = $content;
        return $protocol;
    }

    // 获取上一次的包id
    public static function getIdBefore($client_id)
    {
        if (!isset(self::$id_list[$client_id])) {
            self::$id_list[$client_id] = -1;
        }
        return self::$id_list[$client_id];
    }

//    设置当前包id
    public static function setIdNow($client_id)
    {
        self::$id_list[$client_id]++;
    }

    // 检验包的key是否正确
    public static function keysRight($message)
    {
        foreach (self::$keys as $item) {
            if (!isset($message[$item])) {
                return false;
            }
        }
        return true;
    }


    public function createStatusProtocol($status, $new = false)
    {
        $protocol = new Protocol();
        $protocol->id = $this->id;
        $protocol->compressed = $this->compressed;
        $protocol->encryption = $this->encryption;
        $protocol->total_class = $this->total_class;
        $protocol->little_class = $this->little_class;
        $protocol->content = $new ? "" : $this->content;
        $protocol->status = $status;
        return $protocol;
    }

    public function encode_to_json()
    {
        $array = array();
        $array["id"] = $this->id;
        $array["en"] = $this->encryption;
        $array["co"] = $this->compressed;
        $array["un"] = time();
        $array["st"] = $this->status;
        $array["bc"] = $this->total_class;
        $array["lc"] = $this->little_class;
        $array["ct"] = $this->content;
        if ($this->encryption) {
            $aes = new \aes();
            $array["ct"] = $aes->encode($array["ct"]);
        }
        if ($this->compressed) {
            $array["ct"] = gzencode(json_encode($array["ct"]));
            $array["ct"] = base64_encode($array["ct"]);
        }
        if ($json = json_encode($array)) {
            return $json;
        }
        return json_encode(array("id" => 0, "co" => 0, "en" => 0, "st" => \consts::ERROR_SYS_ERROR, "bc" => 0, "lc" => 0, "ct" => 0));
    }

//    清空全局的包id列表中当前client的包序号数据
    public static function unset_id($client_id)
    {
        unset(self::$id_list[$client_id]);
    }

    public static function getCurrentProtocol():Protocol{
        return $_SESSION["protocol"];
    }
    public static function getCurrentProtocolContent():array {
        return self::getCurrentProtocol()->getContent();
    }

//    检查content的key是否符合协议
    public function checkContent()
    {
        $big_class = $this->total_class;
        $little_class = $this->little_class;
        $const_keys = self::$content_keys[$big_class][$little_class];
        foreach ($const_keys as $key){
            if(!array_key_exists($key,$this->content)){
                throw new \Exception("",123123);
            }
        }
    }
}

