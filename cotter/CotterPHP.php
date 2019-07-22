<?php
namespace cotter;

/**
 * CotterPHP class
 * 目的：注册定制的Autoloader、搭建CotterPHP框架运行环境、转交至恰当的应用程序入口
 */

class CotterPHP
{
    private static $instance = null;            // CotterPHP实例，应存在且仅存在一个实例
    private static $mapStrategies = array();    // 映射策略的缓存数组
    private static $namespaceMaps = array();    // 命名空间映射表

    private static $timeStart = 0;     // CotterPHP框架开始初始化的时间

    /**
     * 驼峰式（camelCase, CamelCase）转短横线式（kebab-case）
     * @param string $path      -- 需要转换的路径
     * @return string
     */
    public static function camelToKebab($path)
    {
        if(empty($path)) return '';

        $path = \preg_replace_callback(
            '/.[A-Z]/',
            function($matches) {
                return $matches[0][0] . (in_array($matches[0][0], ['\\', '/']) ? '' : '-') .strtolower($matches[0][1]);
            },
            $path
        );

        $path = strtolower($path[0]) . substr($path, 1);
    }

    /**
     * 短横线式（kebab-case）转驼峰式（camelCase或CamelCase）
     * @param string $path          -- 需要转换的路径
     * @param bool $bigCamel        -- 是否转换为大驼峰式（CamelCase）；true，大驼峰式(CamelCase)；false，小驼峰式camelCase；默认为小驼峰式camelCase
     * @return $string
     */
    public static function kebabToCamel($path, $bigCamel=false)
    {
        if(empty($path)) return '';
        $path = \preg_replace_callback(
            '/(?:-[a-z])|(?:\\/[a-z])|(?:\\\\[a-z])/',
            function($matches) use ($bigCamel) {
                if($matches[0][0]=='-') {
                    return strtoupper($matches[0][1]);
                }

                return $bigCamel ? \strtoupper($matches[0]) : $matches[0];
            },
            $path
        );
    }

    /**
     * include语句的函数写法
     * @param string $file      -- 加载的文件名
     * @return mixed            -- include语句的返回值
     */
    public static function import($file)
    {
        return @include $file;
    }

    /**
     * 执行映射策略，获取映射结果
     * @param string|array|function $strategy   -- 加载策略
     * @param string $ns                        -- 查询的命名空间
     * @return array                            -- 返回待搜索的路径的数组
     */
    private static function execStrategy(&$strategy, $ns='')
    {
        if(empty($strategy)) return array();

        if(\is_array($strategy)) {
            if(!isset($strategy[$ns])) return array();

            $strategy = $strategy[$ns];
        }

        if(\is_callable($strategy)) $strategy = $strategy($ns);
        if(\is_array($strategy)) return $strategy;

        if(empty($strategy)) return array(COTTER_PHP_PATH);
        return array($strategy);
    }

    /**
     * 自动加载类名（traits等）对应文件
     * @param string $class         -- 自动加载的类名（traits等）
     * @return void
     */
    public static function autoload($class)
    {
        if($class[0]=="\\") $class = substr($class, 1);
        $spaces = explode("\\", $class);
        $n = count($spaces);

        if($n<2) return;                            // 交给其他Autoloader处理
        
        $top = $spaces[0];
        $ns = implode(DIRECTORY_SEPARATOR, array_slice($spaces, 0, $n-1));
        $cls = $spaces[$n-1];

        if(!isset(self::$namespaceMaps[$ns])) {
            if(!isset(self::$mapStrategies[$top])) {
                $strategyFile = __DIR__ . DIRECTORY_SEPARATOR . 'loaders' . DIRECTORY_SEPARATOR . $spaces[0] . '.loader.php';
                if(!is_file($strategyFile)) return;     //加载策略不存在
                self::$mapStrategies[$top] = @include $strategyFile;
            }

            self::$namespaceMaps[$ns] = self::execStrategy(self::$mapStrategies[$top], $ns);
        }

        if(!\is_array(self::$namespaceMaps[$ns])) return;

        foreach(self::$namespaceMaps[$ns] as $dir) {
            $fn = $dir . DIRECTORY_SEPARATOR . $cls . '.php';
            if(is_file($fn)) {
                return @include $fn;
                break;
            }
        }
    }

    /**
     * 注册自动加载
     */
    public static function register()
    {
        \spl_autoload_register(array('self', 'autoload'), false, true);
    }

    /**
     * 解除自动加载
     */
    public static function unregister()
    {
        \spl_autoload_unregister(array('self', 'autoload'));
    }

    /**
     * CotterPHP框架初始化
     */
    public static function initialize()
    {
        if(!is_null(self::$instance)) return;

        self::$timeStart = time();
        self::register();

        self::$instance = new CotterPHP();
    }

    public function __construct()
    {
        
    }

    public function __destruct()
    {
        
    }

}
