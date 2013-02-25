<?php

namespace Cherry\Database;
use ArrayAccess;

use Cherry\Data\Ddl\SdlTag;

class SchemaManager implements ArrayAccess {

    private $db = null;
    private $tables = [];
    private $adapter = null;

    public function __construct(DatabaseConnection $db) {
        $this->db = $db;
        $this->adapter = $db->getSqlAdapter();
        $this->tables = array_map(
            function ($in) { return $in[0]; },
            $this->adapter->getTableList()
        );
    }

    public function getAllTables() {
        $out = [];
        foreach($this->tables as $table) {
            $out[$table] = $this->adapter->getTable($table);
        }
        return $out;
    }

    public function getTableList() {
        return $this->tables;
    }

    public function applyTableSdl(SdlTag $tag) {
        $tm = $this[$tag[0]];
        if ($tm) {
            $sql = $this->adapter->getAlterFromSdl($tag,$tm);
        } else {
            $sql = $this->adapter->getCreateFromSdl($tag);
        }
        if (!$sql)
            return false;
        $this->db->execute($sql);
        return true;
    }

    public function offsetGet($key) {
        return $this->adapter->getTable($key);
    }

    public function offsetSet($key,$value) {
    }

    public function offsetExists($key) {
    }

    public function offsetUnset($key) {
    }

}
