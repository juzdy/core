<?php
namespace Juzdy;

class Debug
{
    public static function d($var): void
    {
        echo '<pre>';
        print_r($var);
        echo '</pre>';
    }

    public static function dd($var): void
    {
        self::d($var);
        die();
    }
}