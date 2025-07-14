<?php

namespace Imarc\Millyard\Support;

class Str
{
    public static function slug(string $string): string
    {
        return strtolower(str_replace(' ', '-', $string));
    }

    public static function title(string $string): string
    {
        return ucwords(str_replace('-', ' ', $string));
    }

    public static function kebab(string $string): string
    {
        return strtolower(str_replace(' ', '-', $string));
    }

    public static function pascal(string $string): string
    {
        return str_replace(' ', '', static::title($string));
    }

    public static function camel(string $string): string
    {
        return lcfirst(static::pascal($string));
    }
}
