<?php

use Pofol\Injector\Injector;
use Pofol\Response\Response;

define('__PF_ROOT__', realpath(__DIR__ . '/..'));

if (!function_exists('config')) {

    function config($config, $value = null) {
        $path = __DIR__ . '\\..\\config';
        $config = explode('.', $config);

        $arr = require $path . '\\' . $config[0] . '.php';

        $config_length = count($config);

        if ($config_length === 1) {
            return $arr;
        }

        $cur = $arr[$config[1]];

        for ($i = 2; $i < $config_length; $i++) {
            if (!is_array($cur)) {
                throw new Exception('config 값이 올바르지 않습니다.');
            }

            $cur = $cur[$config[$i]];
        }

        if (empty($cur)) {
            return $value;
        }

        return $cur;
    }
}

if (!function_exists('injector')) {

    function injector($className, ...$params) {
        return Injector::instance($className, ...$params);
    }
}

if (!function_exists('e')) {

    function e($expression) {
        return htmlspecialchars($expression, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('response')) {

    function response($statusCode = null, array $headers = []) {
        return new Response($statusCode, $headers);
    }
}

if (!function_exists('redirect')) {

    function redirect($location) {
        $response = new Response();
        $response->redirect($location);
    }
}

if (!function_exists('view')) {

    function view($fileName, array $variables = []) {
        $response = new Response();

        return $response->view($fileName, $variables);
    }
}
