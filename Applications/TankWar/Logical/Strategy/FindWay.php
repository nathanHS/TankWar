<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/12/23
 * Time: 10:46
 */
class FindWay
{
    protected static $ways_to_base = array();

    protected static function calculateAllWayToBase(){
        for ($i=1;$i<=4;$i++){
            /** @var Map $map */
            $map = IniParser::getInstance()->createDefault("Entity", "Map", array(
                "all_obj" => IniParser::getInstance()->fromMapInfoCreateAllMapObj($i),
                "level"=>$i
            ));
            $x_ys=PlayerTank1::getRebornPosition();
            foreach (array(0,50,100) as $from_x){
                $tank = IniParser::getInstance()->createDefault("Entity","LightTank",array("x"=>$from_x,"y"=>48));
                foreach ($x_ys as $x_y){
                    static::$ways_to_base[$map->getLevel()][$from_x][] = static::aStar($tank,$x_y[0],$x_y[1],$map);
                }
            }
        }
    }
    public static function getWayToBase($from_x,$level){
        if (!static::$ways_to_base){
           static::calculateAllWayToBase();
        }
        return static::$ways_to_base[$level][$from_x][array_rand(static::$ways_to_base[$level][$from_x],1)];
    }

    /**
     * @param $from_game_obj Block
     * @param $to_x
     * @param $to_y
     * @param $map       Map
     *
     * @return array
     */
    static function aStar($from_game_obj, $to_x, $to_y, $map)
    {

        $open = array();
        $close = array();
        $matrix = array();
        $start_point = $point = array($from_game_obj->getX(), $from_game_obj->getY());
        foreach (range(0,Map::getWidthStatic()) as $index_width){
            foreach (range(0,Map::getLengthStatic()) as $index_length){
                $matrix[$index_width][$index_length]["G"] = 0;
                $matrix[$index_width][$index_length]["H"] = 0;
            }
        }


        $terminal_point = array($to_x, $to_y);
        $open[] = $point;
        $matrix[$point[0]][$point[1]]["p"] = $point;

        while ($point != $terminal_point && is_array($open) && $open) {
            foreach ($open as $index => $x_y){
                $min_x_y_in_open = $x_y;
                $min_index = $index;
                break;
            }
//            寻找open列表中的最小节点
            foreach ($open as $index => $x_y){
                /** @var array $min_x_y_in_open */
                $min_x = $min_x_y_in_open[0];
                $min_y = $min_x_y_in_open[1];
                $x = $x_y[0];
                $y = $x_y[1];
                if ($matrix[$x][$y]["G"]+$matrix[$x][$y]["H"] < $matrix[$min_x][$min_y]["G"]+$matrix[$min_x][$min_y]["H"]){
                    $min_x_y_in_open = $x_y;
                    $min_index = $index;
                }
            }
//            把他从open列表删除，并加入close列表,并把它作为当前节点
            /** @var int $min_index */
            /** @var array $min_x_y_in_open */
            unset($open[$min_index]);
            $close[] = $min_x_y_in_open;
            $point = $min_x_y_in_open;

//            找到当前节点周围的可以通行的节点
            $movable_points_around = static::getMovablePointsAround($point, $from_game_obj, $map);
            foreach ($movable_points_around as $movable_point){
                $x = $movable_point[0];
                $y = $movable_point[1];
//                如果在close列表里
                if (in_array($movable_point,$close)){
                    continue;
                }else{
                    $g = $matrix[$point[0]][$point[1]]["G"] + 1;
                    $h = abs($x - $terminal_point[0]) + abs($y - $terminal_point[1]);
                    if (!in_array($movable_point,$open)){
                        $open[] = $movable_point;
                        $matrix[$x][$y]["p"] = $point;
                        $matrix[$x][$y]["G"] = $g;
                        $matrix[$x][$y]["H"] = $h;
                    }
                    else{
                        if ($g < $matrix[$x][$y]["G"]){
                            $matrix[$x][$y]["G"] = $g;
                            $matrix[$x][$y]["p"] = $point;
                        }
                    }
                }
            }
        }

        $way_points = array($terminal_point);
        $child = $terminal_point;
        $father = $matrix[$terminal_point[0]][$terminal_point[1]]["p"];
        while ($child != $father){
            $way_points[]=$father;
            $child = $father;
            $father = $matrix[$father[0]][$father[1]]["p"];
        }
        $way_points = array_reverse($way_points);
        unset($way_points[0]);
        $way = array();
        $this_point = $start_point;
        foreach ($way_points as $next_point){
            if ($next_point[0] - $this_point[0] < 0){
                $way[] = 3;
            }
            if ($next_point[0] - $this_point[0] > 0){
                $way[] = 4;
            }
            if ($next_point[1] - $this_point[1] < 0){
                $way[] = 2;
            }
            if ($next_point[1] - $this_point[1] > 0){
                $way[]= 1;
            }
            $this_point = $next_point;
        }
        return $way;
    }

    /**
     * 获得某个点周围可以移动的点
     *
*@param $point     array
     * @param $from_game_obj Block
     * @param $map       Map 没有任何坦克的Map
     *
     * @return array
     */
    static function getMovablePointsAround($point, $from_game_obj, $map)
    {
        $movable_points = array();
        $tank_before_x = $from_game_obj->getX();
        $tank_before_y = $from_game_obj->getY();
        $from_game_obj->setX($point[0])->setY($point[1]);

//        对于每一个方向
        foreach (array(1, 2, 3, 4) as $each_direction) {
//            如果不是在边界上
            if (($x_y = $from_game_obj->oneStep($each_direction)) != $point) {
//                如果没有碰撞到任何东西
                if(!$all_collision_objects = $map->computeCollision($x_y[0], $x_y[1], $from_game_obj->getWidth(), $from_game_obj->getLength(), Block::getConcept())){
                    $movable_points[]=$x_y;
                }
            }
        }
        $from_game_obj->setX($tank_before_x)->setY($tank_before_y);
        return $movable_points;
    }

}






