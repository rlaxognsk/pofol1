<?php
namespace Pofol\DB;

use JsonSerializable;
use Pofol\Support\Str;
use ReflectionClass;

class Model implements JsonSerializable
{
    protected $isExist;
    protected $table;
    protected $primaryKey;
    protected $hidden = [];
    protected static $preExistPropsKey = [];

    public function __construct(array $data = [], $isExist = false)
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }

        if (is_bool($isExist)) {
            $this->isExist = $isExist;
        } else {
            $this->isExist = false;
        }
    }

    public static function get($id)
    {
        $className = static::class;

        $instance = new $className;

        return $instance->getById($id);
    }

    public function getById($id, ...$args)
    {
        $builder = new QueryBuilder(DB::getPDO(), $this->getTable());

        if (count($args) !== 0) {
            return $builder->select(...$args)->where($this->getPrimaryKey(), $id)->first();
        }

        return $builder->select('*')->where($this->getPrimaryKey(), $id)->first();
    }

    public function save()
    {
        $builder = new QueryBuilder(DB::getPDO(), $this->getTable());
        $data = $this->toArray();

        if (isset($data[$this->getPrimaryKey()])) {
            unset($data[$this->getPrimaryKey()]);
        }

        if ($this->isExist) {
            $result = $builder
                ->where(
                    $this->getPrimaryKey(), (int)$this->{$this->primaryKey}
                )->update($data);
        } else {
            $result = $builder->insert($data);

            if ($result > 0) {
                $this->isExist = true;
            }
        }

        return $result;
    }

    public function getTable()
    {
        if (isset($this->table)) {
            return $this->table;
        }

        $fullName = get_class($this);
        $name = explode('\\', $fullName);
        $name = array_pop($name);

        $this->table = Str::toSnake($name) . 's';

        return $this->table;
    }

    public function getPrimaryKey()
    {
        if (isset($this->primaryKey)) {
            return $this->primaryKey;
        }

        $this->primaryKey = 'id';

        return $this->primaryKey;
    }

    public function toArray()
    {
        $reflect = new ReflectionClass($this);

        $preExistProps = $reflect->getProperties();

        $preExistPropsKey = [];

        $className = $reflect->getName();

        if (!isset(self::$preExistPropsKey[$className])) {
            foreach ($preExistProps as $props) {
                $preExistPropsKey[] = $props->getName();
            }
            self::$preExistPropsKey[$className] = $preExistPropsKey;
        } else {
            $preExistPropsKey = self::$preExistPropsKey[$className];
        }

        $array = get_object_vars($this);
        $realHidden = array_merge($this->hidden, $preExistPropsKey);

        foreach ($realHidden as $key) {
            if (array_key_exists($key, $array)) {
                unset($array[$key]);
            }
        }

        return $array;
    }

    public function toJson()
    {
        return json_encode($this->toArray());
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
