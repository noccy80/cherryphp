<?php

namespace Cherry\Database;
use Cherry\Data\Ddl\SdlTag;

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

    public function applySdlTag(\Cherry\Data\Ddl\SdlTag $node) {
        if ($node->getName() == 'table') {
            if ($node->getValue() != $this->name) {
                \App::app()->warn("Not applying SDL node to table as names differ: ".$node->getValue());
                return false;
            }
            $cur = $this->getSdlTag();
            foreach($node->getChildren('column') as $col) {
                $curcol = $cur->getChild('column',$col->getValue());
                // Check if the node 
                if (($curcol == null) || ($curcol != $col)) {
                    echo "Column ".$col->getName()." needs creating/applying!\n";
                }
            }
        }
    }
    
    public function getSdlTag() {
        $table = $this;

        // Print out the table nodes
        $sdl = new SdlTag("table",$table->name);
        $sdl->setComment("Table {$table->name} from database {$table->database->name}");
        // Convert the columns to SDL
        foreach($table->getColumns() as $co) {
            $col = new SdlTag("column",$co->name, ['type'=>$co->type ]);
            if (!empty($co->default)) $col->setAttribute('default',$co->default);
            if ($co->auto) $col->setAttribute('auto',1);
            $col->setAttribute('null',$co->null);
            if (!empty($co->comment)) $col->setAttribute('comment',$co->comment);
            $sdl->addChild($col);
        }
        // Convert the indexes to SDL
        $idx = new SdlTag("indexes");
        foreach($table->getIndexes() as $ix) {
            $cols = $ix->columns;
            $ii = new SdlTag($ix->type,$ix->name);
            $ii->setComment("Index {$ix->name}");
            $ii->addChild(new SdlTag(NULL,$cols));
            $idx->addChild($ii);
        }
        $sdl->addChild($idx);
        return $sdl;
        
    }

    private function getMeta() {
        try {
            $rows = $this->db->query("SHOW FULL COLUMNS FROM ".$this->table);
            $stat = $this->db->query("SHOW TABLE STATUS LIKE '".$this->table."'");
            $this->tablemeta = $stat->fetch();
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
