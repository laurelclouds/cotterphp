<?php
namespace cotter;

/**
 * CotterPHP class
 * 目的：注册定制的Autoloader、搭建CotterPHP框架运行环境、转交至恰当的应用程序入口
 */

class CotterPHP
{
    public static $loaderDefs = array();

    public static function import($class)
    {
        if($class[0]=="\\") $class = substr($class, 1);
        $at = strpos($class, "\\");
        if($at===false) return;     // 交给其他Autoloader处理

        $top = substr($class, 0, $at);
        $def = (self::$loaderDefs)[$top];
        if(empty($def)) {
            $ldf = __DIR__ . DIRECTORY_SEPARATOR . "loaders" . DIRECTORY_SEPARATOR . $top . ".loader.php";
            if(!is_file($ldf)) return;

            $def = @include $ldf;
            if(empty($def)) return;

            (self::$loaderDefs)[$top] = $def;
        }

        $got = '';
        if(is_array($def)) {
            $got = $def[$class];
        }
        else if(is_string($def)) {
            $got = $def;
        }
        else if(is_callable($def)) {
            $got = $def(substr($class, $at+1));
        }

        if(empty($got)) return;

        @include $got;
    }

    public static function register()
    {
        \spl_autoload_register('self::import', false, true);
    }

    public static function unregister()
    {
        \spl_autoload_unregister('self::import');
    }
}
