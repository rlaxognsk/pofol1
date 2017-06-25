<?php
namespace Pofol\DB;

use PDO;

class DB
{
    protected static $pdo = null;

    public function __construct()
    {
        $db = config('db');

        $dsn = "mysql:dbname={$db['DB_NAME']};host={$db['DB_HOST']}";
        $user = $db['DB_USER'];
        $pass = $db['DB_PASS'];

        self::$pdo = new PDO($dsn, $user, $pass);
    }
}
