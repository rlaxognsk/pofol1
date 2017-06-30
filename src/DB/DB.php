<?php
namespace Pofol\DB;

use Exception;
use PDO;

class DB
{
    protected static $pdo;
    protected static $isSetBeginTransaction = false;

    private function __construct()
    {
        //
    }

    public static function init()
    {
        $db = config('db');

        $dsn = "mysql:dbname={$db['DB_NAME']};host={$db['DB_HOST']}";
        $user = $db['DB_USER'];
        $pass = $db['DB_PASS'];

        self::$pdo = new PDO($dsn, $user, $pass);
    }

    public static function getPDO()
    {
        if (!isset(self::$pdo)) {
            self::init();
        }

        return self::$pdo;
    }

    public static function table($table)
    {
        if (!isset(self::$pdo)) {
            self::init();
        }

        return new QueryBuilder(self::$pdo, $table);
    }

    public static function select($query, $params = [])
    {
        $stmt = self::query($query, $params);

        if (!$stmt->execute()) {
            $message = $stmt->errorInfo();

            throw new Exception("쿼리 실행 실패. {$message[2]}");
        }

        return $stmt->fetchObject();
    }

    protected static function query($query, $params)
    {
        if (!isset(self::$pdo)) {
            self::init();
        }

        $stmt = self::$pdo->prepare($query);

        var_dump($params);
        foreach ($params as $key => $value) {
            if (is_string($key)) {
                $stmt->bindValue($key, self::revealedType($value));
            } else {
                $stmt->bindValue(($key + 1), self::revealedType($value));
            }
        }

        return $stmt;
    }

    public static function beginTransaction()
    {
        if (!isset(self::$pdo)) {
            self::init();
        }

        self::$pdo->beginTransaction();
        self::$isSetBeginTransaction = true;
    }

    public static function commit()
    {
        if (!self::$isSetBeginTransaction) {
            throw new Exception("beginTransaction이 먼저 호출되어야 합니다.");
        }

        self::$pdo->commit();
    }

    public static function rollBack()
    {
        if (!self::$isSetBeginTransaction) {
            throw new Exception("beginTransaction이 먼저 호출되어야 합니다.");
        }

        self::$pdo->rollBack();
    }

    protected static function revealedType($value)
    {
        switch (gettype($value)) {
            case 'string':
                return PDO::PARAM_STR;

            case 'integer':
                return PDO::PARAM_INT;

            case 'NULL':
                return PDO::PARAM_NULL;

            case 'boolean':
                return PDO::PARAM_BOOL;

            default:
                return PDO::PARAM_STR;
        }
    }
}
