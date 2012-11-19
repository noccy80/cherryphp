<?php

namespace Cherry\Db;
use Cherry\Db\DatabaseConnection;
use App;

class SQLQuery {

    private
            $hasdata = false;   ///< If true, evaluate as prepared query
    
    public function __construct($query,array $data=null) {
        if (($data) && (is_array($data)) {
            $this->data = $data;
        } elseif (!empty($data)) {
            
        
    }

/// Factory template functions ///

    public static function insert($into,$data,$extra=null) {
        
    }

}

class SQLRecordSet implements Iterator, ArrayAccess {

    public function __construct($queryhandle) {
    
    }

}

$q1 = new SQLQuery('INSERT INTO foo (bar,baz) VALUES (1,2)');
$q2 = SQLQuery::insert('foo',[[ 'bar'=>1, 'baz'=>2 ]]);
$db->execute($q1);
$db->execute($q2);

$q3 = new SQLQuery('INSERT INTO foo (bar, baz) VALUES ({string:bar},{string:baz})
