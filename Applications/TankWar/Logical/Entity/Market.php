<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/3
 * Time: 10:46
 */
class Market
{
    private static $_instance = null;
    protected $money_ratio = 1000; // 1元等于多少游戏币
    protected $items = array();
    protected $pre_user_market_info = "!PayItem"; // 用户支付信息（一个hash表）的key的前缀，完整的key是前缀连接用户id
    protected $hash_key_game_money = "!game_money"; // 玩家游戏币
    protected $key_who_have_item_set = "who_have_pay_item"; // 已购买道具的用户的索引集合

    /**
     * 获取单例
     * @return Market
     */
    public static function getMarketInstance():Market
    {
        if (!self::$_instance)
            self::$_instance = new Market();
        return self::$_instance;
    }

    /**
     * 构造:注册商品刷新回调、获取当前商品数据（先设计成变动商品由外部程序变动）
     */
    public function __construct()
    {
        $this->initItems();
    }

    public function getWhoHavePayItem()
    {
        return \DbOperate\RedisInstance::get()->sMembers($this->getKeyWhoHaveItemSet());
    }

    public function removeHeBoughtItemRecord($id)
    {
        \DbOperate\RedisInstance::get()->sRemove($this->getKeyWhoHaveItemSet(), $id);
    }

    public function addHeBoughtItemRecord($id)
    {
        \DbOperate\RedisInstance::get()->sAdd($this->getKeyWhoHaveItemSet(), $id);
    }

    private function initItems()
    {
        $attr_array = IniParser::getInstance()->getAttributes("Market", "MarketItem");
        foreach ($attr_array as $attr) {
            $this->items[$attr["class"]] = new MarketItem($attr["id"], $attr["price"], $attr["num"], $attr["time"], $attr["class"]);
        }
    }

    /**
     * 获得存储在Redis中的已购买的道具
     * @param $id string
     *
     * @return array
     *
     * ["PayFollowBullet"=>MarketItem_json;"PayMine"=>MarketItem_json]
     */
    public function getItemsHeBought($id)
    {
        $redis = \DbOperate\RedisInstance::get();
        $key = $this->getUserMarketInfoKey($id);
        $keys = $redis->hKeys($key);
        $items = array();
        foreach ($keys as $hash_key) {
            if ($hash_key == "!game_money") continue;
            $json = $redis->hGet($key, $hash_key);
            $items[$hash_key] = MarketItem::constructAItemFromJson($json);
        }
        return $items;
    }

    public function removeItemBought($id, $hash_key)
    {
        $redis = \DbOperate\RedisInstance::get();
        $redis->hDel($this->getUserMarketInfoKey($id), $hash_key);
    }

    public function addItemBought($who, $class_name, $json)
    {
        $redis = \DbOperate\RedisInstance::get();
        $redis->hSet($this->getUserMarketInfoKey($who), $class_name, $json);
    }

    /**
     * 判断用户时候还有已购买的等待查询是否过期的道具（因为余额存在同一个数组里，所以把它移除）
     * @param $id
     *
     * @return array
     */
    public function judgeHeBuyItemOrNot($id)
    {
        $redis = \DbOperate\RedisInstance::get();
        $keys = $redis->hKeys($this->getUserMarketInfoKey($id));
        if (isset($keys[$this->getHashKeyGameMoney()]))
            unset($keys[$this->getHashKeyGameMoney()]);
        return $keys;
    }

    /**
     * 更新存储道具数量
     * @param $id string
     * @param $num int
     * @param $item_name string
     */
    public function savePayItemNum($id, $item_name, $num)
    {
        if ($num < 0) $num = 0;
        /** @var MarketItem $item */
        $item = $this->getItemsHeBought($id)[$item_name];
        $item->setNum($num);
        $this->addItemBought($id, $item_name, json_encode($item));
    }


    /**
     * 充值
     *
     * @param string $who
     * @param int $game_money 游戏币
     *
     * @return int
     */
    public function charge($who, int $game_money)
    {
//        如果不是充值比例的整数倍（不能用整数金额购买）
        if (($game_money % $this->getMoneyRatio()))
            return consts::ERROR_CHARGE_MOT_INT_TIMES;

        // 开启事务
        // 如果支付数据库中没有那么多余额
        $rmb_cost = $game_money / $this->getMoneyRatio();
        if (($current_money = Market::getMarketInstance()->getRMBMoney($who)) < $rmb_cost)
           return consts::ERROR_NOT_ENOUGH_MONEY;
        $current_money -= $rmb_cost;
        Market::getMarketInstance()->setRMBMoney($who,$current_money);
        Market::getMarketInstance()->setRMBLog($who,$rmb_cost);

        // 否则（如果支付数据库中有那么多余额）
        // 扣钱
        // 关闭事务
        $this->savePlayerGameMoney($who, $this->getPlayerGameMoney($who) + $game_money);
        return consts::SUCCESS;
    }

