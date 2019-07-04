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
    public static function get($type, $key, $defaultValue=null)
    {
        if(!isset(self::$options[$type])) {
            self::$options[$type] = self::load($type) ?: array();
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
    public static function set($type, $key, $value)
    {
        if(!isset(self::$options[$type])) self::$options[$type] = array();
        self::$options[$type][$key] = $value;
    }

    /**
     * 删除配置项
     * @param string $type      -- 配置类型
     * @param string|int $key   -- 配置项
     * @return void
     */
    public static function unset($type, $key)
    {
        if(!isset(self::$options[$type])) return;
        unset(self::$options[$type][$key]);
    }

    /**
     * 载入配置
     * @param string $type
     * @return array|bool
     */
    public static function load($type)
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
    public static function save($name)
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
        if(!isset(self::$options[$name])) self::load($name);
        $argc = count($arguments);

        if($argc==0) {
            return self::$options[$name];
        }

        // 如果是获取某配置
        $k = $arguments[0];
        if($argc==1) {
            return self::get($name, $k, null);
        }

        // 否则为设置某配置
        self::set($name, $k, $arguments[1]);
        return $arguments[1];
    }

    public function __call($name, $arguments)
    {
        if(!function_exists(self::$name)) throw new \BadMethodCallException(__CLASS__."::$name method NOT found.");

        array_unshift($arguments, $this->type);
        return call_user_func_array(array("self",$name), $arguments);
    }

    public function __invoke($type, $key=null, $defaultValue=null)
    {
        if(\is_null($key)) return new Config($type);
        return self::get($type, $key, $defaultValue);
    }

    /**
     * ArrayAccess::offsetExists接口实现
     * @param string|int $offset
     * @return bool
     */
     public function offsetExists($offset)
     {
         return isset(self::$options[$this->type]) && isset(self::$options[$this->type][$offset]);
     }

     /**
      * ArrayAccess::offsetGet接口实现
      * @param string|int $offset
      * @return mixed
      */
     public function offsetGet($offset)
     {
         return self::get($this->type, $offset, null);
     }

     /**
      * ArrayAccess::offsetSet接口实现
      * @param string|int $offset
      * @param mixed $value
      * @return void
      */
     public function offsetSet($offset, $value)
     {
         self::set($this->type, $offset, $value);
     }

     /**
      * ArrayAccess::offsetUnset接口实现
      * @param string|int $offset
      * @return void
      */
     public function offsetUnset($offset)
     {
         self::unset($this->type, $offset);
     }
}
?>