<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/8
 * Time: 11:48
 */

/**
 * 获得当前时间的毫秒级别的时间戳
 * @return float
 */
function getMillisecond() {
    list($t1, $t2) = explode(' ', microtime());
    return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
}

//function objarray_to_array($obj) {
//    $ret = array();
//    foreach ($obj as $key => $value) {
//        if (gettype($value) == "array" || gettype($value) == "object"){
//            $ret[$key] =  objarray_to_array($value);
//        }else{
//            $ret[$key] = $value;
//        }
//    }
//    return $ret;
//}
//
//function object_to_array($obj){
//    $_arr = is_object($obj) ? get_object_vars($obj) :$obj;
//    foreach ($_arr as $key=>$val){
//        if (is_array($val)){
//            $val = objarray_to_array($val);
//        }
//        if ( is_object($val)){
//            $val= object_to_array($val);
//        }
//        $arr[$key] = $val;
//
//    }
//    return $arr;
//}

/**
 * 概率为p的事件（精度0.0001）
 * @param $p
 *
 * @return bool
 */
function p_happen($p){
    return random_int(0,1 * 10000) < $p * 10000;
}

/**
 * 构造一个正菱形
 * @param $point_x int 中心点x
 * @param $point_y int 中心点y
 * @param $r int 2r + 1 等于 对角线
 *
 * @return array 菱形的每一个点的x,y
 */
function getAllPositionOfLozenge($point_x, $point_y, $r){

    $max_x_R = $point_x + $r;
    $min_x_R = $point_x - $r;
//    容纳菱形坐标的容器
    $positions=array();
//    计算出菱形的所有坐标
    for ($x = $min_x_R,$y = $point_y,$dy = 1;$x <= $max_x_R; $x++,$y = $y + $dy){
//        如果过半则把增量dy设成-1
        if ($point_x <= $x)
            $dy = -1;
//        某一个x下的上半个[三角形]的最大y一定是y,最小y一定是point_y
        $min_y = $point_y;
        $max_y = $y;
        for ($tmp_y = $min_y - ($max_y - $min_y); $tmp_y <= $max_y; $tmp_y++)
            $positions[] = array($x,$tmp_y);
    }
    return $positions;
}

