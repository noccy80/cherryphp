<?php

namespace Cherry\Database;

class DatabaseTable {

    private $db = null;
    private $table = null;
    private $metadata = null;

    public function __construct(DatabaseConnection $db,$table) {
        $this->db = $db;
        $this->table = $table;
    }

    public function exists() {
        if (!$this->metadata) $this->getMeta();
        return ($this->metadata != null);
    }

    public function __get($key) {
        switch($key) {
            case 'name':
                return $this->table;
            case 'database':
                return $this->db;
            default:
                break;
        }
    }

    public function getColumns() {
        if (!$this->metadata) $this->getMeta();
        return $this->metadata;
    }

    public function applySdlNode(\Cherry\Data\Ddl\SdlNode $node) {
        
    }

    private function getMeta() {
        try {
            $rows = $this->db->query("SHOW COLUMNS FROM ".$this->table);
        } catch (Exception $e) {
            $this->metadata = false;
            return;
        }
        if (!$rows) {
            $this->metadata = false;
            return;
        }
        $cols = [];
        foreach($rows as $row) {
            $cname = $row['Field'];
            $ctype = $row['Type'];
            $cnull = $row['Null'];
            $ckey  = $row['Key'];
            $cdef  = $row['Default'];
            $cext  = $row['Extra'];
            $cols[$cname] = (object)[
                'name'=> $cname,
                'type'=> $ctype,
                'null'=> ($cnull=='YES'),
                'default' => $cdef,
                'key' => $ckey,
                'auto' => (strpos($cext,'auto_increment')!==false)
            ];
        }
        $this->metadata = $cols;
    }

    public function getIndexes() {
        $rows = $this->db->query("SHOW INDEX FROM ".$this->table);
        $cols = [];
        $idx = [];
        foreach($rows as $row) {
            $kn = $row['Key_name'];
            if (!array_key_exists($kn,$idx)) {
                $idx[$kn] = (object)[
                    'name' => $kn,
                    'type' => ($row['Non_unique']==0)?'unique':'index',
                    'columns' => []
                ];
            }
            $idx[$kn]->columns[] = $row['Column_name'];
        }
        return $idx;
    }

}
