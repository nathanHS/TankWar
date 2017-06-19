<?php


class consts
{
    /*
     *标准错误
     */
//    包结构错误
    const ERROR_BAD_REQUEST = 1;
//    找不到大类id
    const ERROR_HAS_NO_BIG_CLASS = 2;
//    找不到小类id
    const ERROR_HAS_NO_LITTLE_CLASS = 3;
//    账号密码错误
    const ERROR_WRONG_KEY_OR_PWD = 4;
//    在游戏内还没有注册
    const ERROR_HAS_NO_RIGST = 5;
//    系统错误
    const ERROR_SYS_ERROR = 6;
//    场景错误
    const ERROR_SCENE_ERROR = 7;
//    身份证重复注册
    const ERROR_REPEAT_ID_CARD = 8;
//    目标id不存在
    const ERROR_TARGET_ID_NOT_EXISTS = 9;
//    已经是好友
    const ERROR_HAS_IT_FRIEND = 10;
//    请求标志出错
    const ERROR_WRONG_QID = 11;
//    已经有请求过加好友了
    const ERROR_HAS_ADD_REQUEST_ALREADY = 12;
//    一个设备只能一个账号在线
    const ERROR_ONLY_ONE_ACCOUNT = 13;
//    尝试家自己为好友
    const ERROR_ADD_SELF = 14;
//    用户从别处登陆
    const ERROR_USER_LOGIN_OTHER_PLACE = 15;
//    未登录
    const ERROR_LOGIN_PLEASE = 16;
//    注册存在的UID
    const ERROR_RIGIST_REPETED_UID = 17;
//    这个人没有请求加你
    const ERROR_NO_SUCH_A_PERSON_ADD_YOU_OR_IT_EXPIRED = 18;
//    大厅人数已满
    const ERROR_HALL_IS_FULL = 19;
//    不存在的大厅号
    const ERROR_WRONG_HID = 20;
//    防止出现重身份证请重试
    const ERROR_TRY_AGAIN = 21;
//    桌子上的玩家满了
    const ERROR_TABLE_PLAYER_FULL = 22;
//    正在游戏中
    const ERROR_TABLE_IS_GAMING = 23;
//    桌子上有人
    const ERROR_TABLE_HAS_PEOPLE = 24;
//    不在座位上
    const ERROR_NO_IN_TABLE = 25;
//    正在游戏中
    const ERROR_YOU_ARE_GAMING = 26;
//    你不是房主
    const ERROR_YOU_ARE_NOT_HOST = 27;
//    对方不在这个桌子上
    const ERROR_TARGET_ID_AT_TABLE = 28;
//    当前桌不在游戏中
    const ERROR_TABLE_NOT_GAMING = 29;
//    桌子上没人
    const ERROR_TABLE_NO_PEOPLE = 30;
//    SESSION错误
    const ERROR_SESSION_ERROR = 31;
//    充值的游戏币不能兑换成整数个人民币
    const ERROR_CHARGE_MOT_INT_TIMES = 32;
//    数据库中人民币不够
    const ERROR_NOT_ENOUGH_MONEY = 33;
//    这个道具没有在售
    const ERROR_NOT_SUCH_ITEM = 34;
//    游戏币不够
    const ERROR_NOT_SUCH_GAME_MONEY = 35;
//    你现在还没用完这个道具
    const ERROR_ALREADY_HAVE_THIS_ITEM = 36;
//    没有这个好友
    const ERROR_NO_SUCH_A_FRIEND = 37;
//    好友不在线
    const ERROR_FRIEND_NOT_ONLINE = 38;



    /*********************************************错误信息*********************************************/
    /*
     * 标准成功
     */
    const SUCCESS = 0;

    /*
     * 大类
     */
    const REQUEST_CLASS_USER = 1;
    const REQUEST_CLASS_USER_LOGIN = 1;
    const REQUEST_CLASS_USER_LOGOUT = 2;
    const REQUEST_CLASS_USER_REGIST = 3;
    const REQUEST_CLASS_USER_SEARCH_BY_UID = 4;
    const REQUEST_CLASS_USER_MY_INFO = 5;
    const REQUEST_CLASS_USER_SET_INFO = 6;

