<?php
namespace Pofol\DB;

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

    protected static $specialCharacters = ['>', '<', '=', '<>', '!=', '*'];

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
    protected $inItems = [];

    public function __construct(PDO $pdo, $table)
    {
        $this->table = $table;
        $this->pdo = $pdo;
    }

    public function select(...$columns)
    {
        if (isset($this->statement)) {
            throw new QueryException("이미 statement가 선언되었습니다.");
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
            throw new QueryException("SELECT statement를 호출한 뒤에 사용해야 합니다.");
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

    public function join(...$args)
    {
        if (count($args) !== 4) {
            throw new QueryException("join 매개변수를 확인하세요.");
        }

        $this->clauses['innerJoin'][] = $args;

        return $this;
    }

    public function leftJoin(...$args)
    {
        if (count($args) !== 4) {
            throw new QueryException("leftJoin 매개변수를 확인하세요.");
        }

        $this->clauses['leftJoin'][] = $args;

        return $this;
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
            throw new QueryException("이미 ASC, DESC가 선언되었습니다.");
        } elseif (count($columns) === 0) {
            throw new QueryException("정렬 기준 column이 입력되어야 합니다.");
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
            throw new QueryException("limit을 먼저 호출해주세요.");
        }

        $this->clauses['offset'] = (int)$value;

        return $this;
    }

    public function insert(array $data)
    {
        if (isset($this->statement)) {
            throw new QueryException("이미 statement가 선언되었습니다.");
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

    public function whereIn($column, ...$values)
    {
        if (is_array($values[0])) {
            $values = $values[0];
        }

        if (count($values) === 0) {
            throw new QueryException("column을 입력하셔야 합니다.");
        }

        foreach ($values as $value) {
            $this->inItems[$column][] = $value;
        }

        return $this;
    }

    public function delete()
    {
        if (isset($this->statement)) {
            throw new QueryException("이미 statement가 선언되었습니다.");
        }

        $this->statement = self::STMT_DELETE;

        $stmt = $this->execute();

        return $stmt->rowCount();
    }

    public function update(array $data)
    {
        if (isset($this->statement)) {
            throw new QueryException("이미 statement가 선언되었습니다.");
        }

        $this->statement = self::STMT_UPDATE;

        $temp = [];

        foreach ($data as $key => $value) {
            $this->values[] = "`$key` = ?";
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
                throw new QueryException("decrement 사용 방식이 잘못되었습니다.");
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
                throw new QueryException("타입이 정해지지 않았습니다.");
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
                throw new QueryException("where 형식이 잘못되었습니다.");
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
            throw new QueryException("where 형식이 잘못되었습니다.");
        }
    }

    public function first()
    {
        $stmt = $this->execute();

        $modelName = Str::toCamel($this->table);
        $modelName = substr($modelName, 0, -1);
        $filePath = __PF_ROOT__ . '\\' . 'app\\' . $modelName . '.php';
        $className = 'App\\' . $modelName;

        if (file_exists($filePath)) {
            return $stmt->fetchObject($className, [[], true]);
        }

        return $stmt->fetchObject();
    }

    public function get()
    {
        $stmt = $this->execute();

        $modelName = Str::toCamel($this->table);
        $filePath = __PF_ROOT__ . '\\' . 'app\\' . $modelName . '.php';
        $className = 'App\\' . $modelName;

        if (file_exists($filePath)) {
            return $stmt->fetchAll(PDO::FETCH_CLASS, $className, [[], true]);
        }

        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    protected function prepareModelObject()
    {
        // TODO: 이거 필요한 메서드인가?
    }

    protected function execute()
    {
        if (!isset($this->statement)) {
            throw new QueryException("쿼리 statement가 선언되지 않았습니다.");
        }

        $query = $this->buildQuery();

        var_dump($query);

        $stmt = $this->pdo->prepare($query);

        if (!empty($this->boundValues)) {
            for ($i = 0, $len = count($this->boundValues); $i < $len; $i++) {
                $value = $this->boundValues[$i];

                $stmt->bindValue($i + 1, $value, $this->revealedType($value));
            }
        } elseif (!empty($this->inItems)) {
            foreach ($this->inItems as $key => $values) {
                for ($i = 0, $len = count($values); $i < $len; $i++) {
                    $value = $values[$i];

                    $stmt->bindValue($i + 1, $value, $this->revealedType($value));
                }
            }
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
                $this->buildWhereInIfExist($query);
                $this->buildClauses($query);
                break;

            case self::STMT_INSERT:
                $this->buildInsert($query);
                $this->buildValues($query);
                break;

            case self::STMT_DELETE:
                $this->buildDelete($query);
                $this->buildWhereIfExist($query);
                $this->buildWhereInIfExist($query);
                break;

            case self::STMT_UPDATE:
                $this->buildUpdate($query);
                $this->buildWhereIfExist($query);
                $this->buildWhereInIfExist($query);
                break;

            default:
                throw new QueryException("쿼리 statement가 선언되지 않았습니다.");
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

        if (!empty($this->inItems)) {
            throw new QueryException("where, orWhere은 whereIn과 같이 사용할 수 없습니다.");
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

    protected function buildWhereInIfExist(&$query)
    {
        if (empty($this->inItems)) {
            return;
        }

        if (!empty($this->conditions)) {
            throw new QueryException("whereIn은 where, orWhere과 같이 사용할 수 없습니다.");
        }

        $query[] = 'WHERE';

        foreach ($this->inItems as $key => $values) {
            $query[] = $this->wrapGrave($key);
            $query[] = 'IN';

            $query[] = '(' . join(', ', array_fill(0, count($values), '?')) . ')';
        }
    }

    protected function buildClauses(&$query)
    {
        if (isset($this->clauses['innerJoin'])) {
            foreach ($this->clauses['innerJoin'] as $value) {
                $this->wrapGraves($value);

                $query[] = 'INNER JOIN';
                $query[] = $value[0];
                $query[] = 'ON';
                $query[] = "{$value[1]} {$value[2]} {$value[3]}";
            }
        }

        if (isset($this->clauses['leftJoin'])) {
            foreach ($this->clauses['leftJoin'] as $value) {
                $this->wrapGraves($value);

                $query[] = 'LEFT JOIN';
                $query[] = $value[0];
                $query[] = 'ON';
                $query[] = "{$value[1]} {$value[2]} {$value[3]}";
            }
        }

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
                $value[1] = $value[1] === '*' ? '*' : "`{$value[1]}`";
                $newItem[] = join('.', $value);
            } else {
                $newItem[] = in_array($value, self::$specialCharacters) ? $value : "`$value`";
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
