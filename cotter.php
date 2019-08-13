<?php
/**
 * CotterPHP default autoloader & constant defines
 * Copyright 2019, laurelclouds@163.com
 */
namespace cotter;

if (!defined('COTTERPHP_FRAMEWORK'))
{
    define('COTTERPHP_FRAMEWORK', __DIR__ . DIRECTORY_SEPARATOR . 'cotter' . DIRECTORY_SEPARATOR . 'CotterPHP.php');
    define('COTTER_PHP_PATH', __DIR__ . DIRECTORY_SEPARATOR);         // cotter.php 本文件所在目录

    define('PHP_VERSION_REQUIRED', '5.4.0');    // 所需PHP最低版本号


    /* ************************************************************************* */
    // 检测服务器PHP版本，确定满足最小运行条件
    /* ************************************************************************* */
    function php_version_id($version=PHP_VERSION)
    {
        $vers = explode('.', $version);
        while (count($vers)<3) $vers[] = 0;
        return (intval($vers[0]) * 10000 + intval($vers[1]) * 100 + intval($vers[2]));
    }

    define('PHP_VERSION_ID_REQUIRED', php_version_id(PHP_VERSION_REQUIRED));

    if (!defined('PHP_VERSION_ID'))
    {
        define('PHP_VERSION_ID', php_version_id(PHP_VERSION));
    }

    if (PHP_VERSION_ID<PHP_VERSION_ID_REQUIRED) {
        exit('PHP '.PHP_VERSION_REQUIRED.' or greater required.');
    }

    // 发现CotterPHP框架时，初始化CotterPHP框架
    if (is_file(COTTERPHP_FRAMEWORK))
    {
        require(COTTERPHP_FRAMEWORK);
        \cotter\CotterPHP::initialize();
    }
    else {
        // 默认的Autoloader, 置于所有其他的Autoloader（如果有的话）的最后
        spl_autoload_register(
            function ($class) {
                if ($class[0]=="\\") $class = substr($class, 1);
                if (DIRECTORY_SEPARATOR!=="\\") $class = str_replace("\\", DIRECTORY_SEPARATOR, $class);
                @include COTTER_PHP_PATH . $class . '.php';
            },
            true,
            false
        );
    }
}
