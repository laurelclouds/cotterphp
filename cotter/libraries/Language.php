<?php
namespace cotter;

use cotter\Config;

class Language
{
    /**
     * 共享的默认Language对象实例
     */
    protected static $instance = null;

    private $id = 'zh-hans';
    private $dict = null;

    public function get($key/* , ...$args */)
    {
        $result = $this->dict[$key];
        if (empty($result) && $this->id!==Config::get('app.language', 'zh-hans')) {
            $result = (self::$instance)->get($key);
        }

        if (func_num_args()>1) {
            $args = func_get_args();
            array_shift($args);

            $result = call_user_func_array("sprintf", $result, $args);
        }

        return $result;
    }

    public function __construct($lang='zh-hans')
    {
        $this->id = $lang;
        $fn = Path::langs("$lang.php");
        if (is_file($fn)) {
            $this->dict = new Dictionary(@include $fn);
        }
        else {
            $this->dict = new Dictionary();
        }

        if (is_null(self::$instance)) {
            if ($lang==Config::get('app.language', 'zh-hans')) {
                self::$instance = $this;
            }
            else {
                self::$instance = new Language(Config::get('app.language', 'zh-hans'));
            }
        }
    }

    public function __invoke($key)
    {
        if (is_null(self::$instance)) {
            self::$instance = new Language(Config::get('app.language', 'zh-hans'));
        }

        return (self::$instance)->get($key);
    }
}
