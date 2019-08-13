<?php
namespace cotter;

/**
 * CotterPHP class - PSR-4
 * 目的：注册定制的Autoloader、搭建CotterPHP框架运行环境、转交至恰当的应用程序入口
 */

class CotterPHP
{
    private static $instance = null;            // CotterPHP实例，应存在且仅存在一个实例
    private static $vendors = array();          // 供应商映射策略
    private static $namespaces = array();       // 命名空间映射表
    private static $aliases = array();          // 类别名
    private static $declarations = array();     // 特别声明，类名 => 对应文件；不使用通用规则的自动加载

    protected static $timeStart = 0;            // CotterPHP框架开始初始化的时间

    private $id = "";                           // 当前实例id（即供应商id）
    private $strategy = false;                  // 当前实例的命名空间存放策略

    /**
     * 自动加载类名等对应的文件
     * @param string $class         -- 自动加载的类名等
     * @return void
     */
    public static function autoload($class)
    {
        // 如果$class为别名，登记
        if (isset(self::$aliases[$class])) {
            return class_alias(self::$aliases[$class], $class, true);
        }
        
        $f = self::findClass($class);
        if ($f!==false) {
            @include $f;
            return class_exists($class, false);
        }

        return false;
    }

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = self::getVendor();
        }
        return self::$instance;
    }

    public static function getVendors()
    {
        return self::$vendors;
    }

    public static function getNamespaces()
    {
        return self::$namespaces;
    }

    public static function getAliases()
    {
        return self::$aliases;
    }

    public static function getDeclarations()
    {
        return self::$declarations;
    }

    /**
     * CotterPHP框架初始化
     * @return void
     */
    public static function initialize()
    {
        if (isset(self::$instance)) return;
        self::$instance = self::getVendor("");

        \spl_autoload_register(array('self', 'autoload'), true, true);
    }

    /**
     * 为指定命名空间添加查找目录
     * @param string|array $namespace
     * @param array|string $dirs
     * @return void
     */
    public static function addNamespace($namespace, $dirs=null)
    {
        if (is_array($namespace)) {
            self::$namespaces = array_merge_recursive(self::$namespaces, $namespace);
            return;
        }

        if (substr($namespace, -1)!=="\\") $namespace .= "\\";

        if (empty($dirs)) {
            $dirs = [
                \dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace("\\", DIRECTORY_SEPARATOR, $namespace)
            ];
        }

        if (!isset(self::$namespaces[$namespace]) || !is_array(self::$namespaces[$namespace])) {
            self::$namespaces[$namespace] = array();
        }

        if (!is_array($dirs)) {
            $dirs = [$dirs];
        }
        self::$namespaces[$namespace] = array_merge(self::$namespaces[$namespace], $dirs);
    }

    /**
     * 为类添加别名
     * @param string|array $alias
     * @param string $class
     * @return void
     */
    public static function addAlias($alias, $class=null)
    {
        if (is_array($alias)) {
            self::$aliases = array_merge(self::$aliases, $alias);
            return;
        }

        if (empty($class)) {
            $class = "cotter\\$alias";
        }

        self::$aliases[$alias] = $class;
    }

    /**
     * 指定类声明所在的具体PHP文件
     * @param string|array $class
     * @param string $filename
     * @return void
     */
    public static function addDeclaration($class, $filename=null)
    {
        if (is_array($class)) {
            self::$declarations = array_merge(self::$declarations, $class);
            return;
        }

        if (empty($filename)) {
            $filename = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace("\\", DIRECTORY_SEPARATOR, $class) . ".php";
        }

        self::$declarations[$class] = $filename;
    }

    /**
     * 获取指定供应商
     * @param string $vendorId      -- 供应商id
     * @return CotterPHP
     */
    protected static function getVendor($vendorId = "")
    {
        if (!isset(self::$vendors[$vendorId])) {
            self::$vendors[$vendorId] = new static($vendorId);
        }

        return self::$vendors[$vendorId];
    }

    /**
     * 查找类
     * @param string $class
     * @return false|string
     */
    protected static function findClass($class)
    {
        $at = strpos($class, "\\");
        $vendorId = "";
        if ($at!==false) {
            $vendorId = substr($class, 0, $at);
        }

        $vendor = self::getVendor($vendorId);
        $files = $vendor->lookup($class);
        foreach ($files as $file) {
            if (is_file($file)) {
                return $file;
            }
        }

        return false;
    }

    /**
     * CotterPHP 构造函数
     * @access protected
     * @param string $namespace
     * @return void
     */
    protected function __construct($vendorId = "")
    {
        if (!self::$timeStart) self::$timeStart = time();

        $strategy = ["\\"   => \dirname(__DIR__) . DIRECTORY_SEPARATOR];
        if (!empty($vendorId)) {
            $strategyFile = __DIR__ . DIRECTORY_SEPARATOR .
                "loaders" . DIRECTORY_SEPARATOR .
                $vendorId . ".loader.php";
            if (is_file($strategyFile)) {
                $strategy = @include $strategyFile;
            }
            else {
                // 交给默认策略进行处理
                $strategy = false;
            }
        }

        $this->id = $vendorId;
        $this->strategy = $strategy;
    }

    /**
     * 执行策略
     * @param string $namespace
     * @return array
     */
    protected function execStrategy($namespace)
    {
        if (isset(self::$namespaces[$namespace])) {
            return self::$namespaces[$namespace];
        }

        $strategy = $this->strategy;
        $empty = empty($strategy);
        $result = array();
        if (is_callable($strategy)) {
            // 策略为函数求值，求值结果：false|array|string
            $t = $strategy($namespace);
            if (is_string($t)) {
                $result[] = $t;
            }
            elseif (is_array($t)) {
                $result = array_merge($result, $t);
            }
        }
        elseif (is_array($strategy) && !$empty) {
            // 策略为数组查询，查询结果：array|string
            $l = strlen($namespace);
            $offset = -1;
            while (false!==($at=strrpos($namespace, "\\", $offset))) {
                $ns = substr($namespace, 0, $at+1);
                if (isset($strategy[$ns])) {
                    $t = $strategy[$ns];
                    if (!\is_array($t)) {
                        $t = [ $t ];
                    }
                    if ($ns!==$namespace) {
                        $suffix = substr($namespace, strlen($ns));
                        $n = count($t);
                        for ($i=0; $i<$n; $i++) {
                            $t[$i] .= str_replace("\\", DIRECTORY_SEPARATOR, $suffix);
                        }
                    }

                    $result = array_merge($result, $t);
                }

                $offset = $at - $l - 1;
            }
        }
        elseif (!$empty) {
            // 策略为根据指定供应商目录求值，求值结果：string
            $result[] = $strategy . str_replace(
                "\\",
                DIRECTORY_SEPARATOR,
                substr($namespace, strlen($this->id)+1)?:""
            );
        }
        
        $defaultDir = dirname(__DIR__) . DIRECTORY_SEPARATOR;

        $result = array_merge(
            $result,
            array($defaultDir . str_replace("\\", DIRECTORY_SEPARATOR, $namespace))
        );

        self::$namespaces[$namespace] = $result;
        return $result;
    }

    /**
     * 查找命名空间或类对应的目录或文件
     * @param string $noc       -- Namespace Or Class(noc)
     * @return array
     */
    public function lookup($noc = "")
    {
        if (empty($noc)) {
            $noc = $this->id . "\\";
        }
        $at = strrpos($noc, "\\");
        $ns = "\\";
        $class = $noc;

        if ($at!==false) {
            $at ++;
            $ns = substr($noc, 0, $at);
            $class = substr($noc, $at);
        }

        if (!empty($class)) {
            // 如果某类已明确声明使用特定文件，则返回指定文件构成的数组
            if (isset(self::$declarations[$noc])) {
                return [self::$declarations[$noc]];
            }
        }

        // 查找指定命名空间对应目录列表
        $result = $this->execStrategy($ns);
        if (!empty($class)) {
            // 按通用规则查找指定类的可能文件列表
            $result = array_map(
                function ($v) use ($class) {
                    return $v . $class . ".php";
                },
                $result
            );
        }

        return $result;
    }
}
