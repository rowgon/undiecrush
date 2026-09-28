<?php

namespace Asylum\Utility\Facades;

/**
 * @method static mixed dd(mixed $data)
 * @method static mixed sendHeader(string $type)
 * @method static mixed die(mixed $data)
 * @method static mixed dump(...$data)
 * @method static mixed log(mixed $data)
 * @method static mixed dumpWhen($callback, ...$data)
 * @method static mixed ddWhen($callback, ...$data)
 * @method static mixed dumpWhenNot($callback, ...$data)
 * @method static mixed ddWhenNot($callback, ...$data)
 */
class Debug
{
    private static $instance = null;

    public static function __callstatic($method, $arguments)
    {
        if (self::$instance === null) {
            self::$instance = new \Asylum\Utility\Debug\Debug;
        }

        return call_user_func_array([self::$instance, $method], $arguments);
    }
}