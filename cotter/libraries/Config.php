<?php
namespace cotter;

class Config
{
    /**
     * 配置缓存数组
     * @var array $options
     */
    public static $options = array();

    public static function __callStatic($name, $arguments)
    {
        if(!array_key_exists($name, self::$options)) {
            self::load($name);
        }

        $argc = count($arguments);
        if($argc==0) {
            return self::$options[$name];
        }

        // 如果是获取某配置
        $k = $arguments[0];
        if($argc==1) {
            if(is_array($k)) {
                self::$options[$name] = array_merge(self::$options[$name], $k);
                return self::$options;
            }

            return self::$options[$name][$k];
        }

        if(is_null($arguments[1])) {
            unset(self::$options[$name][$k]);
        }
        else {
            self::$options[$name][$k] = $arguments[1];
        }

        return $arguments[1];
    }

    /**
     * 载入配置
     * @param string $name      -- 载入何种配置
     * @return array            -- 返回配置内容数组
     */
    public static function load($name)
    {
        $filename = COTTER_PHP_PATH . DIRECTORY_SEPARATOR .'config'. DIRECTORY_SEPARATOR . $name . '.php';
        if(!is_file($filename)) {
            return array();
        }

        $cfg = @include $filename;

        if(empty($cfg)) $cfg = array();

        self::$options[$name] = $cfg;
        return $cfg;
    }

    /**
     * 保存配置
     * @param string $name      -- 保存何种配置，空表示保存全部
     * @return mixed            -- false, 保存失败；正整数n表示成功保存几个配置
     */
    public static function save($name='')
    {
        if(!empty($name)) {
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
                    @mkdir($base, 0777, true);       // 如有需要，权限可更改为0700
                    @chmod($base, 0777);
                }

                return @file_put_contents($filename, implode("\n", $lines), LOCK_EX)!==false ? 1 : false;
            }

            return false;
        }

        $success = 0;
        foreach(self::$options as $k => $v) {
            if(self::save($k)) $success++;
        }

        return $success!==0 ? $success : false;
    }
}
?>