<?php

namespace Cherry\Database;

use PDO;
use Cherry\Base\PathResolver;
use Cherry\Data\Ddl\SdlTag;

class DatabaseConnection {

    const POOL_DEFAULT = 'default';

    private static $dbpool = [];
    private static $config = [];
    private static $connections = null;
    private $conn = null;
    private $database = null;

    public function __construct($uri, $opts=array()) {

        if (!$uri) throw new \UnexpectedValueException("DatabaseConnection::__construct() expects an URI");

        $ci = parse_url($uri);
        if (strpos($uri,"://")===false) {
            // Connectionstring is in PDO format
        } else {
            $type=      $ci['scheme'];
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
            $options = [];
            $this->database = $database;
            // Connectionstring is in URL format
        }
        // Scrolling cursor not supported with MySQL nor SQLite so warn on these
        if (!$password) {
            // Try to get from keystore
            $ks = \Cherry\Crypto\KeyStore::getInstance();
            if ($database) {
                try {
                    $curi = "{$type}://{$username}@{$host}/{$database}";
                    $password = $ks->queryCredentials($curi);
                } catch (Exception $e) { \debug("Unable to access credentials for connection {$curi}"); }
            }
            if (!$password) try {
                $curi = "{$type}://{$username}@{$host}";
                $password = $ks->queryCredentials($curi);
            } catch (Exception $e) { \debug("Unable to access credentials for connection {$curi}"); }
        }

        // Temporarily change the PHP exception handler while we . . .
        set_exception_handler(array(__CLASS__, 'exception_handler'));

        // . . . create a PDO object
        $this->conn = new PDO($dsn, $username, $password, $options);
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Change the exception handler back to whatever it was before
        restore_exception_handler();
    }

    public function getSqlAdapter() {
        return new SqlAdapters\MySqlAdapter($this);
    }

    public function getSchemaManager() {
        return new SchemaManager($this);
    }

    public static function register($pool,$conn) {
        \debug("Database: Registered connection '{$conn}' to pool {$pool}");
        self::$connections[$pool] = new SdlTag("connection",[ $pool,$conn ]);
    }

    static function getInstance($pool=null) {
        if (!$pool) $pool = self::POOL_DEFAULT;
        if (!self::$connections) {
            $cp = PathResolver::path("{APP}/config/database.sdl");
            if (file_exists($cp)) {
                $cfg = SdlTag::createFromFile($cp);
                foreach($cfg->spath("database/connection") as $connection) {
                    $cpool = $connection[0];
                    $curi = $connection[1];
                    if (empty(self::$connections[$cpool]))
                        self::$connections[$cpool] = [];
                    \debug("Database: Registered connection '{$curi}' to pool {$cpool}");
                    self::$connections[$cpool][] = $connection;
                }
            }
        }
        if (!array_key_exists($pool,self::$dbpool)) {
            if (array_key_exists($pool,self::$connections)) {
                self::$dbpool[$pool] = new self(self::$connections[$pool]);
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
        for($n = ($argcount>1)?1:0; $n < $argcount; $n++) {
            $value = $args[$n];
            if ($value === null) $argo[$n] = "NULL";
            if ($value === true) $argo[$n] = 1;
            if ($value === false) $argo[$n] = 0;
            if (!is_numeric($value)) {
                $argo[$n] = "'".str_replace("'","\'",$value)."'";
            }
        }
        if ($argcount>1) {
            $esql = call_user_func_array('sprintf',$argo);
        } else { $esql = $argo[0]; }
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
        \App::app()->debug("DB:Query: %s", $esql);
        return $this->conn->query($esql); // fetchmode?
    }

    public function pdo() {
        return $this->conn;
    }

    public function execute($sql,$varargs=null) {
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
        \App::app()->debug("DB:Exec: %s", $esql);
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
                return $this->getSchemaManager()->getTableManager();
            case 'name':
                return $this->database;
            default:
                break;
        }
    }

}
