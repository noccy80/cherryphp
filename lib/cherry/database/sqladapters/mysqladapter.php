<?php

namespace Cherry\Database\SqlAdapters;

use Cherry\Data\Ddl\SdlTag;

class MySqlAdapter extends SqlAdapter {

    private $db;

    function __construct($db) {
        $this->db = $db;

    }
    
    public function getSdlFromTable($table) {
        $meta = $this->getTableMeta($table);
        $root = new SdlTag("table", [ $table ]);
        foreach($meta as $row=>$md) {
            $col = new SdlTag("column", [ $row ]);
            $col->type = $md->type;
            if (!$md->null) $col->null = false;
            $col->default = $md->default;
            $col->setComment($md->comment);
            $root->addChild($col);
        }
        return $root->encode();
    }

    public function getAlterFromSdl(SdlTag $tag, $meta) {
        $cols = [];
        foreach($tag->query("column") as $column) {
            if (array_key_exists($column[0],$meta)) {
                $ctype = $this->getSqlVartype($column);
                $atype = strtoupper($meta[$column[0]]->type);
                if (($ctype != $atype)) {
                    $cid = $column[0];
                    $ctype = $this->getSqlVartype($column);
                    $col = "CHANGE COLUMN `{$cid}` `{$cid}` $ctype";
                    $col.= ($column->null?' NULL':' NOT NULL');
                    if ($column->default) $col.= " DEFAULT '".$column->default."'";
                    $cols[$cid] = $col;
                }
            } else {
                $cid = $column[0];
                $ctype = $this->getSqlVartype($column);
                $col = "ADD COLUMN `{$cid}` $ctype";
                $col.= ($column->null?' NULL':' NOT NULL');
                if ($column->default) $col.= " DEFAULT '".$column->default."'";
                $cols[$cid] = $col;
            }
        }
        if (($tag->auto) && (!array_key_exists($tag->auto,$meta))) {
            $cols[$tag->auto] = "ADD COLUMN `{$tag->auto}` INT NOT NULL PRIMARY KEY AUTO_INCREMENT";
        }
        foreach($meta as $col=>$cmeta) {
            if ((!$tag->getChild("column",$col)) && ($tag->auto != $col)) {
                $cols[$col] = "DROP COLUMN `{$col}`";
            }
        }
        if (count($cols)>0) {
            $sql = "ALTER TABLE `{$tag[0]}` ".join(", ",$cols);
            return $sql;
        }
        return null;
    }

    public function getCreateFromSdl(SdlTag $tag) {
        $sql = "CREATE TABLE {$tag[0]} (\n    ";
        $cols = [];
        foreach($tag->query("column") as $column) {
            $cid = $column[0];
            $ctype = $this->getSqlVartype($column);
            $col = "`{$cid}` $ctype";
            $col.= ($column->null?' NULL':' NOT NULL');
            if ($column->default) $col.= " DEFAULT '".$column->default."'";
            $cols[$cid] = $col;
        }
        if ($tag->auto) {
            if (!in_array($tag->auto,$cols)) {
                array_unshift($cols,"`{$tag->auto}` INT NOT NULL PRIMARY KEY AUTO_INCREMENT");
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
                if ($len < 1024) { $type = "VARCHAR({$len})"; }
                    elseif ($len < 65535) { $type = "TEXT"; }
                        else { $type = "MEDIUMTEXT"; }
                break;
            case 'blob':
                if (!$len) $len = 65535;
            case 'binary':
                if ($len==null) $len = 250;
                if ($len < 1024) { $type = "VARBINARY({$len})"; }
                    elseif ($len < 65535) { $type = "BLOB"; }
                        else { $type = "MEDIUMBLOB"; }
                break;
            case 'bool':
                $type = "TINYINT(1)";
                break;
            case 'int':
            case 'integer':
                if (!$len) $len = 11;
                $type = "INT";
                $type .= "({$len})";
                break;
            case 'float':
                $type = "FLOAT";
                if ($len) $type .= "({$len})";
                break;
            case 'double':
                $type = "FLOAT";
                if ($len) $type .= "({$len})";
                break;
            case 'date':
                $type = "DATETIME";
                break;
            case 'set':
                $values = [];
                foreach ($tag->query("value") as $value) {
                    $values[] = $value[0];
                }
                $type = "SET('".join("','",$values)."')";
            case 'enum':
                $values = [];
                foreach ($tag->query("value") as $value) {
                    $values[] = $value[0];
                }
                $type = "ENUM('".join("','",$values)."')";
        }
        return $type;
    }

    public function getTableList() {
        $ret = $this->db->query("SHOW TABLES")->fetchAll();
        $out = array_map(
            function ($in) { return $in[0]; },
            $ret
        );
        return $out;
    }

    public function getTable($table) {
        return $this->getTableMeta($table);
    }

    private function getTableMeta($table) {
        try {
            $rows = $this->db->query("SHOW FULL COLUMNS FROM `".$table."`");
            $stat = $this->db->query("SHOW TABLE STATUS LIKE '".$table."'");
            $this->tablemeta = $stat->fetch();
        } catch (Exception $e) {
            return false;
        }
        if (!$rows) {
            return false;
        }
        $cols = [];
        foreach($rows as $row) {
            $cname = $row[0];
            $ctype = $row[1];
            $cnull = $row[3];
            $ckey  = $row[4];
            $cdef  = $row[5];
            $cext  = $row[6];
            $ccom  = $row[8];
            $cols[$cname] = (object)[
                'name'      => $cname,
                'type'      => $ctype,
                'null'      => ($cnull=='YES'),
                'default'   => $cdef,
                'key'       => $ckey,
                'auto'      => (strpos($cext,'auto_increment')!==false),
                'comment'   => $ccom
            ];
        }
        return $cols;
    }

    public function getIndexes($table) {
        $rows = $this->db->query("SHOW INDEX FROM ".$table);
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
    
    public function getCreateTableSql($table) {
        $ret = $this->db->query("SHOW CREATE TABLE `{$table}`'")->fetch();
        return $ret[1];

    }    

}
