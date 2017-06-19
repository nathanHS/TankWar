<?php
/**
 * Created by PhpStorm.
 * User: nil
 * Date: 2016/11/12
 * Time: 17:23
 */

use Workerman\Autoloader;
use Channel\Client;

//忽略对象写静态属性导致的Notice
error_reporting(E_ALL ^ E_NOTICE);
ini_set('memory_limit', '10000M');
// 自动加载类
require_once __DIR__ . '/Logical/Logical.php';
require_once __DIR__ . '/../../Workerman/Autoloader.php';
Autoloader::setRootPath(__DIR__);
require_once __DIR__ . DIRECTORY_SEPARATOR . "Loader.php";
findFile(__DIR__ . "/../..");


$server = new Channel\Server();
$timer_worker = new \GatewayWorker\BusinessWorker();
$timer_worker->name = 'Timer';
$timer_worker->registerAddress = '127.0.0.1:1238';
$timer_worker->onWorkerStart = function () {
    start();
};
$GLOBALS["huanguan"] = 1;

/**
 * 注册Chanel的回调
 */
function start()
{
    Client::on(consts::CLASS_GAME_LEVEL, function ($content) {
        wantGame($content["player_arr"], $content["group_id"], $content["level"]);
    });
    Client::on(consts::CLASS_GAME_ONE_PLAYER_PREPARE_GAME, function ($content) {
        prepareGame($content["id"]);
    });
    Client::on(consts::CLASS_GAME_MOVE, function ($content) {
        tankMove($content["id"], $content["po"]);
    });
    Client::on(consts::CLASS_GAME_SHOOT_BULLET, function ($client) {
        playerShoot($client);
    });
    Client::on(consts::CLASS_GAME_SHOOT_SUPER_BULLET, function ($client) {
        playerSuperShoot($client);
    });
    Client::on(consts::CLASS_GAME_SHOOT_PAY_FOLLOW_BULLET, function ($client) {
        playerPayFollowShoot($client);
    });
    Client::on(consts::CLASS_GAME_SHOOT_PAY_MINE_BULLET, function ($uid) {
        playerPayMineSet($uid);
    });
    Client::on(consts::CLASS_GAME_CHANGE_POSITION, function ($content) {
        playerChangeFaceTo($content["id"], $content["po"]);
    });
    Client::on(consts::CLASS_GAME_DECIDE_NEXT_LEVEL, function ($content) {
        playerChooseNextLevel($content["id"]);
    });
    Client::on(consts::CLASS_GAME_DESTROY_SIGNAL, function ($content) {
        destroyGameBySignal($content["id"]);
    });
    Client::on(consts::CLASS_GAME_VOICE_BROADCAST, function ($content) {
        broadCastVoice($content["id"], $content["content"],$content["client_id"]);
    });
    Client::on(consts::CLASS_GAME_RECONNECT, function ($content) {
        reconnect($content["id"], $content["client_id"]);
    });
    Client::on(consts::CLASS_GAME_NEW_VISITOR_IN, function ($content) {
        newVisitorIn($content["client_id"], $content["uid"], $content["player"]);
    });
    \Workerman\Lib\Timer::add(120,"listenPayBulletLifeTime");
    IniParser::getInstance()->fromXmlReadMap(1);
    IniParser::getInstance()->saveAllObjInstance("Entity");
}

/**
 * 如果花钱购买的道具过期了就删除
 */
function listenPayBulletLifeTime(){
    $market = Market::getMarketInstance();
    $who_have_pay_item = $market->getWhoHavePayItem();

    foreach ($who_have_pay_item as $uid){
        /** @var MarketItem $item */
        foreach ($market->getItemsHeBought($uid) as $class_name => $item)
        {
//            如果道具过期了
            if (getMillisecond()/1000 > $item->getBuyTime()/1000 + 24 * 3600  * $item->getTime())
            {
                echo "过期了！！！\n";
                $market->removeItemBought($uid,$class_name);
//                如果他一个道具都不剩了，就清空关于他的索引
                if (empty($market->judgeHeBuyItemOrNot($uid))){
                    $market->removeHeBoughtItemRecord($uid);
                }
            }
        }
    }
}

