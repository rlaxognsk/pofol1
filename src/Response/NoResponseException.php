<?php
namespace Pofol\Response;

use Exception;

class NoResponseException extends Exception
{
    protected $message = 'Response does not exist.';
}
