<?php
namespace Pofol\DB;

use Exception;
use PDO;
use Pofol\Support\Str;

class QueryBuilder
{
    const STMT_SELECT = 0;
    const STMT_INSERT = 1;
    const STMT_DELETE = 2;
    const STMT_UPDATE = 3;
    const OPER_AND = 10;
    const OPER_OR = 11;

    protected $pdo;
    protected $statement;
    protected $table;
    protected $columns = [];
    protected $conditions = [];
    protected $whereQueue = [];
    protected $values = [];
    protected $boundValues = [];

    public function __construct(PDO $pdo, $table)
    {
        $this->table = $table;
        $this->pdo = $pdo;
    }

    public function select(...$columns)
    {
        if (isset($this->statement)) {
            throw new Exception("이미 statement가 선언되었습니다.");
        }

        $this->statement = self::STMT_SELECT;

        if (is_array($columns[0])) {
            $columns = $columns[0];
        }

        $this->columns = $columns;

        return $this;
    }

    public function insert(array $data)
    {
        if (isset($this->statement)) {
            throw new Exception("이미 statement가 선언되었습니다.");
        }

        $this->statement = self::STMT_INSERT;

        // $data가 2차원 배열이면 $data[0]이 존재할 것이다.
        // 그리고 2차원 배열이란 뜻은 데이터를 여러개 넣었다는 뜻이다.
        if (isset($data[0])) {
            // 첫번째 배열엔 column 이름이 들어있다.
            $this->columns = $data[0];

            for ($i = 1, $length = count($data); $i < $length; $i++) {
                $this->values[] = $data[$i];
            }
        } else {
            foreach ($data as $key => $value) {
                $this->columns[] = $key;
                $this->values[] = $value;
            }
        }

        $this->execute();
    }

    public function where(...$conditions)
    {
        $this->handlingWhereQueue(self::OPER_AND);
        $this->handlingWhere($conditions);

        return $this;
    }

    public function orWhere(...$conditions)
    {
        $this->handlingWhereQueue(self::OPER_OR);
        $this->handlingWhere($conditions);

        return $this;
    }

    public function delete()
    {
        if (isset($this->statement)) {
            throw new Exception("이미 statement가 선언되었습니다.");
        }

        $this->statement = self::STMT_DELETE;

        $stmt = $this->execute();

    }

    public function update(array $data)
    {
        if (isset($this->statement)) {
            throw new Exception("이미 statement가 선언되었습니다.");
        }

        $this->statement = self::STMT_UPDATE;

        $temp = [];

        foreach ($data as $key => $value) {
            $this->values[] = "$key = ?";
            $temp[] = $value;
        }

        $this->boundValues = array_merge($temp, $this->boundValues);

        $stmt = $this->execute();
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

        switch ($logicOperator) {
            case self::OPER_AND:
                $this->whereQueue[] = 'AND';
                return;

            case self::OPER_OR:
                $this->whereQueue[] = 'OR';
                return;
        }
    }

    protected function qualifyCondition(array $condition)
    {
        $length = count($condition);

        if ($length === 2) {
            $this->boundValues[] = $condition[1];
            $condition = ["`{$condition[0]}`", '=', '?'];
            return $condition;
        } elseif ($length === 3) {
            $this->boundValues[] = $condition[2];
            $condition[0] = "`{$condition[0]}`";
            $condition[2] = '?';
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
        $stmt = $this->execute();

        $tableName = Str::toCamel($this->table);
        $filePath = __PF_ROOT__ . '\\' . 'app\\' . $tableName . '.php';
        $className = 'App\\' . $tableName;

        if (file_exists($filePath)) {
            return $stmt->fetchAll(PDO::FETCH_CLASS, $className);
        }

        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    protected function execute()
    {
        if (!isset($this->statement)) {
            throw new Exception("쿼리 statement가 선언되지 않았습니다.");
        }

        $query = $this->buildQuery();

        var_dump($query);
        var_dump($this->boundValues);

        $stmt = $this->pdo->prepare($query);

        for ($i = 0, $len = count($this->boundValues); $i < $len; $i++) {
            $value = $this->boundValues[$i];

            $stmt->bindValue($i + 1, $value, $this->revealedType($value));
        }

        $stmt->execute();

        return $stmt;
    }

    protected function buildQuery()
    {
        $query = [];

        switch ($this->statement) {
            case self::STMT_SELECT:
                $this->buildSelect($query);
                $this->buildFrom($query);
                $this->buildWhereIfExist($query);
                break;

            case self::STMT_INSERT:
                $this->buildInsert($query);
                $this->buildValues($query);
                break;

            case self::STMT_DELETE:
                $this->buildDelete($query);
                $this->buildWhereIfExist($query);
                break;

            case self::STMT_UPDATE:
                $this->buildUpdate($query);
                $this->buildWhereIfExist($query);
                break;

            default:
                throw new Exception("쿼리 statement가 선언되지 않았습니다.");
        }

        return join(' ', $query);
    }

    protected function buildSelect(&$query)
    {
        $query[] = 'SELECT';

        $this->wrapGraves($this->columns);

        $query[] = join(', ', $this->columns);
    }

    protected function buildFrom(&$query)
    {
        $query[] = "FROM `{$this->table}`";
    }

    protected function buildWhereIfExist(&$query)
    {
        if (empty($this->conditions)) {
            return;
        }
        // stack을 뒤집어서 queue로 사용한다.
        if (count($this->whereQueue) > 1) {
            $this->whereQueue = array_reverse($this->whereQueue);
        }
        // dummy값 제거
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

    protected function buildInsert(&$query)
    {
        $query[] = "INSERT INTO `{$this->table}`";

        $this->wrapGraves($this->columns);

        $query[] = '(' . join(', ', $this->columns) . ')';
    }

    protected function buildDelete(&$query)
    {
        $query[] = "DELETE FROM `{$this->table}`";
    }

    protected function buildUpdate(&$query)
    {
        $query[] = "UPDATE `{$this->table}` SET";

        $this->wrapGraves($this->values);

        $query[] = join(', ', $this->values);
    }


    protected function buildValues(&$query)
    {
        $query[] = 'VALUES';
        $values = [];

        if (!is_array($this->values[0])) {
            for ($i = 0, $len = count($this->values); $i < $len; $i++) {
                $this->boundValues[] = $this->values[$i];
                $values[] = '?';
            }
            $query[] = '(' . join(', ', $values) . ')';
        } else {
            foreach ($this->values as $value) {
                for ($i = 0, $len = count($value); $i < $len; $i++) {
                    $this->boundValues[] = $value[$i];
                    $value[$i] = '?';
                }

                $values[] = '(' . join(', ', $value) . ')';
            }

            $query[] = join(', ', $values);
        }
    }

    protected function wrapGraves(array &$values)
    {
        array_walk($values, function (&$item) {
            $item = "`$item`";
        });
    }

    protected function revealedType($value)
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
