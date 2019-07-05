<?php
namespace cotter;

/**
 * Config类，实现了ArrayAccess访问接口
 */
class Config implements \ArrayAccess
{
    /**
     * 配置缓存数组
     * @var array $options
     */
    public static $options = array();

    /**
     * 当前实例的类型
     * @var string $type
     */
    public $type = '';

    public function __construct($type='')
    {
        $this->type = $type;
    }

    /**
     * 获取单项配置
     * @param string $type      -- 配置类型
     * @param string|int $key   -- 配置项
     * @param mixed $defaultValue   -- 配置不存在时的默认值；默认为null
     * @return mixed
     */
    private static function _so_get($type, $key, $defaultValue=null)
    {
        if(is_object($type)) $type = $type->type;
        if(!isset(self::$options[$type])) {
            self::$options[$type] = self::_so_load($type) ?: array();
        }
        
        if(!isset(self::$options[$type][$key])) return $defaultValue;

        return self::$options[$type][$key];
    }

    /**
     * 设置配置项的值
     * @param string $type      -- 配置类型
     * @param string|int $key   -- 配置项
     * @param mixed $value      -- 配置值
     * @return void
     */
    private static function _so_set($type, $key, $value)
    {
        if(!isset(self::$options[$type])) self::$options[$type] = array();
        self::$options[$type][$key] = $value;
    }

    private static function _so_isset($type, $key)
    {
        return isset(self::$options[$type]) && isset(self::$options[$type][$key]);
    }

    /**
     * 删除配置项
     * @param string $type      -- 配置类型
     * @param string|int $key   -- 配置项
     * @return void
     */
    private static function _so_unset($type, $key)
    {
        if(!isset(self::$options[$type])) return;
        unset(self::$options[$type][$key]);
    }

    /**
     * 载入配置
     * @param string $type
     * @return array|bool
     */
    private static function _so_load($type)
    {
        $fn = COTTER_PHP_PATH . DIRECTORY_SEPARATOR .'config'. DIRECTORY_SEPARATOR . $type . '.php';
        if(!is_file($fn)) return false;
        return @include $fn;
    }

    /**
     * 保存配置
     * @param string $name      -- 保存何种配置
     * @return bool             -- 是否保存成功
     */
    private static function _so_save($name)
    {
        if(isset(self::$options[$name]) && is_array(self::$options[$name])) {
            $base = COTTER_PHP_PATH . DIRECTORY_SEPARATOR .'config';
            $filename = $base. DIRECTORY_SEPARATOR . $name . '.php';
            $lines = array(
                "<?php",
                "return array("
            );

            $rows = array();
            foreach(self::$options[$name] as $k => $v) {
                if(is_null($v)) continue;

                $k = str_replace(["\\", "\"", "\$"], ["\\\\", "\\\"", "\\\$"], $k);

                if(is_bool($v)) {
                    $v = $v ? 'true' : 'false';
                }
                elseif(is_numeric($v)) {
                    $v = strval($v);
                }
                elseif(is_string($v)) {
                    $v = "\"" . str_replace(["\\", "\"", "\$"], ["\\\\", "\\\"", "\\\$"], $v) . "\"";
                }
                else {
                    $v = "json_decode(\"" . str_replace(["\\", "\"", "\$"], ["\\\\", "\\\"", "\\\$"], json_encode($v)) . "\")";
                }
                $rows[] = "\"$k\" => $v";
            }

            $lines[] = implode(",\n", $rows);
            $lines[] = ");";
            $lines[] = "?>";
            
            if(!is_dir($base)) {
                @mkdir($base, 0777, true);
                @chmod($base, 0777);
            }

            return @file_put_contents($filename, implode("\n", $lines), LOCK_EX)!==false;
        }

        return false;
    }

    public static function __callStatic($name, $arguments)
    {
        if(!isset(self::$options[$name])) self::_so_load($name);
        $argc = count($arguments);

        // 不带参数调用时，返回Config对象
        if($argc==0) {
            return new Config($name);
        }

        // 带参数时，返回指定关键字的值
        if($argc==1) {
            return self::_so_get($name, $arguments[0], null);
        }

        // 设定默认值后，如果指定关键字的值不存在，将返回为默认值
        return self::_so_get($name, $arguments[0], $arguments[1]);
    }

    public function __call($name, $arguments)
    {
        $func = "_so_$name";
        if(!method_exists(self::class, $func)) throw new \BadMethodCallException(__CLASS__."::$name method NOT found.");
        array_unshift($arguments, $this->type);
        return call_user_func_array(array("self", $func), $arguments);
    }

    public function __invoke($type, $key=null, $defaultValue=null)
    {
        if(\is_null($key)) return new Config($type);
        return self::_so_get($type, $key, $defaultValue);
    }

    public function __get($name)
    {
        return self::_so_get($this->type, $name, null);
    }

    public function __set($name, $value)
    {
        self::_so_set($this->type, $name, $value);
    }

    public function __isset($name)
    {
        return self::_so_isset($this->type, $name);
    }

    public function __unset($name)
    {
        self::_so_unset($this->type, $name);
    }

    /**
     * ArrayAccess::offsetExists接口实现
     * @param string|int $offset
     * @return bool
     */
     public function offsetExists($offset)
     {
         return self::_so_isset($this->type, $offset);
     }

     /**
      * ArrayAccess::offsetGet接口实现
      * @param string|int $offset
      * @return mixed
      */
     public function offsetGet($offset)
     {
         return self::_so_get($this->type, $offset, null);
     }

     /**
      * ArrayAccess::offsetSet接口实现
      * @param string|int $offset
      * @param mixed $value
      * @return void
      */
     public function offsetSet($offset, $value)
     {
         self::_so_set($this->type, $offset, $value);
     }

     /**
      * ArrayAccess::offsetUnset接口实现
      * @param string|int $offset
      * @return void
      */
     public function offsetUnset($offset)
     {
         self::_so_unset($this->type, $offset);
     }
}
?>