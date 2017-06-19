<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/9
 * Time: 13:05
 */
class FileLoad
{
    public static $files_array = array();
}

function requireClass($name)
{
    $name=str_replace('\\',DIRECTORY_SEPARATOR,$name);
    foreach (FileLoad::$files_array as $file) {
        $name_file = substr($file, -(5 + strlen($name)));
        if ($name_file == (DIRECTORY_SEPARATOR.$name.".php") ) {
            require_once $file;
            return;
        }
    }
}

function findFile($dir)
{
    static $tmp = 0;
    static $floor = 0;
    static $dir_name_added = array(__DIR__);
    if ($tmp==0){
        $dir_name_added=array($dir);
        $tmp++;
    }
    $file_suitable = "";
    $dir_fo = opendir($dir);
    while ($file = readdir($dir_fo)) {
        if ($file != '.' and $file != '..') {
            $file_tmp = "";
            for ($i = 0; $i <= $floor; $i++) {
                $file_tmp = $file_tmp . $dir_name_added[$i];
            }
            $file_tmp = $file_tmp == "" ? $file : $file_tmp . DIRECTORY_SEPARATOR . $file;
            if (is_dir($file_tmp)) {
                $floor++;
                $dir_name_added[$floor] = DIRECTORY_SEPARATOR . $file;
                findFile($file_tmp);
            } else {
                if (extend_file($file) == "php") {
                    for ($i = 0; $i <= $floor; $i++) {
                        $file_suitable = $file_suitable . $dir_name_added[$i];
                    }
                    FileLoad::$files_array[] = $file_suitable . DIRECTORY_SEPARATOR . $file;
                    $file_suitable = "";
                }
            }
        }
    }
    $floor--;
    closedir($dir_fo);
}

function extend_file($file_name)
{
    $extend = explode(".", $file_name);
    $va = count($extend) - 1;
    return $extend[$va];
}

spl_autoload_register("requireClass");