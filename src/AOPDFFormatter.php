<?php

namespace AOPDF;

use Illuminate\Support\Str;

class AOPDFFormatter
{

//    'select' => [], // campos que serao marcados como selecionados
//    'date' => [], // campos que serao formatados como data
//    'date.time' => [], // campos que serao formatados como data, hora, minuto e segundo
//    'date.timestamp' => [], // campos que serao formatados como data a partir de um timestamp
//    'time' => [], // campos que serao formatados como hora, minuto e segundo
//    'time.timestamp' => [], //  campos que serao formatados como hora, minuto e segundo a partir de um timestamp

    public static function uuid($value)
    {
        return Str::uuid();
    }

    //
    // TEXT
    //

    public static function upper($value)
    {
        return mb_strtoupper($value);
    }

    public static function lower($value)
    {
        return mb_strtolower($value);
    }

    public static function trim($value)
    {
        return trim($value);
    }

    public static function unaccented($string)
    {
        return preg_replace([
            "/(á|à|ã|â|ä)/",
            "/(Á|À|Ã|Â|Ä)/",
            "/(é|è|ê|ë)/",
            "/(É|È|Ê|Ë)/",
            "/(í|ì|î|ï)/",
            "/(Í|Ì|Î|Ï)/",
            "/(ó|ò|õ|ô|ö)/",
            "/(Ó|Ò|Õ|Ô|Ö)/",
            "/(ú|ù|û|ü)/",
            "/(Ú|Ù|Û|Ü)/",
            "/(ñ)/",
            "/(Ñ)/",
            "/(ç)/",
            "/(Ç)/"
        ], [
            "a", "A", "e", "E", "i", "I", "o", "O", "u", "U", "n", "N", "c", "C"
        ], $string);
    }

    public static function slug($value)
    {
        return Str::slug($value);
    }

    public static function camel($value)
    {
        return Str::camel($value);
    }

    public static function kebab($value)
    {
        return Str::kebab($value);
    }

    public static function snake($value)
    {
        return Str::snake($value);
    }

    //
    // NUMBERS
    //

    public static function int($value)
    {
        if ($value && is_numeric($value))
            return number_format($value, 0, ',', '.');
        return $value;
    }

    public static function decimal($value)
    {
        if ($value && is_numeric($value))
            return number_format($value, 2, ',', '.');
        return $value;
    }

    //
    // SUFFIXED
    //

    public static function percent($value)
    {
        return self::decimal($value) . '%';
    }

    public static function kg($value)
    {
        return self::decimal($value) . 'Kg';
    }

    public static function kmh($value)
    {
        return self::decimal($value) . 'Km/h';
    }

    public static function rpm($value)
    {
        return self::decimal($value) . 'rp/m';
    }

    public static function m2($value)
    {
        return self::decimal($value) . 'm²';
    }

    public static function ml($value)
    {
        return self::decimal($value) . 'ml';
    }

    //
    // MONEY
    //

    public static function money($value)
    {
        return self::rbl($value);
    }

    public static function usd($value)
    {
        return 'U$ ' . self::decimal($value);
    }

    public static function rbl($value)
    {
        return 'R$ ' . self::decimal($value);
    }

    //
    // POST CODE
    //

    public static function cep($value)
    {
        return strlen($value) <= 8
            ? AOPDF::mask(str_pad($value, 8, 0, STR_PAD_LEFT), '##.###-###')
            : $value;
    }

    //
    // DOCUMENTS
    //

    public static function cpf($value)
    {
        return strlen($value) == 11
            ? AOPDF::mask($value, '###.###.###-##')
            : $value;
    }

    public static function cnpj($value)
    {
        return strlen($value) == 14
            ? AOPDF::mask($value, '##.###.###/####-##')
            : $value;
    }

    //
    // PHONE
    //

    public static function phone($value)
    {
        if (strlen($value) == 12)
            return AOPDF::mask($value, '(##) ##-####-####');

        if (strlen($value) == 11)
            return AOPDF::mask($value, '(##) #-####-####');

        if (strlen($value) == 10)
            return AOPDF::mask($value, '(##) ####-####');

        if (strlen($value) == 9)
            return AOPDF::mask($value, '#-####-####');

        if (strlen($value) == 8)
            return AOPDF::mask($value, '####-####');

        if (strlen($value) == 7)
            return AOPDF::mask($value, '###-####');

        return $value;
    }

    //
    // DATE
    //

    public static function date($value)
    {
        return is_numeric($value) ? self::dateByTimestamp($value) : self::dateByString($value);
    }

    public static function datetime($value)
    {
        return is_numeric($value) ? self::datetimeByTimestamp($value) : self::datetimeByString($value);
    }

    public static function time($value)
    {
        return is_numeric($value) ? self::timeByTimestamp($value) : self::timeByString($value);
    }

    public static function dateByString($value)
    {
        try {
            return (new \DateTime($value))->format('d/m/Y');
        } catch (\Exception $e) {
            return $value;
        }
    }

    public static function datetimeByString($value)
    {
        try {
            return (new \DateTime($value))->format('d/m/Y H:i:s');
        } catch (\Exception $e) {
            return $value;
        }
    }

    public static function timeByString($value)
    {
        try {
            return (new \DateTime($value))->format('H:i:s');
        } catch (\Exception $e) {
            return $value;
        }
    }

    public static function dateByTimestamp($value)
    {
        return date('d/m/Y', $value);
    }

    public static function datetimeByTimestamp($value)
    {
        return date('d/m/Y H:i:s', $value);
    }

    public static function timeByTimestamp($value)
    {
        return date('H:i:s', $value);
    }

    public static function dateByNow($value)
    {
        return date('d/m/Y');
    }

    public static function datetimeByNow($value)
    {
        return date('d/m/Y H:i:s');
    }

}
