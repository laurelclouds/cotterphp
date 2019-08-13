<?php
namespace cotter;

class Path
{
    /**
     * 路径规范化，分隔符采用当前系统的分隔符，并去除.和..等
     * @param string $path      -- 待规范的路径
     * @return string
     */
    public static function normalize($path)
    {
        if (empty($path)) return '';
        $dirs = explode(DIRECTORY_SEPARATOR, str_replace(["\\", "/"], DIRECTORY_SEPARATOR, $path));

        $results = [ $dirs[0] ];

        $n = count($dirs);
        for ($i=1; $i<$n; $i++) {
            $s = $dirs[$i];
            if (empty($s) || $s=='.') continue;
            if ($s=='..') {
                $l = count($results);
                if ($l==1) {
                    if ($results[0]=='') continue;
                    if ($results[0]=='.') {
                        $results[0] = '..';
                        continue;
                    }
                    if ($results[0][strlen($results[0])-1]==':') continue;  // Win根目录
                }
                if ($l>0 && $results[$l-1]!='..') {
                    array_pop($results);
                    continue;
                }
            }
            $results[] = $s;    
        }

        return implode(DIRECTORY_SEPARATOR, $results);
    }

    /**
     * 连接若干路径，生成规范路径表示的结果
     * @param mixed $args       -- 不定参数
     * @return string
     */
    public static function join(/*...$args */)
    {
        $n = func_num_args();
        if ($n==0) return '';
    
        $args = func_get_args();
        $results = [];
        foreach ($args as $arg) {
            $s = '';
            if (is_array($arg)) {
                $s = \call_user_func_array(static::join, $arg);
            }
            else {
                $s = $arg;
            }
    
            if (!empty($s)) $results[] = $s;
        }
    
        return static::normalize(\implode(DIRECTORY_SEPARATOR, $results));
    }

    /**
     * 递归创建目录
     * @param string $path  -- 待创建的目录
     * @param int $mode     -- 目录模式
     * @return bool         -- 是否创建成功
     */
    public static function mkdir($path, $mode=0777)
    {
        if (!@\mkdir($path, $mode, true)) return false;
        @\chmod($path, $mode);
        return true;
    }

    public static function clear($path)
    {
        if (!is_dir($path)) {
            return false;
        }

        $dirs = \scandir($path);
        foreach ($dirs as $dir) {
            if ($dir=='.' || $dir=='..') continue;
            $t = $path.'/'.$dir;
            if (is_dir($t)) {
                if (false===static::rmdir($t, true)) {
                    return false;
                }
            }
            else {
                if (false===@\unlink($t)) {
                    return false;
                }
            }
        }
        return true;
    }

    public static function rmdir($path, $force)
    {
        if (!$force) return @\rmdir($path);
        
        $removed = static::clear($path);
        if ($removed===false) return false;
        return @\rmdir($path);
    }

    public static function get($name)
    {
        $name = str_replace(["\\", "/"], DIRECTORY_SEPARATOR, $name);
        if (strncmp($name, COTTER_PHP_PATH, strlen(COTTER_PHP_PATH))!==0) {
            return COTTER_PHP_PATH . $name;
        }

        return $name;
    }
}
