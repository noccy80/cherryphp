<?php

namespace Cherry\Database\SqlAdapters;

use Cherry\Data\Ddl\SdlTag;

class MySqlAdapter extends SqlAdapter {

    private $db;

    function __construct($db) {
        $this->db = $db;
    }

    public function getAlterFromSdl(SdlTag $tag, $meta) {
    }

    public function getCreateFromSdl(SdlTag $tag) {
        $sql = "CREATE TABLE {$tag[0]} (\n    ";
        $cols = [];
        foreach($tag->spath("column") as $column) {
            $cid = $column[0];
            $ctype = $this->getSqlVartype($column);
            $col = "`{$cid}` $ctype";
            $col.= ($column->null?' NULL':' NOT NULL');
            if ($column->default) $col.= " DEFAULT '".$column->default."'";
            $cols[$cid] = $col;
        }
        if ($tag->auto) {
            if (!in_array($tag->auto,$cols)) {
                array_unshift($cols,"`{$tag->auto}` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT");
            }
        }
        $sql.= join(",\n    ",$cols);
        $sql.= "\n)\n";
        return $sql;
    }

    protected function getSqlVartype(SdlTag $tag) {
        $type = $tag->type;
        if (strpos($type,":")!==false) {
            list($type,$len) = explode(":",$type);
        } else {
            $len = null;
        }
        switch(strtolower($type)) {
            case 'text':
                if (!$len) $len = 65535;
            case 'char':
                if ($len==null) $len = 250;
                if ($len < 1024) { $type = "TEXT({$len})"; }
                    elseif ($len < 65535) { $type = "TEXT"; }
                        else { $type = "TEXT"; }
                break;
            case 'blob':
            case 'binary':
                $type = "BLOB";
                break;
            case 'bool':
                $type = "INTEGER";
                break;
            case 'int':
            case 'integer':
                if (!$len) $len = 11;
                $type = "INTEGER";
                $type .= "({$len})";
                break;
            case 'float':
                $type = "REAL";
                if ($len) $type .= "({$len})";
                break;
            case 'double':
                $type = "REAL";
                if ($len) $type .= "({$len})";
                break;
            case 'date':
                $type = "TEXT";
                break;
            case 'set':
                $values = [];
                foreach ($tag->spath("value") as $value) {
                    $values[] = $value[0];
                }
                $type = "TEXT";
            case 'enum':
                $values = [];
                foreach ($tag->spath("value") as $value) {
                    $values[] = $value[0];
                }
                $type = "TEXT";
        }
        return $type;
    }

    public function getTableList() {
    }

    public function getTable($table) {
    }

    private function getTableMeta($table) {
    }

    public function getIndexes($table) {
    }

}
