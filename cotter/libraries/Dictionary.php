<?php
namespace cotter;

use ArrayAccess;
use JsonSerializable;
use TypeError;
use BadMethodCallException;
use cotter\Language;

class Dictionary implements ArrayAccess, JsonSerializable
{
    public $items = array();

    public function offsetExists($offset)
    {
        if(!is_string($offset)) {
            trigger_error('Dictonary key should be a string value.', E_USER_WARNING);
        }

        return isset($this->items[@strval($offset)]);
    }

    public function offsetGet($offset)
    {
        if(!is_string($offset)) {
            trigger_error('Dictonary key should be a string value.', E_USER_WARNING);
        }

        return $this->items[@strval($offset)];
    }

    public function offsetSet($offset, $value)
    {
        if(!is_string($offset)) {
            trigger_error('Dictonary key should be a string value.', E_USER_WARNING);
        }

        $this->items[@strval($offset)] = $value;
    }

    public function offsetUnset($offset)
    {
        if(!is_string($offset)) {
            trigger_error('Dictonary key should be a string value.', E_USER_WARNING);
        }

        unset($this->items[@strval($offset)]);
    }

    public function jsonSerialize()
    {
        return $this->items;
    }

    public function __call($name, $arguments)
    {
        $re = "/[A-Z]/";
        $func = \preg_replace_callback(
            $re,
            function($matches) {
                return "_".\strtolower($matches[0]);
            },
            $name
        );
        $func = "array_$func";
        if(function_exists($func)) {
            array_unshift($arguments, $this->items);
            return \call_user_func_array($func, $arguments);
        }

        throw new BadMethodCallException(Language::get('BadMethodCall', __CLASS__, $name));
    }

    public function __get($name)
    {
        if(property_exists($this->items, $name)) {
            return $this->items->$name;
        }

        return $this->items[$name];
    }

    public function __set($name, $value)
    {
        if(property_exists($this->items, $name)) {
            $this->items->$name = $value;
            return;
        }

        $this->items[$name] = $value;
    }

    public function __isset($name)
    {
        if(property_exists($this->items, $name)) return true;
        return isset($this->items[$name]);
    }

    public function __unset($name)
    {
        if(!property_exists($this->items, $name)) {
            unset($this->items->$name);
            return;
        }

        unset($this->items[$name]);
    }

    public function __construct($arrayLike=null)
    {
        if(is_null($arrayLike)) return;
        if(is_object($arrayLike)) $arrayLike = get_object_vars($arrayLike);
        if(!is_array($arrayLike)) return;

        $this->items = array_merge($this->items, $arrayLike);
    }

    public function __invoke($arrayLike=null)
    {
        return new Dictionary($arrayLike);
    }
}
