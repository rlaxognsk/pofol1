<?php
namespace Pofol\DB;

use JsonSerializable;
use Pofol\Support\Str;
use ReflectionClass;
use ReflectionProperty;

class Model implements JsonSerializable
{
    protected $table;
    protected $primaryKey;
    protected $hidden = [];
    protected static $preExistPropsKey = [];

    public function getTable()
    {
        if (isset($this->table)) {
            return $this->table;
        }

        $fullName = get_class($this);
        $name = array_pop(explode('\\', $fullName));

        $this->table = Str::toSnake($name);

        return $this->table;
    }

    public function getPrimaryKey()
    {
        if (isset($this->primaryKey)) {
            return $this->primaryKey;
        }

        $tableName = $this->getTable();

        $this->primaryKey = $tableName . '_id';

        return $this->primaryKey;
    }

    public function toArray()
    {
        $reflect = new ReflectionClass($this);

        $preExistProps = $reflect->getProperties(
            ReflectionProperty::IS_PROTECTED |
            ReflectionProperty::IS_PRIVATE |
            ReflectionProperty::IS_STATIC
        );

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
