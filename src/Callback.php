<?php

namespace App;

class Callback
{
    /**
     * Converts argument to a callable.
     * @param mixed $cb Callback
     */
    final public static function make($cb): callable
    {
        if (is_callable($cb)) {
            return $cb;
        }

        if (is_string($cb)) {
            $rgx = '/^([a-zA-Z0-9_\\\]+)(::|@)([a-zA-Z0-9_]+)$/';
            if (preg_match($rgx, $cb, $matches)) {
                $classname = $matches[1];
                $method = $matches[3];
                if (class_exists($classname)) {
                    $obj = new $classname();
                    if (method_exists($obj, $method)) {
                        return [$obj, $method];
                    }
                }
            }
        }

        if (is_array($cb) && 2 == count($cb)) {
            if (is_object($cb[0]) && is_string($cb[1])) {
                return $cb;
            }
            if (is_string($cb[0]) && is_string($cb[1])) {
                $classname = $cb[0];
                $method = $cb[1];
                if (class_exists($classname)) {
                    $obj = new $classname();
                    if (method_exists($obj, $method)) {
                        return [$obj, $method];
                    }
                    if (method_exists($classname, $method)) {
                        return $cb;
                    }
                }
            }
        }

        throw new \Exception('Invalid callback');
    }

    /**
     * Calls a callable with arguments.
     *
     * @param mixed $cb Callback
     * @return mixed
     */
    final public static function call($cb, array $args = [])
    {
        return call_user_func_array(static::make($cb), $args);
    }
}
