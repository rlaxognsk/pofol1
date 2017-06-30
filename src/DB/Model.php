<?php
namespace Pofol\DB;

use Pofol\Support\Str;

class Model
{
    protected $table;
    protected $primaryKey;

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
}
