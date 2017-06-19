<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/8
 * Time: 12:09
 */
class GameObj
{

    /*
     * 全局访问的属性
     */
    protected static $concept;
    protected static $id;
    protected static $die;
    protected static $length;
    protected static $width;


    /*
     * 大小信息
     */

    private $_id = "";
    private $map;
    protected $x = -10;
    protected $y = -10;

    /**
     * 通过类 id 获得类字符串
     * @param $class_id int
     * @return string
     *
     */
    public static function getClassStrByClassId(int $class_id) {

        $save_objects = IniParser::getInstance()->getSavedObjects()["Entity"];

        /** @var GameObj $obj */
        foreach ($save_objects as $obj) {
            if ($obj->getId() == $class_id)
                return get_class($obj);
        }
        return "";
    }

    public static function getClassIdByClassStr(string $class_str){
        if (class_exists($class_str) && in_array(GameObj::class, class_parents($class_str)))
            return $class_str::getId();
        return 0;
    }

    /**
     * @return mixed
     */
    public static function getLengthStatic()
    {
        return static::$length;
    }
    /**
     * @return mixed
     */
    public static function getWidthStatic()
    {
        return static::$width;
    }

    /**
     * @param mixed $length
     */
    public function setLength($length)
    {
        $this->length = $length;
    }

    public function setWidth($width)
    {
        $this->width = $width;
    }

    function get_Id()
    {
        return $this->_id;
    }

    function set_Id($id)
    {
        if ($id) {
            $this->_id = $id;
        }
    }


    public static function getId()
    {
        return static::$id;
    }

    function getWidth()
    {
        if (isset($this->width)) {
            return $this->width;
        }
        return static::$width;
    }

    function getLength()
    {
        if (isset($this->length)) {
            return $this->length;
        }
        return static::$length;
    }

    function __construct($attr)
    {
        $reflect = new ReflectionClass($this);
        $pros = $reflect->getDefaultProperties();
        $obj_vars = get_object_vars($this);

        $this->_id = uniqid();
        $static_attr = $attr["static"];
        $no_static_attr = $attr["no_static"];

        foreach ($static_attr as $attr_name => $attr_value) {
            if (array_key_exists($attr_name, $pros) && !array_key_exists($attr_name, $obj_vars))
                static::$$attr_name = $attr_value;
        }
        foreach ($no_static_attr as $attr_name => $attr_value) {
            $this->$attr_name = $attr_value;
        }

//        foreach ($attr as $attr_name => $attr_value) {
//            if (array_key_exists($attr_name, $obj_vars) ) {
//                $this->$attr_name = $attr_value;
//            } else if (key_exists($attr_name, $pros)) {
//                static::$$attr_name = $attr_value;
//            }
//        }
    }

    public static function getConcept()
    {
        return static::$concept;
    }
//    public static function getStaticConcept(){
//        return static::$concept;
//    }
    /**
     * @return int
     */
    public function getX()
    {
        return $this->x;
    }

    /**
     * @param int $x
     */
    public function setX($x)
    {
        $this->x = $x;
        return $this;
    }

    /**
     * @return int
     */
    public function getY()
    {
        return $this->y;
    }

    /**
     * @param int $y
     */
    public function setY($y)
    {
        $this->y = $y;
        return $this;
    }


    public function currentInfoArray()
    {
        return array("x" => $this->getX(), "y" => $this->getY(), "ty" => $this->getId(), "id" => $this->get_Id(), "die" => (int)$this->getDie());
    }

    /**
     * 调试矩阵
     *
     * @param $map Map
     */
    public function varDumpPosition($map)
    {
        echo "id:" . $this->get_Id() . "\n";
        echo "x:" . $this->getX() . "y:" . $this->getY() . "\n";
        foreach ($map->getMatrix()[3] as $x => $every_x) {
            /** @var GameObj $obj */
            foreach ($every_x as $y => $obj) {
                if ($obj->_id == $this->_id) {
                    echo "(" . $x . "," . $y . ")";
                }
            }
        }
        echo "\n";
    }


