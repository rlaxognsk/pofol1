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
    const UPDATE_INC = 20;
    const UPDATE_DEC = 21;

    protected $pdo;
    protected $statement;
    protected $table;
    protected $columns = [];
    protected $clauses = [];
    protected $conditions = [];
    protected $whereQueue = [];
    protected $values = [];
    protected $rawValues = [];
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

    public function addSelect(...$columns)
    {
        if (!isset($this->statement) || $this->statement !== self::STMT_SELECT) {
            throw new Exception("SELECT statement를 호출한 뒤에 사용해야 합니다.");
        }

        foreach ($columns as $column) {
            $this->columns[] = $column;
        }

        return $this;
    }

    public function distinct()
    {
        $this->clauses['distinct'] = true;

        return $this;
    }

    public function join()
    {
        
    }

    public function asc(...$columns)
    {
        $this->orderBy($columns);

        $this->clauses['orderBy'][] = 'ASC';

        return $this;
    }

    public function desc(...$columns)
    {
        $this->orderBy($columns);

        $this->clauses['orderBy'][] = 'DESC';

        return $this;
    }

    protected function orderBy(array $columns)
    {
        if (isset($this->clauses['orderBy'])) {
            throw new Exception("이미 ASC, DESC가 선언되었습니다.");
        } elseif (count($columns) === 0) {
            throw new Exception("정렬 기준 column이 입력되어야 합니다.");
        }

        foreach ($columns as $value) {
            $this->clauses['orderBy'][] = $this->wrapGrave($value);
        }
    }

    public function limit($value)
    {
        $this->clauses['limit'] = (int)$value;

        return $this;
    }

    public function offset($value)
    {
        if (!isset($this->clauses['limit'])) {
            throw new Exception("limit을 먼저 호출해주세요.");
        }

        $this->clauses['offset'] = (int)$value;

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

        $stmt = $this->execute();

        return $stmt->rowCount();
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

        return $stmt->rowCount();
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

        return $stmt->rowCount();
    }

    public function increment($column, ...$data)
    {
        return $this->updateVariation(self::UPDATE_INC, $column, $data);
    }

    public function decrement($column, ...$data)
    {
        return $this->updateVariation(self::UPDATE_DEC, $column, $data);
    }

    protected function updateVariation($type, $column, $data)
    {
        $variation = 1;

        $column = $this->wrapGrave($column);

        if (isset($data[0])) {
            if (is_numeric($data[0])) {
                $variation = $data[0];
            } elseif (is_array($data[0])) {
                $data = $data[0];
            } else {
                throw new Exception("decrement 사용 방식이 잘못되었습니다.");
            }
        }

        if (isset($data[1]) && is_array($data[1])) {
            $data = $data[1];
        }

        switch ($type) {
            case self::UPDATE_INC:
                $this->rawValues[] = "$column = $column + $variation";
                break;

            case self::UPDATE_DEC:
                $this->rawValues[] = "$column = $column - $variation";
                break;

            default:
                throw new Exception("타입이 정해지지 않았습니다.");
        }

        if (empty($data)) {
            $data = [];
        }

        return $this->update($data);
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
            $condition[0] = $this->wrapGrave($condition[0]);
            $this->boundValues[] = $condition[1];
            $condition = [$condition[0], '=', '?'];
            return $condition;
        } elseif ($length === 3) {
            $this->boundValues[] = $condition[2];
            $condition[0] = $this->wrapGrave($condition[0]);
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

        $modelName = Str::toCamel($this->table);
        $filePath = __PF_ROOT__ . '\\' . 'app\\' . $modelName . '.php';
        $className = 'App\\' . $modelName;

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
                $this->buildClauses($query);
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
        $query[] = !empty($this->clauses['distinct']) ? 'SELECT DISTINCT' : 'SELECT';

        if ($this->columns[0] !== '*') {
            $this->wrapGraves($this->columns);
        }

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

    protected function buildClauses(&$query)
    {
        if (isset($this->clauses['orderBy'])) {
            $order = array_pop($this->clauses['orderBy']);

            $query[] = 'ORDER BY';
            $query[] = join(', ', $this->clauses['orderBy']);
            $query[] = $order;
        }

        if (isset($this->clauses['limit'])) {
            $query[] = 'LIMIT';
            $query[] = $this->clauses['limit'];
        }

        if (isset($this->clauses['offset'])) {
            $query[] = 'OFFSET';
            $query[] = $this->clauses['offset'];
        }
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

        $query[] = join(', ', array_merge($this->values, $this->rawValues));
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
            $item = $this->wrapGrave($item);
        });
    }

    protected function wrapGrave($item)
    {
        $matches = [];
        $itemArr = null;
        $newItem = [];

        if (strpos($item, '\`') !== false) {
            $item = str_replace('\`', '', $item);
        }

        if (preg_match('/(\s[Aa][Ss]\s)/', $item, $matches)) {
            $itemArr = explode($matches[1], $item);
        } else {
            $itemArr = [$item];
        }

        foreach($itemArr as $value) {
            if (strpos($value, '.')) {
                $value = explode('.', $value);
                $value[0] = "`{$value[0]}`";
                $value[1] = $value[1] === '*' ? $value[1] : "`{$value[1]}`";
                $newItem[] = join('.', $value);
            } else {
                $newItem[] = "`$value`";
            }
        }

        if (count($newItem) === 2) {
            $newItem = join(' AS ', $newItem);
        } else {
            $newItem = $newItem[0];
        }

        return $newItem;
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
