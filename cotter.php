<?php
/**
 * CotterPHP default autoloader & constant defines
 * Copyright 2019, laurelclouds@163.com
 */

if( !defined('COTTER_PHP_VERSION') )
{
    define('COTTER_PHP_VERSION', "0.1.0.0");
    define('PHP_VERSION_REQUIRED', '5.4.0');

    /* ************************************************************************* */
    // 检测服务器PHP版本，确定满足最小运行条件
    /* ************************************************************************* */
    function php_version_id($version=PHP_VERSION)
    {
        $vers = explode('.', $version);
        while(count($vers)<3) $vers[] = 0;
        return ($vers[0] * 10000 + $vers[1] * 100 + $vers[2]);
    }

    define('COTTER_PHP_VERSION_ID', php_version_id(COTTER_PHP_VERSION));

    define('PHP_VERSION_ID_REQUIRED', php_version_id(PHP_VERSION_REQUIRED));

    if(!defined('PHP_VERSION_ID'))
    {
        define('PHP_VERSION_ID', php_version_id(PHP_VERSION));
    }

    if(PHP_VERSION_ID<PHP_VERSION_ID_REQUIRED) {
        exit('PHP '.PHP_VERSION_REQUIRED.' or greater required.');
    }

    /* ************************************************************************* */
    // cotter.php 相关目录设置
    /* ************************************************************************* */
    // cotter.php 本文件所在目录
    if(!defined('COTTER_PHP_PATH'))
    {
        define('COTTER_PHP_PATH', __DIR__);
    }

    // 默认的Autoloader, PSR-4
    spl_autoload_register(function($class) {
        if($class[0]=="\\") $class = substr($class, 1);
        $at = strrpos($class, "\\");

        if(DIRECTORY_SEPARATOR!=="\\") $class = str_replace("\\", DIRECTORY_SEPARATOR, $class);

        $class .= ".php";
        if($at!==false) {
            /**
             * 命名空间由驼峰式（CamelCase、camelCase）转换为短横线式（kebab-case）
             * 除非命名空间中目录的首字母（仅转换为小写字母）
             * 注意：最终的类名大小写不转换
             */
            $re = '/.[A-Z]/';
            $class = preg_replace_callback($re,
                function($matches) {
                    if($matches[0][0]==DIRECTORY_SEPARATOR) return DIRECTORY_SEPARATOR.strtolower($matches[0][1]);
                    return $matches[0][0].'-'.$matches[0][1];
                },
                substr($class, 0, $at)
            ).substr($class, $at);
        }

        @include(COTTER_PHP_PATH . DIRECTORY_SEPARATOR . $class);
    }, true, false);

    // 发现CotterPHP框架时，注册并使用框架的autoloader；单独使用时不会注册CotterPHP架构的autoloader
    if(is_file(COTTER_PHP_PATH . DIRECTORY_SEPARATOR . 'cotter' . DIRECTORY_SEPARATOR . 'CotterPHP.php'))
    {
        cotter\CotterPHP::register();
    }
}
?>