/**
 * 新观战者加入
 *
 * @param $client_id
 * @param $uid
 * @param $player
 */
function newVisitorIn($client_id, $uid, $player)
{
    echo "进入观战\n";
    $game = Game::findGameByPlayerId($player);
    if (!$game) {
        echo "观战失败\n";
        return;
    }
        
    $group_id = $game->getGroupId();
    $game->addVisitor($uid);
    $protocol_to = \AnalysisQues\Protocol::createToNewProtocol(consts::CLASS_GAME, consts::CLASS_GAME_NEW_VISITOR_IN, array("info" => (new User($uid))->getAllInfo()));
    \GatewayWorker\Lib\Gateway::sendToGroup($group_id, $protocol_to->encode_to_json());
    \GatewayWorker\Lib\Gateway::joinGroup($client_id, $group_id);
    \GatewayWorker\Lib\Gateway::sendToUid($uid, \AnalysisQues\Protocol::createToNewProtocol(consts::CLASS_GAME, consts::GAME_CURRENT_ALL_INFO, $game->currentInfoArray())->encode_to_json());
}

/**
 * 广播语音
 *
 * @param $uid
 * @param $content
 * @param $client_id
 */
function broadCastVoice($uid, $content,$client_id)
{
    $game = Game::findGameByPlayerId($uid);
    \GatewayWorker\Lib\Gateway::sendToGroup($game->getGroupId(), \AnalysisQues\Protocol::createToNewProtocol(consts::CLASS_GAME, consts::CLASS_GAME_VOICE_BROADCAST, $content)->encode_to_json(),[$client_id]);
}

/**
 * 外部销毁游戏的信号处理
 *
 * @param $uid
 */
function destroyGameBySignal($uid)
{
    if ($game = Game::findGameByPlayerId($uid)) {
        $game->destroy();
    }
}

/**
 *  0.1秒刷新一次数据给客户端
 */
function gameLoop()
{
//    array_map(function ($game) {
//        /** @var Game $game */
//        if (is_a($game, Game::class)) {
//            /** @var Bullet $bullet */
//            $game->flushUpdateInfo();
//            for ($i = 0; $i < 5; $i++) {
//                foreach ($game->getMap()->getBullets() as $bullet) {
//                    $bullet->move($bullet->getFaceTo());
//                }
//            }
//        }
//    }, is_array(Game::getAllGamesIndexId()) ? Game::getAllGamesIndexId() : array());
}

/**
 * 断线重连
 *
 * @param $uid
 */
function reconnect($uid, $client_id)
{
    $game = Game::findGameByPlayerId($uid);

    \GatewayWorker\Lib\Gateway::joinGroup($client_id, $game->getGroupId());

    $map = $game->getMap();
    $old_f = $map->getFinish();
    $map->setFinish(3);
    $all_info = $game->currentInfoArray();
    $map->setFinish($old_f);

    $protocol_to = \AnalysisQues\Protocol::createToNewProtocol(consts::CLASS_GAME, consts::GAME_CURRENT_ALL_INFO, $all_info);
    \GatewayWorker\Lib\Gateway::sendToUid($uid, $protocol_to->encode_to_json());
}

/**
 * 玩家加载完成
 *
 * @param $id
 */
function prepareGame($id)
{
    $game = Game::findGameByPlayerId($id);
//    观战
    if (!$game)
        return;
    if (!$game->getMap()->getStart()) {
        $game->registerPlayerOneDetailInfo($id, "prepare_game");
        if ($game->allPlayersDetailInfoRegistered("prepare_game")) {
            $game->flushPlayersPrepared();
            $game->clearAllPlayerOneDetailInfo("prepare_game");
            $game->getMap()->setStart(1);
            $game->getMap()->loop();
            $game->getMap()->saveData();
        }
    }
}

/**
 * 玩家准备
 *
 * @param $player_arr
 * @param $group_id
 * @param $level
 */
