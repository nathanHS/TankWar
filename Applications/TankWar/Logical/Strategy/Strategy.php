<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/12/22
 * Time: 14:35
 */
class Strategy
{
//    系数增加
    const tank_in_map_max_ratio = array(4, 1.05); //初始值，每增加一关地图上一共可以显示的坦克数目的倍数（向下取整，下同）
    const tank_all_ratio = array(10, 1.1); // 每增加一关一共需要击杀的坦克数目
    const tank_shoot_probability = array("LightTank" => array(0.4, 1.2), "HeavyTank" => array(0.3, 1.2), "LightQuickTank" => array(0.3, 1.2), "HeavyQuickTank" => array(0.3, 1.2)); // 每个坦克射击概率和概率递增倍数
    const all_tank_type_choice = array(
        array("LightTank" => 35, "HeavyTank" => 35, "LightQuickTank" => 15, "HeavyQuickTank" => 15),
        array("LightTank" => 25, "HeavyTank" => 45, "LightQuickTank" => 15, "HeavyQuickTank" => 15),
        array("LightTank" => 25, "HeavyTank" => 35, "LightQuickTank" => 25, "HeavyQuickTank" => 15),
    ); // 每关坦克总量概率总共分配情况（暂定三种概率分配情况）

//    ai
    const ai_tank_strategy = array("rand" => array(50, 1), "base" => array(1, 1.1)); //每个策略的初始比例，和每关后的增速

    /**
     * 获取这关最多的地图上总共出现的PC坦克数目
     *
     * @param $level
     *
     * @return int
     */
    static function getMaxTankInMap($level)
    {
        echo "一个地图上最多的坦克数目：".(int)self::tank_in_map_max_ratio[0] * pow(self::tank_in_map_max_ratio[1], $level - 1)."\n";
        return (int)self::tank_in_map_max_ratio[0] * pow(self::tank_in_map_max_ratio[1], $level - 1);
    }

    /**
     * 获取这一关的坦克总量
     *
     * @param $level
     *
     * @return int
     */
    static function getLevelTankNum($level)
    {
        return (int)(self::tank_all_ratio[0] * pow(self::tank_all_ratio[1], $level - 1));
    }

    /**
     * 根据当前关卡决定生成一个符合某种概率特征的坦克类型
     *
     * @param $level
     *
     * @return int|string
     */
    static function getCurrentTankTypeByProbability()
    {
        $choice_type = random_int(0, sizeof(self::all_tank_type_choice) - 1);
        if ($result = self::baseMakeChoiceByProbability(self::all_tank_type_choice[$choice_type]))
            return $result;
        return "LightTank";
    }

    /**
     * 根据当前关卡决定生成一个符合某种概率特征的坦克AI策略
     *
     * @param $level
     *
     * @return int|string
     */
    static function getCurrentTankAiTypeByProbability($level)
    {
        $prob_array = array();
        foreach (self::ai_tank_strategy as $type => $base_ratio_array)
            $prob_array[$type] = $base_ratio_array[0] * pow($base_ratio_array[1], $level - 1);
        if ($result = self::baseMakeChoiceByProbability($prob_array))
            return $result;
        return "rand";
    }

    /**
     * @param $array array 这个参数符合array("key1"=>比重1，"key2"=>比重2 .. )的特征
     *
     * @return int|string 返回是其中一个key
     */
    static function baseMakeChoiceByProbability($array)
    {
        $base_value = 0;
        $target_section = array();
        foreach ($array as $key => $probability)
            $target_section[$key] = array($base_value, ($base_value += $probability));

        $result = random_int(0, $base_value);
        foreach ($target_section as $key => $section) {
            if ($result >= $section[0] && $result <= $section[1])
                return $key;
        }
        return "";
    }

    /**
     * @param $tank_str
     * @param $level
     *
     * @return int
     */
    static function getShootProbability($tank_str, $level)
    {
        $p_array = static::tank_shoot_probability[$tank_str];
        return ($result = $p_array[0] * pow($p_array[1], $level - 1)) > 1 ? 1 : $result;
    }




}