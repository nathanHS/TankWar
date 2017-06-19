<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/9
 * Time: 17:31
 */
class Item extends GameObj
{
    protected static $all_items;
    protected static $concept;
    protected static $id;
    protected static $die;
    protected static $length;
    protected static $width;
//    在地图上显现的时间长度
    protected static $show_in_map_time;

//    初始化在地图中的时间
    protected $ini_in_map_time;

    static function getAllItems()
    {
        if (!static::$all_items) {
            foreach (IniParser::getInstance()->getSavedObjects()["Entity"] as $object) {
                if (is_subclass_of($object, __CLASS__)) {
                    static::$all_items[] = get_class($object);
                }
            }
        }
        return static::$all_items;
    }

    function __construct($attr)
    {
        $this->ini_in_map_time = getMillisecond();
        parent::__construct($attr);
    }

    function checkAlive()
    {
        if (getMillisecond() > $this->ini_in_map_time + $this->getShowInMapTime()) {
            $this->destroy();
        }
    }

    function destroy($update = true)
    {
        parent::destroy($update);
        $this->getMap()->deleteItemIndex($this);
    }

    function currentInfoArray()
    {
        $return_array = parent::currentInfoArray();
        $return_array["cnt"] = $this->getShowInMapTime();
//        初始化时间
//        $return_array["it"] = $this->getIniInMapTime();
        return $return_array;
    }

    function objAddedToMap($map)
    {
        $map->addObjToItemContainer($this);
        return parent::objAddedToMap($map);
    }

    /**
     * @return mixed
     */
    public function getShowInMapTime()
    {
        return $this->show_in_map_time??static::$show_in_map_time;
    }

    /**
     * @param mixed $show_in_map_time
     */
    public function setShowInMapTime($show_in_map_time)
    {
        $this->show_in_map_time = $show_in_map_time;
    }

    /**
     * @return float
     */
    public function getIniInMapTime(): float
    {
        return $this->ini_in_map_time;
    }

    /**
     * @param float $ini_in_map_time
     */
    public function setIniInMapTime(float $ini_in_map_time)
    {
        $this->ini_in_map_time = $ini_in_map_time;
    }

}