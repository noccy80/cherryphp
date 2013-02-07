<?php

namespace CherryUtil\commands;
use cherryutil\Command;
use cherryutil\CommandBundle;
use cherryutil\CommandList;
use Cherry\Database\DatabaseConnection;
use Cherry\Data\Ddl\SdlTag;
use App;

class DatabaseCommands extends CommandBundle {
    
    function getCommands() {
        return array(
            new Command('dbtosdl','',
                    'Convert database table to SDL ddl format', 
                    array($this,'dbtosdl')),
            new Command('sdltodb','',
                    'Convert SDL ddl definition to database table', 
                    array($this,'sdltodb'))
        );
    }
    
    public function sdltodb() {
    
    }
    
    public function dbtosdl() {
        $args = func_get_args();
        $opts = $this->parseOpts($args,array(
            'database' => 'database:',
            'table' => 'table:',
            'with-data' => '+withdata'
        ));
        var_dump($opts);
        // Get the connection instance
        if (empty($opts['database'])) {
            $conn = DatabaseConnection::getInstance("default");
        } else {
            $conn = DatabaseConnection::getInstance($opts['database']);
        }
        // Enumerate all the tables
        foreach($conn->tables->getAll() as $table) {
            if ((empty($opts['table'])) || ($table->name == $opts['table'])) {
                // Print out the table nodes
                $sdl = new SdlTag("table",$table->name);
                $sdl->setComment("Table {$table->name} from database {$table->database->name}");
                // Convert the columns to SDL
                foreach($table->getColumns() as $co) {
                    $col = new SdlTag("column",$co->name, ['type'=>$co->type ]);
                    if ($co->default !== NULL) $col->setAttribute('default',$co->default);
                    if ($co->auto) $col->setAttribute('auto',1);
                    $col->setAttribute('null',$co->null);
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
                $rs = $conn->query("SELECT * FROM {$table->name}");
                $data = new SdlTag("data");
                foreach($rs as $row) {
                    $rdata = new SdlTag("row");
                    foreach($row as $k=>$v) {
                        if (!is_integer($k))
                            $rdata->addChild(new SdlTag($k,$v));
                    }
                    $data->addChild($rdata);
                }
                $sdl->addChild($data);
                echo $sdl->encode()."\n";
            }
        }
    
    }
}

CommandList::getInstance()->registerBundle(new DatabaseCommands());
