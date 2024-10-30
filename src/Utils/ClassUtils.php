<?php

namespace Aqqo\OData\Utils;

class ClassUtils
{
    /**
     * @var array<string, string>
     */
    private static array $classes = [];

    /**
     * @param object $class
     * @return string
     */
    public static function getShortName(object $class): string
    {
        if (!isset(self::$classes[get_class($class)])) {
            self::$classes[get_class($class)] = strtolower((new \ReflectionClass($class))->getShortName());
        }
        return self::$classes[get_class($class)];
    }
}