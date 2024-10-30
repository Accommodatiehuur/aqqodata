<?php

namespace Aqqo\OData\Utils;

class ClassUtils
{
    private static $classes = [];
    public static function getShortName(object $class)
    {
        if (!isset(self::$classes[get_class($class)])) {
            self::$classes[get_class($class)] = strtolower((new \ReflectionClass($class))->getShortName());
        }
        return self::$classes[get_class($class)];
    }
}