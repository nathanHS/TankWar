<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/8
 * Time: 21:11
 */
class IniParser
{
    protected static $attribute_array = array();
    private static $ini_path = array();
    private static $ini = array();

    /**
     * @return array
     */
    public static function getIni(): array
    {
        return self::$ini;
    }
    private static $instance = null;
    private $tags = array();
    private $saved_objects = array();
    private $map_info = array();
    private static $has_ini_all_obj = array();

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new IniParser();
        }
        return self::$instance;
    }

    function __construct()
    {
        if (self::$ini == null) {
            $dir_fo = opendir(__DIR__ . DIRECTORY_SEPARATOR . "ini");
            while ($file = readdir($dir_fo)) {
                if ($file != '.' and $file != '..' and substr($file, -4) == ".xml") {
                    self::$ini_path[substr($file, 0, strlen($file) - 4)] = __DIR__ . DIRECTORY_SEPARATOR . "ini" . DIRECTORY_SEPARATOR . $file;
                }
            }
            closedir($dir_fo);

            foreach (self::$ini_path as $path_name => $path) {
                self::$ini[$path_name] = new DOMDocument("1.0", "UTF-8");
                self::$ini[$path_name]->load($path);
            }
        }


    }

    public function saveAllObjInstance($from)
    {
        $classes = $this->getAllEntityName($from, $this->getDom($from));
        foreach ($classes as $class_name) {
            $this->saved_objects[$from][$class_name] = $this->createDefault($from, $class_name);
        }
        return $this->saved_objects[$from];
    }

    /**
     * 构造默认实体对象
     *
     * @param       $from                      string ini文件夹下的xml文件的文件名（不包括.xml）
     * @param       $default_obj_type          string 类名
     * @param array $no_static_attribute_array 需要从静态属性中独立出来的非静态属性部分如（array("attr1"=> 1),attr1是一个静态属性）
     *
     * @return null | GameObj
     */
    public function createDefault($from, $default_obj_type, $no_static_attribute_array = array())
    {
//        如果这个类没有被定义过
        if (!class_exists($default_obj_type)) {
            return null;
        }
//        如果这个对象没有被初始化过
        if (!isset(self::$has_ini_all_obj[$default_obj_type])) {
//            从配置文件中获得这个对象的所有静态属性，并把它存起来
            $static_attribute_array = @$this->getAttribute($from, $default_obj_type);
            self::$attribute_array[$default_obj_type] = $static_attribute_array;
//            做一个标记
            self::$has_ini_all_obj[$default_obj_type] = true;
        } else {
//            如果初始化过，取出静态属性配置值
            $static_attribute_array = self::$attribute_array[$default_obj_type];
        }

        $attr = array("static" => $static_attribute_array, "no_static" => $no_static_attribute_array);
        return new $default_obj_type($attr);
    }


    /**
     * 以数组的方式返回来自ini/$from文件的指定TageName的所有属性[具有继承功能]。
     * @param $from string 文件名
     * @param $tag_name string 第一个标签名
     * @return array 
     */
    public function getAttribute($from, $tag_name)
    {
        // 递归栈清空时做清除操作(清空静态变量$attribute_array，防止下次调用该函数时变量里还有数据)。
        static $floor = 0;
        $item = self::$ini[$from]->getElementsByTagName($tag_name)->item(0);
        // 非最顶级节点就一直向父辈节点请求查看全部所有属性。
        if ($item->parentNode->nodeName != null) {
            $floor++;
            @$this->getAttribute($from, $item->parentNode->nodeName);
        }
        $attribute = $item->attributes;
        $attribute_length = $attribute->length;

        static $attribute_array = array();
        for ($i = 0; $i < $attribute_length; $i++) {
            $key = $attribute->item($i)->nodeName;
            $value = $attribute->item($i)->nodeValue;
            // 栈底的属性覆盖栈首的属性，实现继承覆盖功能。
            $attribute_array[$key] = (is_numeric($value)) ? floatval($value) : $value;
        }
        if ($floor == 0) {
            $tmp = $attribute_array;
            $attribute_array = array();
        } else {
            $floor--;
        }
        return $tmp;
    }

    /**
     * 单纯获得标签名为tag_name的标签集合的属性
     * @param $from
     * @param $tag_name
     *
     * @return array
     */
    public function getAttributes($from, $tag_name){
        /** @var DOMNodeList $items */
        $attributes = array();
        $items =  self::$ini[$from]->getElementsByTagName($tag_name);
        for($i = 0; $i < $items->length; $i++){
            $item_att = $items->item($i)->attributes;
            $attribute = array();
            for ($j = 0; $j < $item_att->length; $j++){
                $attribute[$item_att->item($j)->nodeName] = $item_att->item($j)->nodeValue;
            }
            $attributes[] = $attribute;
        }
        return $attributes;
    }
    public function fromMapInfoCreateAllMapObj($map_number)
    {
        $all_ga = array();
        $all_info = $this->fromXmlReadMap($map_number);
        foreach ($all_info as $each_obj_info) {
            $tmp_obj = @self::createDefault("Entity", $each_obj_info["obj"], $each_obj_info["attr"]);
            if ($tmp_obj) {
                $all_ga[$tmp_obj->get_Id()] = $tmp_obj;
            }
        }
        return $all_ga;
    }

    public function fromXmlReadMap($map_number)
    {
        // 配置文件读取
        $file_name = "level" . "$map_number";
        if (isset($this->map_info[$file_name])) {
            return $this->map_info[$file_name];
        } else {
            $tags = self::$ini[$file_name]->getElementsByTagName("gameObjects");
            for ($i = 0; $i < $tags->length; $i++) {
                $real_obj = $tags->item($i);
                $x_y = $real_obj->childNodes->item(1)->childNodes->item(1)->childNodes;
                $x = @intval($x_y->item(1)->nodeValue);
                $y = @intval($x_y->item(3)->nodeValue);
                $value = @explode(".", $real_obj->attributes->getNamedItem("asset")->nodeValue)[0];
                if ($value) {
                    $this->map_info[$file_name][] = array("obj" => $value, "attr" => array("x" => $x, "y" => $y));
                }
            }
            return $this->map_info[$file_name];
        }

    }

    public function getEntityInfo($entity_str)
    {
        $tmp = @self::getInstance()->createDefault("Entity", $entity_str);
        $info = array();
        $info["id"] = $tmp->getId();
        $info["width"] = $tmp->getWidth();
        $info["length"] = $tmp->getLength();
        $info["concept"] = $tmp->getConcept();
        return $info;
    }

    public function getAllEntityName($from, $dom)
    {
        if (!isset($this->tags[$from])) {
            $this->tags[$from] = array();
        }
        /** @var DOMDocument $dom */
        if (!in_array($dom->nodeName, $this->tags[$from]) && class_exists($dom->nodeName)) {
            $this->tags[$from][] = $dom->nodeName;
        }

        if ($dom->childNodes) {
            foreach ($dom->childNodes as $new_dom) {
                $this->getAllEntityName($from, $new_dom);
            }
        }
        return $this->tags[$from];
    }

    public function getDom($from)
    {
        return self::$ini[$from];
    }

    /**
     * @return array
     */
    public function getSavedObjects(): array
    {
        return $this->saved_objects;
    }
}




