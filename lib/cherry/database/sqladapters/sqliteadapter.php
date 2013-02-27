<?php

namespace Cherry\Database\SqlAdapters;

use Cherry\Data\Ddl\SdlTag;

class SqliteAdapter extends SqlAdapter {

    private $db;

    function __construct($db) {
        $this->db = $db;
    }

    public function getAlterFromSdl(SdlTag $tag, $meta) {
        $cols = [];
        $ret = [];
        foreach($tag->spath("column") as $column) {
            if (array_key_exists($column[0],$meta)) {

                // TODO: Rewrite this part with a function to cast to appropriate
                // null and string values for match.
                //var_dump($meta[$column[0]]);
                //echo $column->encode()."\n";
                $ctype = $this->getSqlVartype($column);
                $atype = strtoupper($meta[$column[0]]->type);
                $_adef = $meta[$column[0]]->default;
                $_cdef = $column->default;
                if (!is_numeric($_cdef) && is_string($_cdef)) $_cdef = "'{$_cdef}'";
                $cdef = ($_cdef===null)?'NULL':$_cdef;
                $adef = ($_adef===null)?'NULL':$_adef;
                $cnull = (($column->null===true))?'NULL':'NOT NULL';
                $anull = (($meta[$column[0]]->null===true) || ($meta[$column[0]]->null===null))?'NULL':'NOT NULL';
                //echo "{$column[0]} [$ctype,$atype],[$cdef,$adef],[$cnull,$anull]\n";

                if (($ctype != $atype) || ($cdef != $adef) || ($cnull != $anull)) {
                    if (SQLITE_RECREATE)
                        return [
                            "DROP TABLE `{$tag[0]}`",
                            $this->getCreateFromSdl($tag)
                        ];
                    else
                        throw new \Exception("Columns can not be altered or renamed in SQLite, can not apply column {$column[0]} to schema without SQLITE_RECREATE defined");
                }
            } else {
                $cid = $column[0];
                $ctype = $this->getSqlVartype($column);
                $col = "ALTER TABLE `{$tag[0]}` ADD COLUMN `{$cid}` $ctype";
                $col.= ($column->null?' NULL':' NOT NULL');
                if ($column->hasAttribute('default')) {
                    $str = $this->getSqlString($column->default);
                    $col.= " DEFAULT {$str}";
                }
                $cols[$cid] = $col;
                $ret[] = $col;
            }
        }
        if (($tag->auto) && (!array_key_exists($tag->auto,$meta))) {
            if (SQLITE_RECREATE)
                return [
                    "DROP TABLE `{$tag[0]}`",
                    $this->getCreateFromSdl($tag)
                ];
            else
                throw new \Exception("Columns can not be altered or renamed in SQLite, can not apply new index to schema without SQLITE_RECREATE defined");
        }
        foreach($meta as $col=>$cmeta) {
            if ((!$tag->getChild("column",$col)) && ($tag->auto != $col)) {
            if (SQLITE_RECREATE)
                return [
                    "DROP TABLE `{$tag[0]}`",
                    $this->getCreateFromSdl($tag)
                ];
            else
                throw new \Exception("Columns can not be altered or renamed in SQLite, can not apply schema without SQLITE_RECREATE defined");
            }
        }
        return $ret;
    }

    public function getCreateFromSdl(SdlTag $tag) {
        $sql = "CREATE TABLE {$tag[0]} (\n    ";
        $cols = [];
        foreach($tag->spath("column") as $column) {
            $cid = $column[0];
            $ctype = $this->getSqlVartype($column);
            $col = "`{$cid}` $ctype";
            $col.= ($column->null?' NULL':' NOT NULL');
            if ($column->hasAttribute('default')) {
                $str = $this->getSqlString($column->default);
                $col.= " DEFAULT {$str}";
            }
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

    protected function getSqlString($def) {
        if (is_null($def))
            $str = "NULL";
        elseif ($def===true)
            $str = 1;
        elseif ($def===false)
            $str = 0;
        else
            $str = $this->db->escape($def);
        return $str;
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
        $ret = $this->db->query("SELECT * FROM sqlite_master WHERE type='table'")->fetchAll();
        $out = array_map(function($o){
            return $o['name'];
        },$ret);
        return (array)$out;
    }

    public function getTable($table) {
        return $this->getTableMeta($table);
    }

    private function getTableMeta($table) {
        $rows = $this->db->query("PRAGMA TABLE_INFO({$table})")->fetchAll();
        $cols = [];
        foreach($rows as $row) {
            $cols[$row['name']] = (object)[
                'name'      => $row['name'],
                'type'      => $row['type'],
                'null'      => ($row['notnull']==0),
                'default'   => $row['dflt_value'],
                'key'       => $row['pk'],
                'auto'      => null,
                'comment'   => null
            ];
        }
        return $cols;
    }

    public function getCreateTableSql($table) {
        $ret = $this->db->query("SELECT * FROM sqlite_master WHERE type='table' AND name='{$table}'")->fetch();
        return $ret['sql'];

    }

    public function getIndexes($table) {
    }

}
