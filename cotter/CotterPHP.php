<?php
namespace cotter;

/**
 * CotterPHP class
 */

class CotterPHP
{
    public static function import($class)
    {
        if($class[0]=="\\") $class = substr($class, 1);
        $at = strpos($class, "\\");
        if($at===false) return;     // 交给其他Autoloader处理

        $loaderDef = __DIR__ . DIRECTORY_SEPARATOR . "loaders" . DIRECTORY_SEPARATOR . substr($class, 0, $at) . ".loader.php";
        if(is_file($loaderDef)) {
            $loader = @include $loaderDef;
            if(empty($loader) || !is_callable($loader)) return;

            $class = substr($class, $at + 1);
            @include $loader($class);
        }
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
?>