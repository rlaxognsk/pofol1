<?php
namespace Pofol\Router;

use Exception;

class HttpNotFoundException extends Exception
{
    protected $message = '404 NOT FOUND.';
}
