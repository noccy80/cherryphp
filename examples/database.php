<?php

require getenv("CHERRY_LIB")."/lib/bootstrap.php";

// Import databaseconnection class for easy access
use Cherry\Database\DatabaseConnection;
use Cherry\Data\Ddl\SdlNode;
use Cherry\Crypto\KeyStore;

// Register a connection
DatabaseConnection::register("default", "mysql://user:password@localhost/database",
                             [ "priority"=>10, "role"=>"RW" ]);
DatabaseConnection::register("admin", "mysql://root@localhost",
                             [ "keystore"=>"db.access.adminpass" ]);

// Or you can store the credentials with restricted access
KeyStore::getInstance()->addCredentials(
    "db.access.adminpass",
    "rootpassword",
    [
        // The allow statement defines that this key can be read by the classes
        // listed.
        'allow' => [
            "Cherry\Database\DatabaseConnection"
        ]
    ]
);

// Get the connection instance from the pool
$conn = DatabaseConnection::getInstance("default");

// Create a table
$tbl = $conn->tables['test'];
// Add some columns to the table
$tbl->addColumn('id', 'int', [ 'key'=>'primary', 'auto'=>true ]);
$tbl->addColumn('text','varchar(250)', [ 'null'=>false ]);
// Apply the columns that don't exist yet, i.e. create the table
$tbl->apply();
