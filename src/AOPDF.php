<?php

namespace AOPDF;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AOPDF
{

    public static function encode($data)
    {
        return base64_encode(json_encode($data));
    }

    public static function decode($data)
    {
        return json_decode(base64_decode($data));
    }

    public static function uniqid($suffix = '')
    {
        return time() . '_' . uniqid() . $suffix;
    }

    public static function exec($command)
    {
        $output = [];

        exec(is_array($command) ? implode(' ', $command) : $command, $output);

        return $output;
    }

    public static function root($path = '')
    {
        return storage_path('ao-pdf' . (empty($path) ? '' : Str::start($path, '/')));
    }

    /**
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    public static function disk()
    {
        static $filesystem = null;

        if (is_null($filesystem)) {
            Config::set('filesystems.disks.ao-pdf', ['driver' => 'local', 'root' => self::root()]);
            $filesystem = Storage::disk('ao-pdf');
        }

        $filesystem->path = function ($path = '') {
            return self::root($path);
        };

        return $filesystem;
    }

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
