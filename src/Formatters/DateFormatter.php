<?php

namespace PDF\Formatters;

class DateFormatter extends Formatter
{

    public static function date($value)
    {
        if (strlen($value) >= 8)
            return (new \DateTime($value))->format('d/m/Y');
        return $value;
    }

    public static function datetime($value)
    {
        if (strlen($value) >= 8)
            return (new \DateTime($value))->format('d/m/Y H:i:s');
        return $value;
    }

    public static function timestamp($value)
    {
        if (strlen($value) >= 8)
            return date('d/m/Y', $value);
        return $value;
    }

    public static function time($value)
    {
        if (strlen($value) >= 8)
            return (new \DateTime($value))->format('H:i:s');
        return $value;
    }

}
