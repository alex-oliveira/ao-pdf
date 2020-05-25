<?php

namespace AOPDF;

class AOPDFHelper
{

    public static function mask($value, $mask)
    {
        $maskared = '';

        $k = 0;

        for ($i = 0; $i <= strlen($mask) - 1; $i++) {
            if ($mask[$i] == '#') {
                if (isset($value[$k]))
                    $maskared .= $value[$k++];
            } else {
                if (isset($mask[$i]))
                    $maskared .= $mask[$i];
            }
        }

        return $maskared;
    }

    public static function ehBynaryString($input)
    {
        return preg_match('~[^\x20-\x7E\t\r\n]~', $input) > 0;
    }

    public static function convertBynaryString($input, $default = '-')
    {
        if (self::ehBynaryString($input)) {
            $input = preg_replace('~[^\x20-\x7E\t\r\n]~', $default, $input);
        }
        return $input;
    }

    public static function fixFDFvalue($value)
    {
        $qt = strlen($value);

        $nivel = 0;

        for ($i = 0; $i < $qt; $i++) {
            if ($value[$i] == '(') {
                $nivel++;
            } elseif ($value[$i] == ')') {
                $nivel--;
            }
            if ($nivel < 0) {
                break;
            }
        }

        $value = $nivel == 0 ? $value : str_replace('(', '-', (str_replace(')', '-', $value)));

        $value = self::convertBynaryString($value);

        return $value;
    }

}