    public function setMap($map)
    {
        $this->map = $map;
        return $this;
    }

    public function destroy($update=true)
    {
        $this->setDie();
        /** @var Map $map */
        $map = $this->map;
        $map->deleteAllObjIndex($this);
        $map->setObjectOutOfMatrix($this);
        \Workerman\Lib\Timer::del($this->timer_id);
        if ($update){
            $this->updateMe();
        }
        return $this;
    }

//    返回该Obj在矩阵中的所有点
    public function getAllPoint($except_x = null,$except_y = null){
        $return_array = array();
        for($i = 0;$i < $this->getWidth(); $i++){
            for ($j = 0; $j < $this->getLength(); $j++){
                $return_array[$i+$this->getX()][$j+$this->getY()] = 1;
            }
        }
        if ($except_y && $except_x){
            unset($return_array[$except_x][$except_y]);
        }
        return $return_array;
    }

    public function setDie($die_flag = 1)
    {
        $this->die = $die_flag;
        return $this;
    }

//    public static function getStaticWidth()
//    {
//        return static::$width;
//    }
//
//    public static function getStaticLength()
//    {
//        return static::$length;
//    }

    /**
     * @return Map
     */
    public function getMap()
    {
        return $this->map;
    }

    public function isAlive()
    {

        return !($die = $this->getDie());
    }

    function __toString()
    {
        // TODO: Implement __toString() method.
        return (string)$this->_id;
    }



    /**
     * @param $map Map
     */
    public function objAddedToMap($map)
    {
        $map->addObjToAllObjContainer($this);
        $map->addObjToMatrix($this, $this->getLength(), $this->getWidth(), $this->x, $this->y, $this->getConcept());
        return $this;
    }

    public function updateMe()
    {
        $this->getMap()->getGame()->addUpdateInfo($this);
        return $this->getMap()->getGame();
    }


    /**
     * @param mixed $timer_id
     */
    public function setTimerId($timer_id)
    {
        $this->getMap()->timer_ids[$this->get_Id()] = $this->timer_id = $timer_id;
        return $this;
    }

    public function getTimerId()
    {
        return isset($this->timer_id) ? $this->timer_id : null;
    }

    private function getDie()
    {
        if (isset($this->die)) {
            return $this->die;
        }
        return static::$die;
    }

    /**
     * 获得所有该物体的坐标点
     * @return  array
     */
    public function getPoints()
    {
        $ret = array();
        for ($i = 0; $i < $this->getWidth(); $i++){
            for ($j = 0; $j < $this->getLength(); $j++){
                $ret[] = array($this->x+$i,$this->y+$j);
            }
        }
        return $ret;
    }
    /**
     * @param $game_object GameObj
     *
     * @return int
     */
    public function theGameObjectPositionOfMe($game_object)
    {
        $me_min_x = $this->x;
        $me_min_y = $this->y;
        $me_max_x = $me_min_x - 1 + $this->getWidth();
        $me_max_y = $me_min_y - 1 + $this->getLength();

        $tank_min_x = $game_object->getX();
        $tank_min_y = $game_object->getY();
        $tank_max_x = $tank_min_x - 1 + $game_object->getWidth();
        $tank_max_y = $tank_min_y - 1 + $game_object->getLength();

//        上下
        if ($me_min_x >= $tank_min_x && $me_min_x <= $tank_max_x || $me_max_x >= $tank_min_x && $me_max_x <= $tank_max_x) {
            if ($tank_min_y > $me_max_y) return 1;
            if ($me_min_y > $tank_max_y) return 2;
        }
        if ($me_min_y >= $tank_min_y && $me_min_y <= $tank_max_y || $me_max_y >= $tank_min_y && $me_max_y <= $tank_max_y) {
            if ($tank_min_x > $me_max_x) return 4;
            if ($me_min_x > $tank_max_x) return 3;
        }
        return 0;
    }
}

