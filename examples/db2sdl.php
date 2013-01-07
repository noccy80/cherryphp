#!/usr/bin/php
<?php

require getenv("CHERRY_LIB")."/lib/bootstrap.php";

use Cherry\Database\DatabaseConnection;
use Cherry\Data\Ddl\SdlNode;

// Register a connection
DatabaseConnection::register("default", "mysql://noccy@localhost/nojesfabriken", 10, "R");
// Or you can store the credentials with restricted access
/*
KeyStore::addCredentials("db.default","mysql://noccy@localhost/nojesfabriken", [
    'allow'=>"Cherry\Database\DatabaseConnection"
]);
*/


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
