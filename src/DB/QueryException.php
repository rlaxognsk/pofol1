<?php
namespace Pofol\DB;

use Exception;

class QueryException extends Exception
{
    protected $message = "Query가 잘못되었습니다.";
}
