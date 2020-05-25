<?php

namespace PDF\Formatters;

class TimeFormatter extends Formatter
{

    public static function time($value)
    {
        $date = new \DateTime($value);
        return $date->format('H:i:s');
    }

    public static function timestamp($value)
    {
        return date('H:i:s', $value);
    }

}
