<?php
namespace Pofol\Support;

class Str
{
    public static function qualifyUrl($url)
    {
        $url = preg_replace('/\?.*/', '', $url);

        if ($url[strlen($url) - 1] !== '/') {
            $url .= '/';
        }

        return $url;
    }

    public static function toSnake($value)
    {
        $patterns = [
            '/([a-z\d])([A-Z])/',
            '/([^_])([A-Z][a-z\d])/',
        ];

        $replacement = '${1}_${2}';

        return strtolower(preg_replace($patterns, $replacement, $value));
    }

    public static function toCamel($value)
    {
        return str_replace('_', '', ucwords(ucfirst($value), '_'));
    }
}
