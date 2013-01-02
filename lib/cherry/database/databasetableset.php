<?php

namespace Cherry\Database;
use IteratorAggregate;
use ArrayAccess;

class DatabaseTableSet implements IteratorAggregate {

    private $db = null;
    private $tables = [];

    public function __construct(DatabaseConnection $db) {
        $this->db = $db;
        $rows = $this->db->query("SHOW TABLE STATUS");
        foreach($rows as $row) {
            $this->tables[$row["Name"]] = new DatabaseTable($db,$row["Name"]);
        }
    }

    public function getIterator() {
        return new \ArrayIterator($this->tables);
    }

}