function wantGame($player_arr, $group_id, $level)
{

    echo "选关完成\n";
    $game = new Game($player_arr, $group_id, $level);

    //        每一个人加到广播租，每个人生成一个坦克
    foreach ($game->getPlayerIds() as $player_id) {
        \GatewayWorker\Lib\Gateway::joinGroup(\GatewayWorker\Lib\Gateway::getClientIdByUid($player_id)[0], $game->getGroupId());
    }
    $game->loop();
    $game->flushAllInfo();
    echo "发给两个玩家地图信息。这个时候还没有生成坦克\n";
}

/**
 * 收到命令移动
 *
 * @param $id
 * @param $po
 */
function tankMove($id, $po)
{
    if (!isset($_SESSION["last"])) {
        $_SESSION["last"] = getMillisecond();
    }


    try {
        $tank = Game::findGameByPlayerId($id)->getMap()->tank($id);
        if (isset($tank->ice_effect_timer_id)) {
            \Workerman\Lib\Timer::del($tank->ice_effect_timer_id);
            unset($tank->ice_effect_timer_id);
            unset($tank->ice_move_step);
        }
        $result = $tank->move($po);
        if (!$result) {
            echo "在start_timer里:移动失败了\n";
        }
        $tank->updateMe()->flushUpdateInfo();

        $GLOBALS["send_time"] = getMillisecond();
    } catch (Exception $e) {
        echo $e->getMessage();
    } catch (Error $e) {
        echo $e->getMessage();
    }
    $now_time_2 = getMillisecond();
    $_SESSION["last"] = $now_time_2;
}

/**
 * 发射子弹
 *
 * @param $client_id
 */
function playerShoot($client_id)
{
    try {
//    成功射出
        Game::findGameByPlayerId($client_id)->getMap()->tank($client_id)->shootNormalBullet();
    } catch (Exception $e) {
        echo $e->getMessage();
    } catch (Error $e) {
        $e->getMessage();
    }
}

/**
 * 发射跟踪弹
 *
 * @param $uid string 用户id
 */
function playerSuperShoot($uid)
{
    try {
//    成功射出
        Game::findGameByPlayerId($uid)->getMap()->tank($uid)->shootSuperBullet();
    } catch (Exception $e) {
        echo $e->getMessage();
    } catch (Error $e) {
        echo $e->getMessage();
    }
}

/**
 * 发射跟踪弹
 *
 * @param $uid string 用户id
 */
function playerPayFollowShoot($uid)
{
    try {
//    成功射出
        Game::findGameByPlayerId($uid)->getMap()->tank($uid)->shootPayFollowBullet();

    } catch (Exception $e) {
        echo $e->getMessage();
    } catch (Error $e) {
        echo $e->getMessage();
    }
}

/**
 * 改变方向
 *
 * @param $id
 * @param $po
 */
function playerChangeFaceTo($id, $po)
{
    try {
        Game::findGameByPlayerId($id)->getMap()->tank($id)->setFaceTo($po)->updateMe()->flushUpdateInfo();
    } catch (Exception $e) {
        echo $e->getMessage();
    } catch (Error $e) {
        $e->getMessage();
    }
}

/**
 * 换关
 *
 * @param $id
 */
function playerChooseNextLevel($id)
{
    try {
        echo "choose Next Level\n";
        $game = Game::findGameByPlayerId($id);
        if ($game->getMap()->getFinish() == 1) {
            $game->registerPlayerOneDetailInfo($id, "next_level");
            if ($game->allPlayersDetailInfoRegistered("next_level")) {
                $game->clearAllPlayerOneDetailInfo("next_level");
                $game->changeToNextLevel($game->getMap()->getNextLevelNum());
                gc_collect_cycles();
            }
        }
    } catch (Exception $e) {
        echo $e->getMessage();
    } catch (Error $e) {
        $e->getMessage();
    }
}

/**
 * 防止地雷堆
 *
 * @param $uid string 用户id
 */
function playerPayMineSet($uid)
{
    try {
        Game::findGameByPlayerId($uid)->getMap()->tank($uid)->shootPayMineBullet();
    } catch (Exception $e) {
        echo $e->getMessage();
    } catch (Error $e) {
        echo $e->getMessage();
    }
}