    public function setRMBMoney($uid,$money){
        $db = new \Workerman\MySQL\Connection('121.201.33.26', '3306', 'szhhlPlat', 'MYNkO8aKqIqQufY', 'szhhlPlat');
        $row_count = $db->update('charge_current_account')->cols(array('num'))->where("uid='".$uid."'")->bindValue('num', $money)->query();
        return $row_count;
    }
    public function setRMBLog($uid,$money){
        function create_uuid($prefix = "")
        {    //可以指定前缀
            $str = md5(uniqid(mt_rand(), true));
            $uuid = substr($str, 0, 8) . '-';
            $uuid .= substr($str, 8, 4) . '-';
            $uuid .= substr($str, 12, 4) . '-';
            $uuid .= substr($str, 16, 4) . '-';
            $uuid .= substr($str, 20, 12);
            return $prefix . $uuid;
        }
        
        $db = new \Workerman\MySQL\Connection('121.201.33.26', '3306', 'szhhlPlat', 'MYNkO8aKqIqQufY', 'szhhlPlat');
        $row_count = $db->insert('charge_account_use_log')->cols(array('idcharge_account_use_log'=>create_uuid(),'uid'=>$uid,'num'=>$money,'reason'=>1,'time'=>date('Y-m-d H:i:s',time()),'game'=>"TankWar",'server'=>"1"))->query();
        return $row_count;
    }
    public function getRMBMoney($uid){
        $db = new \Workerman\MySQL\Connection('121.201.33.26', '3306', 'szhhlPlat', 'MYNkO8aKqIqQufY', 'szhhlPlat');
        $ret = $db->select('uid,num')->from('charge_current_account')->where('uid= :uid')->bindValues(array('uid'=>$uid))->query();
        $num = 0;
        if ($ret)
            $num = (int)$ret[0]['num'];
        return $num;
    }

    /**
     * 保存玩家游戏币
     * @param $who string
     * @param $money
     * @return int
     */
    public function savePlayerGameMoney($who, $money)
    {
        $redis = \DbOperate\RedisInstance::get();
        $redis->hSet($this->getUserMarketInfoKey($who), $this->getHashKeyGameMoney(), (int)$money);
    }

    /**
     * 获取玩家游戏币
     * @param $who
     *
     * @return int
     */
    public function getPlayerGameMoney($who)
    {
        $redis = \DbOperate\RedisInstance::get();
        return (int)$redis->hGet($this->getUserMarketInfoKey($who), $this->getHashKeyGameMoney());
    }

    /**
     * 获取游戏币比例
     * @return int
     */
    public function getMoneyRatio(): int
    {
        return $this->money_ratio;
    }

    /**
     * 获取玩家关于游戏币的Hash key
     * @return string
     */
    public function getHashKeyGameMoney(): string
    {
        return $this->hash_key_game_money;
    }

    /**
     * 获取玩家关于商场支付的key
     * @param $id
     * @return string
     */
    public function getUserMarketInfoKey($id): string
    {
        return $this->pre_user_market_info . $id;
    }

    /**
     * @return string
     */
    public function getKeyWhoHaveItemSet(): string
    {
        return $this->key_who_have_item_set;
    }

    public function buyPayItem($who, $item_class)
    {
        $current_game_money = $this->getPlayerGameMoney($who);

        /** @var MarketItem $item */
        if (!isset($this->items[$item_class]))
            return consts::ERROR_NOT_SUCH_ITEM;

        /** @var MarketItem $item */
        $item = $this->items[$item_class];
        $price = $item->getPrice();

        if ($current_game_money < $price)
            return consts::ERROR_NOT_SUCH_GAME_MONEY;
        $items_bought = $this->getItemsHeBought($who);
        if (isset($items_bought[$item_class]))
            return consts::ERROR_ALREADY_HAVE_THIS_ITEM;

        $this->addHeBoughtItemRecord($who);
        $new_item = $item->createAnItem();

        $this->addItemBought($who, $item_class, json_encode($new_item));
        $this->savePlayerGameMoney($who, $current_game_money - $price);
        return consts::SUCCESS;
    }

    /**
     * 序列化商场数据
     */
    public function encode()
    {
        $arr = array();
        if (!isset(IniParser::getInstance()->getSavedObjects()["Entity"]))
            IniParser::getInstance()->saveAllObjInstance("Entity");
        /** @var MarketItem $item_obj */
        foreach ($this->items as $class_name => $item_obj) {
            $arr[] = $item_obj;
            $item_obj->cid = $class_name::getId();
        }
        $ret_arr = array();
        $ret_arr["MarketInfo"] = $arr;
        return /**json_encode($ret_arr)**/ $ret_arr;
    }
}
/**
require_once "../../Loader.php";
findFile("../../../../..");


IniParser::getInstance()->saveAllObjInstance("Entity");
//require_once "MarketItem.php";
$c =  Market::getMarketInstance()->encode();
echo 1; **/
