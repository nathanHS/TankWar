<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);

use AnalysisQues\Analyzer;
use AnalysisQues\Protocol;
use DbOperate\RedisInstance;
use GatewayWorker\Lib\Gateway;

require_once __DIR__ . '/Const/consts.php';
require_once __DIR__ . '/Logical/Logical.php';

/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{


    public static function onWorkerStart($BusinessWorker)
    {

        require_once __DIR__ . "/AnalysisQues/Analyzer.php";
//        自动加载类
        require_once __DIR__ . DIRECTORY_SEPARATOR . "Loader.php";
        findFile(__DIR__ . "/../..");

        $redis = new Redis();
        $redis->pconnect("0.0.0.0", 6380);
        RedisInstance::saveRedis($redis);
        Protocol::setContentKey();


    }

    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     *
     * @param int $client_id 连接id
     */
    public static function onConnect($client_id)
    {
//        $transmit = Protocol::createToNewProtocol(consts::CLASS_TEST,consts::CLASS_GAME_CLIENT_ID_BIND,array("uid"=>$client_id))->encode_to_json();
//        Gateway::sendToCurrentClient($transmit);
    }


    public static function onMessage($client_id, $message)
    {
        try {
            //
//            反序列化数据包
            $protocol = Protocol::createFromProtocol($client_id, $message);
//            将反序列化的对象存放在【与一个CONNECTION生命周期一致】的SESSION里
            $_SESSION["protocol"] = $protocol;
//            根据业务逻辑解析数据包【返回正确状态下要传送给对方的未序列化】数据包
            $protocol_to = Analyzer::analyze();
//            当成功执行请求，但无数据需要返回给当前连接对象时（如：给请求加对方为好友）返回null，否则
            if ($protocol_to) {
                $back_message = $protocol_to->encode_to_json();
                Gateway::sendToCurrentClient($back_message);
            }
//            捕捉分析是根据【业务逻辑】判断的数据包异常
        } catch (Exception $e) {
//            恶意数据或ping
            if ($e->getMessage() == consts::IGNORE) {
                return;
            }
//            根据异常的$code参数生成相应的数据包状态
            $protocol = Protocol::getCurrentProtocol()->createStatusProtocol($e->getCode());
//            发送
            Gateway::sendToCurrentClient($protocol->encode_to_json());
//            严重异常时，在异常中配置CLOSE_CONNECTION的message
            if ($e->getMessage() == consts::CLOSE_CONNECTION) {
//                断开当前连接
                Gateway::closeCurrentClient();
            }
//            回滚数据库写入操作
            RedisInstance::get()->discard();
        }
    }


    /**
     * 当用户断开连接时触发
     * @param int $client_id 连接id
     *
     */
    public static function onClose($client_id)
    {
        if (isset($_SESSION["protocol"])) {
            Analyzer::dealUserClose($client_id);
        }
    }

    public static function onWorkerStop($BusinessWorker)
    {
        $users = Gateway::getAllClientInfo();
        foreach ($users as $client_id => $sth) {
            Gateway::closeClient($client_id);
        }
    }
}

//$a = date("Y-m-d H:i:s");
//echo $a;

//$a  =array();
//var_dump(array_shift($a));

//$a = IniParser::getInstance()->createDefault("Entity","Bullet");

//echo 1;
//Events::onConnect(1);
//$tank = IniParser::getInstance()->createDefault("Entity", "PlayerTank");
//echo 1;
//Events::onConnect(1);
//Events::onConnect(1);
//Events::onConnect(1);
//$a = IniParser::getInstance();
//$a = array(array(1));
//$b = $a[0];
//$b[0] = 2;
//var_dump($a);
//echo (string)RedisInstance::get()->get("ASdasdasd");
////Events::onMessage(1,'{"id":0,"en":0,"co":0,"un":4545156,"st":0,"bc":1,"lc":1,"ct":"{"pn":"1","uid":"3","ssid":1}"}');
//var_dump(RedisInstance::get()->zRange("a",0,-1));
//var_dump((int)"false");