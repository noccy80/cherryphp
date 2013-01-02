<?php

namespace Cherry\Database;
use ArrayAccess;

class SchemaManager implements ArrayAccess {

    private $db = null;

    public function __construct(DatabaseConnection $db) {
        $this->db = $db;
    }

    public function getAll() {
        return new DatabaseTableSet($this->db);
    }

    public function offsetGet($key) {
        return new DatabaseTable($this->db,$key);
    }

    public function offsetSet($key,$value) {
    }

    public function offsetExists($key) {
    }

    public function offsetUnset($key) {
    }

}
