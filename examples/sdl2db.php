#!/usr/bin/php
<?php

require getenv("CHERRY_LIB")."/lib/bootstrap.php";

use Cherry\Database\DatabaseConnection;
use Cherry\Data\Ddl\SdlTag;

$node = new SdlTag("root");
$node->decode(file_get_contents("db.sdl"));

$tables = $node->getChildrenByName("table");
foreach($tables as $table) {
    echo "Table ".$table[0]."\n";
    $cols = $table->getChildrenByName("column");
    foreach($cols as $col) {
        $type = $col->getAttribute("type");
        echo " - {$col[0]} ({$type})\n";
        foreach($col->getAllAttributes() as $k=>$v) {
            echo "    {$k} = {$v}\n";
        }
    }
    //var_dump($table);
}
//echo $node->encode();
