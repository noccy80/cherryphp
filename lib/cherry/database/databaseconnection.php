<?php

namespace Cherry\Database;

use PDO;

class DatabaseConnection {

    const POOL_DEFAULT = 'default';

    private static $dbpool = [];
    private static $config = [];
    private $sm = null;
    private $conn = null;
    private $database = null;

    public function __construct($uri, $opts=array()) {

        if (!$uri) throw new \UnexpectedValueException("DatabaseConnection::__construct() expects an URI");

        $this->sm = new SchemaManager($this);
        $ci = parse_url($uri);
        if (strpos($uri,"://")===false) {
            // Connectionstring is in PDO format
        } else {
            $type=      $ci['scheme'];
            if ($type == 'mysql') {
                $username = !empty($ci['user'])?$ci['user']:get_current_user();
                $password = !empty($ci['pass'])?$ci['pass']:null;
                $host =     !empty($ci['host'])?$ci['host']:'localhost';
                $database = !empty($ci['path'])?trim($ci['path'],"/"):null;
                $dsn = "mysql:host={$host};";
                if ((!empty($ci['port'])) && ($ci['port'] != 3306)) {
                    $port = $ci['port'];
                    $dsn.= "port={$port}";
                }
                $dsn.= "dbname={$database}";
                if (!$password)
                    $password = $this->getKeystorePassword($type,$username,$host,$database);
            } elseif ($type == 'sqlite') {
                $database = !empty($ci['host'])?$ci['host']:null;
                $database.= !empty($ci['path'])?$ci['path']:null;
                \debug("SQLite3: Using database {$database}");
                $dsn = "sqlite:{$database}";
                $username = null;
                $password = null;
            }
            $options = [];
            $this->database = $database;
            // Connectionstring is in URL format
        }
        // Scrolling cursor not supported with MySQL nor SQLite so warn on these


        // Temporarily change the PHP exception handler while we . . .
        set_exception_handler(array(__CLASS__, 'exception_handler'));

        // . . . create a PDO object
        $this->conn = new PDO($dsn, $username, $password, $options);

        // Change the exception handler back to whatever it was before
        restore_exception_handler();
    }
    
    public static function getKeystorePassword($type,$username,$host,$database) {
        // Try to get from keystore
        $ks = \Cherry\Crypto\KeyStore::getInstance();
        if ($curi) {
            try {
                $curi = "{$type}://{$username}@{$host}/{$database}";
                $password = $ks->queryCredentials($curi);
            } catch (Exception $e) { \debug("Unable to access credentials for connection {$curi}"); }
        }
        if (!$password) try {
            $curi = "{$type}://{$username}@{$host}";
            $password = $ks->queryCredentials($curi);
        } catch (Exception $e) { \debug("Unable to access credentials for connection {$curi}"); }
        return $password;
    }

    public static function register($pool,$conn) {
        self::$config[$pool] = $conn;
    }

    static function getInstance($pool=null) {
        if (!$pool) $pool = self::POOL_DEFAULT;
        if (!array_key_exists($pool,self::$dbpool)) {
            if (array_key_exists($pool,self::$config)) {
                self::$dbpool[$pool] = new self(self::$config[$pool]);
            } else {
                if (strpos($pool,"://")!==false) {
                    self::$dbpool[$pool] = new self($pool);
                } else {
                    throw new \UnexpectedValueException("Unable to connect to pool {$pool}");
                }
            }
        }
        return self::$dbpool[$pool];
    }

    public function prepare($statement) {

    }

    public function escape($sql) {
        $args = func_get_args();
        $argo = $args;
        $argcount = func_num_args();
        for($n = 1; $n < $argcount; $n++) {
            $value = $args[$n];
            if (!is_numeric($value)) {
                $argo[$n] = "'".str_replace("'","\'",$value)."'";
            }
        }
        $esql = call_user_func_array('sprintf',$argo);
        \App::app()->debug("DB:Escape: %s", $esql);
        return $esql;
    }

    public function query($sql) {
        $args = func_get_args();
        $argo = $args;
        $argcount = func_num_args();
        for($n = 1; $n < $argcount; $n++) {
            $value = $args[$n];
            if (!is_numeric($value)) {
                $argo[$n] = "'".str_replace("'","\'",$value)."'";
            }
        }
        $esql = call_user_func_array('sprintf',$argo);
        \debug("DB:Query: %s", $esql);
        return $this->conn->query($esql); // fetchmode?
    }

    public function pdo() {
        return $this->conn;
    }

    public function execute($sql) {
        $args = func_get_args();
        $argo = $args;
        $argcount = func_num_args();
        for($n = 1; $n < $argcount; $n++) {
            $value = $args[$n];
            if (!is_numeric($value)) {
                $argo[$n] = "'".str_replace("'","\'",$value)."'";
            }
        }
        $esql = call_user_func_array('sprintf',$argo);
        \debug("DB:Exec: %s", $esql);
        return $this->conn->exec($esql); // fetchmode?
    }

    public static function exception_handler($exception) {
        // Output the exception details
        error_log("Uncaught exception: ". $exception->getMessage());
        die(1);
    }

    public function __get($key) {
        switch($key) {
            case 'tables':
                return $this->sm;
            case 'name':
                return $this->database;
            default:
                break;
        }
    }

}
