<?php

namespace CherryUtil\commands;
use cherryutil\Command;
use cherryutil\CommandBundle;
use cherryutil\CommandList;
use Cherry\Database\DatabaseConnection;
use Cherry\Data\Ddl\SdlNode;
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
            'table' => 'table:'
        ));
        // Get the connection instance
        $conn = DatabaseConnection::getInstance("default");
        // Enumerate all the tables
        foreach($conn->tables->getAll() as $table) {
            // Print out the table nodes
            $sdl = new SdlNode("table",$table->name);
            $sdl->setComment("Table {$table->name} from database {$table->database->name}");
            // Convert the columns to SDL
            foreach($table->getColumns() as $co) {
                $col = new SdlNode("column",$co->name, ['type'=>$co->type ]);
                if ($co->default !== NULL) $col->setAttribute('default',$co->default);
                if ($co->auto) $col->setAttribute('auto',1);
                $col->setAttribute('null',$co->null);
                $sdl->addChild($col);
            }
            // Convert the indexes to SDL
            $idx = new SdlNode("indexes");
            foreach($table->getIndexes() as $ix) {
                $cols = $ix->columns;
                $ii = new SdlNode($ix->type,$ix->name);
                $ii->setComment("Index {$ix->name}");
                $ii->addChild(new SdlNode(NULL,$cols));
                $idx->addChild($ii);
            }
            $sdl->addChild($idx);
            echo $sdl->encode()."\n";
        }
    
    }
}

CommandList::getInstance()->registerBundle(new DatabaseCommands());
