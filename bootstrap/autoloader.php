<?php

function autoload($className) {
    $location = explode('\\', $className);

    switch ($location[0]) {
        case 'App':
            $root = '\\app';
            $className = str_replace('App\\', '', $className);
            break;

        case 'Pofol':
            $root = '\\src';
            $className = str_replace('Pofol\\', '', $className);
            break;
        default:
            return;
    }

    $path = __DIR__ . '\\..' . $root . '\\' . $className . '.php';

    require $path;
}

spl_autoload_register('autoload');
