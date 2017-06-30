<?php
namespace Pofol\DB;

use Exception;
use PDO;
use Pofol\Support\Str;

class QueryBuilder
{
    protected $pdo;
    protected $method;
    protected $columns;
    protected $table;
    protected $conditions = [];
    protected $whereQueue = [];

    public function __construct(PDO $pdo, $table)
    {
        $this->table = $table;
        $this->pdo = $pdo;
    }

    public function select(...$columns)
    {
        $this->method = 'SELECT';

        if (is_array($columns[0])) {
            $columns = $columns[0];
        }

        $this->columns = $columns;

        return $this;
    }

    public function where(...$conditions)
    {
        $this->handlingWhereQueue('AND');
        $this->handlingWhere($conditions);

        return $this;
    }

    public function orWhere(...$conditions)
    {
        $this->handlingWhereQueue('OR');
        $this->handlingWhere($conditions);

        return $this;
    }

    protected function handlingWhere($conditions)
    {
        if (is_string($conditions[0])) {
            $qualified = $this->qualifyCondition($conditions);
            $this->conditions[] = join(' ', $qualified);
            return;
        }

        $conditions = $conditions[0];
        $groupConditions = [];

        foreach ($conditions as $condition) {
            if (!is_array($condition)) {
                throw new Exception("where 형식이 잘못되었습니다.");
            }

            $groupConditions[] = join(' ', $this->qualifyCondition($condition));
        }

        $this->conditions[] = '(' . join(' AND ', $groupConditions) . ')';
    }

    protected function handlingWhereQueue($logicOperator)
    {
        if (empty($this->whereQueue)) {
            $this->whereQueue[] = 0;
            return;
        }

        if ($logicOperator === 'OR') {
            $this->whereQueue[] = 'OR';
        } else {
            $this->whereQueue[] = 'AND';
        }
    }

    protected function qualifyCondition(array $condition)
    {
        $length = count($condition);

        if ($length === 2) {
            $condition = [$condition[0], '=', $this->revealedType($condition[1])];
            return $condition;
        } elseif ($length === 3) {
            $condition[2] = $this->revealedType($condition[2]);
            return $condition;
        } else {
            throw new Exception("where 형식이 잘못되었습니다.");
        }
    }

    public function first()
    {
        //
    }

    public function get()
    {
        if (!isset($this->method)) {
            throw new Exception("쿼리 방식이 선언되지 않았습니다.");
        }

        $query = $this->buildQuery();

        var_dump($query);

        $stmt = $this->pdo->prepare($query);

        $stmt->execute();

        $tableName = Str::toCamel($this->table);
        $filePath = __PF_ROOT__ . '\\' . 'app\\' . $tableName . '.php';
        $className = 'App\\' . $tableName;

        if (file_exists($filePath)) {
            return $stmt->fetchAll(PDO::FETCH_CLASS, $className);
        }

        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    protected function buildQuery()
    {
        $query = [];

        switch ($this->method) {
            case 'SELECT':
                $this->buildSelect($query);
                $this->buildFrom($query);

                if (empty($this->conditions)) {
                    break;
                }

                $this->buildWhere($query);
        }

        return join(' ', $query);
    }

    protected function buildSelect(&$query)
    {
        $query[] = $this->method;
        $query[] = join(', ', $this->columns);
    }

    protected function buildFrom(&$query)
    {
        $query[] = 'FROM ' . $this->table;
    }

    protected function buildWhere(&$query)
    {
        if (count($this->whereQueue) > 1) {
            $this->whereQueue = array_reverse($this->whereQueue);
        }

        array_pop($this->whereQueue);

        $where = [];

        foreach ($this->conditions as $condition) {
            $where[] = $condition;

            if (!empty($this->whereQueue)) {
                $where[] = array_pop($this->whereQueue);
            }
        }

        $query[] = 'WHERE ' . join(' ', $where);
    }

    protected function revealedType($value)
    {
        switch (gettype($value)) {
            case 'string':
                return "'{$value}'";

            case 'integer':
                return $value;

            case 'NULL':
                return 'NULL';

            case 'boolean':
                if ($value) {
                    return 'TRUE';
                } else {
                    return 'FALSE';
                }

            default:
                return $value;
        }
    }
}