    /*
     * FRIEND
     */
    const REQUEST_CLASS_Friend = 2;
    const REQUEST_CLASS_Friend_ADD = 1;
    const REQUEST_CLASS_Friend_REPLY_ADD = 2;
    const REQUEST_CLASS_Friend_List_Request = 3;
    const REQUEST_CLASS_FRIEND_TALK = 5;
    const REPLY_CLASS_Friend_ADD = 1;
    const REPLY_CLASS_Friend_REPLY_ADD = 2;
    const REPLY_CLASS_FRIEND_INFO_FLUSH = 4;
    const REPLY_CLASS_FRIEND_YOU_GET_A_MESSAGE_FROM_FRIEND = 6;
    /*
     * HALL
     */
    const REQUEST_CLASS_HALL = 3;
    const REQUEST_CLASS_HALL_CURRENT_INFO = 1;
    const REQUEST_CLASS_HALL_CHOOSE_HALL = 2;
    const Reply_CLASS_HALL_CURRENT_INFO = 1;
    const CLASS_HALL_PEOPLE_FLUSH = 3;
    const CLASS_HALL_CHOOSE_TABLE = 4;
    const CLASS_HALL_USER_LEAVE_TABLE = 7;
    const CLASS_HALL_USER_PREPARE = 5;
    const CLASS_HALL_USER_ANTI_PREPARE = 6;
    const CLASS_HALL_KICK_OUT_PEOPLE = 8;
    const CLASS_HALL_NEW_VISITOR_IN = 9;
    /*
     * GAME
     */
    const CLASS_GAME = 4;
    const CLASS_GAME_LEVEL = 1;
    const GAME_CURRENT_ALL_INFO = 2;
    const GAME_INFO_FLUSH = 3;
    const CLASS_GAME_MOVE = 4;
    const CLASS_GAME_SHOOT_BULLET = 6;
    const CLASS_GAME_CHANGE_POSITION = 7;
    const CLASS_GAME_DECIDE_NEXT_LEVEL = 8;
    const CLASS_GAME_ONE_PLAYER_PREPARE_GAME = 9;
    const CLASS_GAME_DESTROY_SIGNAL = 10;
    const CLASS_GAME_VOICE_BROADCAST = 11;
    const CLASS_GAME_RECONNECT = 12;
    const CLASS_GAME_NEW_VISITOR_IN = 13;
    const CLASS_GAME_SHOOT_SUPER_BULLET = 14;
    const CLASS_GAME_SHOOT_PAY_FOLLOW_BULLET = 15;
    const CLASS_GAME_SHOOT_PAY_MINE_BULLET = 16;
    const CLASS_GAME_MARKET_INFO = 17;
    const CLASS_GAME_MARKET_BUY_ITEM = 18;
    const CLASS_GAME_MARKET_MY_BUY_ITEM_INFO = 19;
    const CLASS_GAME_MARKET_CHARGE = 20;
    const CLASS_GAME_MARKET_MY_CHARGE_IN_PLAT = 21;


    /*
     * SESSION
     */
    const CLASS_SESSION = 5;
    const CLASS_SESSION_SET_SESSION = 1;
    /********************************************* 协议大小类 *********************************************/
    /*
     * 场景
     */
//    仅仅是连接上
    const SCENE_BEFORE_EVERYTHING = 0;
//    登陆成功但没有在游戏内部注册
    const SCENE_HAS_LOGIN_NO_REGIST = 1;
//    成功进入游戏
    const SCENE_HAS_LOGIN = 2;
//    已经进入大厅
    const SCENE_HALL = 3;
//    已经进入游戏
    const SCENE_GAMING = 4;
//    关闭连接
    const CLOSE_CONNECTION = "close";
//    忽略
    const IGNORE = "ignore";

    /********************************************* 场景 *********************************************/
}
