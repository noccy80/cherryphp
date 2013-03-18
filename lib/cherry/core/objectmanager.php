<?php

namespace Cherry\Core;

/**
 * ObjectManager::getObject($uri)
 *  - Returns the registered (or mapped) instance
 *  - Returns null on paths (ending with "/") and non-existing uris.
 * ObjectManager::getObjectRecord($uri)
 *  - Returns either a ObjectList or an ObjectRecord.
 *  - The list allows iteration over the child nodes
 *  - The record gives access to the instance and its properties.
 * ObjectManager::registerObject($uri,$object)
 *  - Adds a new object
 *  - Parents are automatically created
 * ObjectManager::registerTree($uri,$tree)
 *  - Adds a new tree to the node tree.
 *  - Parents are automatically created
 */

class ObjectRecord extends ObjectUri {

    private $uri;
    private $obj;

    function __construct($uri,$obj) {
        $this->uri = $uri;
        $this->obj = $obj;
        parent::__construct($uri);
    }

    public function getObjectUri() {
        return $this->uri;
    }

    public function getAllProperties() {
        $props = ObjectManager::getObjectProperties();
        return $props;
    }

    public function getProperty($propname) {
        $props = ObjectManager::getObjectProperties($this->uri);
        if (array_key_exists($propname,$props))
            return $props[$propname];
        return null;
    }

    public function setProperty($propname,$value) {

    }

    public function getInstance() {
        return $this->obj;
    }

    public function getType() {
        return get_class($this->obj);
    }

}


/*
 * class ObjectUri
 */

class ObjectUri {
    public $host = "local";
    public $path = null;
    public $object = null;
    public $index = null;
    public function isLocal() {
        return ($this->host == "local");
    }
    public function __construct($uri) {

        $path = $uri;

        if (strpos($path,":")!==false) {
            list($host,$path) = explode(":",$path);
        } else {
            $host = "local";
        }
        $this->host = $host;

        if (strpos($path,"#")!==false) {
            list($path,$index) = explode("#",$path,2);
        } else {
            $index = null;
        }
        $this->index = $index;

        $pseg = explode("/",$path);
        if (count($pseg)>1) {
            $name = array_pop($pseg);
        } else {
            $name = null;
        }
        $this->name = $name;

        $this->path = join("/",$pseg)."/";
    }
    public function __toString() {
        if (!$this->host) $this->host = "local";
        $idx = !empty($this->index)?"#{$this->index}":"";
        return "{$this->host}:{$this->path}{$this->name}{$idx}";
    }
}

class ObjectVfsList implements \ArrayAccess, \IteratorAggregate {
    use \Cherry\Traits\TDebug;
    private $nodes = [];
    function __construct() {

    }
    function offsetGet($key) {
        $key = trim($key,"/");
        if (strpos($key,"/")!==false) {
            // Break up the path, work on the top part and pass the rest on.
            $keys = explode("/",$key);
            $tk = array_shift($keys);
            $key = join("/",$keys);
            return $this->nodes[$tk][$key];
        } else {
            return $this->nodes[$key];
        }
    }
    function offsetSet($key,$value) {
        $key = trim($key,"/");
        if (strpos($key,"/")!==false) {
            // Break up the path, work on the top part and pass the rest on.
            $keys = explode("/",$key);
            $tk = array_shift($keys);
            if (!array_key_exists($tk,$this->nodes)) {
                $vfs = new ObjectVfsList();
                $this->nodes[$tk] = $vfs;
            }
            $key = join("/",$keys);
            $this->nodes[$tk][$key] = $value;
        } else {
            if ($value instanceof ObjectVfsInterface) {
                $this->nodes[$key] = $value;
            } else {
                $this->nodes[$key] = new ObjectVfsNode($value);
            }
        }
    }
    function offsetUnset($key) {
        $key = trim($key,"/");

    }
    function offsetExists($key) {
        $key = trim($key,"/");
        if (strpos(trim($key,"/"),"/")!==false) {
            // Break up the path, work on the top part and pass the rest on.
            $keys = explode("/",$key);
            $tk = array_shift($keys);
            $key = join("/",$keys);
            if (!array_key_exists($tk,$this->nodes))
                return false;
        }
        return array_key_exist($key,$this->nodes[$tk]);
    }
    function getIterator() {
        return new ArrayIterator($this->nodes);
    }
}

class ObjectVfsNode {
    public $object;
    function __construct($object) {
        $this->object = $object;
    }
}

class ObjectVfsInterface {
    function __construct(IObjectManagerInterface $i) {

    }
}


class ObjectManager {
    use \Cherry\Traits\TStaticDebug;

    const PROP_CAN_SERIALIZE = "object.capability.serialize";

    private static $_ovfs = null;
    private static $_omstorage = [];
    public static function registerObjectRoot($path, IObjectManagerInterface $root) {
        if (!self::$_ovfs) self::$_ovfs = new ObjectVfsList();
        self::debug("Registered object root '{$path}' for %s", get_class($root));
        if (!($path instanceof ObjectUri)) $path = new ObjectUri($path);
        if ($path->host != "local")
            return;
        if ($path->name)
            throw new \UnexpectedValueException("path for registerObjectRoot must end with a /");
        self::$_ovfs[$path->path.$path->name] = new ObjectVfsInterface($root);
        self::$_omstorage[$path->path] = $root;
    }
    public static function registerObject($path, $object, array $prop = null) {
        if (!self::$_ovfs) self::$_ovfs = new ObjectVfsList();
        if (!($path instanceof ObjectUri)) $path = new ObjectUri($path);
        self::debug("Registered object '{$path}' for %s", get_class($object));
        if ($path->host != "local")
            return;
        if (!$path->name)
            throw new \UnexpectedValueException("path for registerObject must contain an object name");
        self::checkPath($path->path);
        self::$_ovfs[$path->path.$path->name] = $object;
        self::$_omstorage[$path->path][$path->name] = $object;
    }
    private static function checkPath($path) {
        if (!self::$_ovfs) self::$_ovfs = new ObjectVfsList();
        $seg = explode("/",$path);
        for($n = 2; $n < count($path); $n++) {
            $spath = join("/",array_slice($seg,0,$n))."/";
            if (!array_key_exists($spath,self::$_omstorage)) {
                self::debug("Initializing object path '{$spath}'");
                self::$_omstorage[$spath] = [];
            }
        }
    }
    public static function enumPath($path) {
        if (!($path instanceof ObjectUri)) $path = new ObjectUri($path);
        $m = [];
        $ps = $path->path;
        if (array_key_exists($ps,self::$_omstorage)) {
            if (self::$_omstorage[$ps] instanceof IObjectManagerInterface) {
                return self::$_omstorage[$ps]->omiGetObjectList();
            }
        }
        foreach(self::$_omstorage as $k=>$v) {
            if (substr($k,0,strlen($ps))==$ps) {
                $np = substr(rtrim($k,"/"),strlen($ps));
                if ((strlen($np)>0) && (strpos($np,"=")===false))
                    $m[$k] = $v;
            }
        }
        return $m;
    }
    public static function getObject($path) {
        if (!($path instanceof ObjectUri)) $path = new ObjectUri($path);
        if (!$path->isLocal()) return self::doRpcOp($path,"object.get");
        self::debug("Looking for object '{$path}' in storage");
        if (!$path->name) {
            throw new \UnexpectedValueException("path for getObject must contain an object name");
        }
        if (!array_key_exists($path->path,self::$_omstorage)) {
            self::debug("No matching object found for '{$path}'");
            return false;
        }
        if (is_array(self::$_omstorage[$path->path])) {
            if (!array_key_exists($path->name,self::$_omstorage[$path->path])) {
                self::debug("No matching object found for '{$path->name}' in '{$path->path}'");
                return false;
            }
            return self::$_omstorage[$path->path][$path->name];
        } else {
            return self::$_omstorage[$path->path]->omiGetObject($path);
        }
    }
    public static function getObjectRecord($path) {
        if (!($path instanceof ObjectUri)) $path = new ObjectUri($path);
        if (!$path->isLocal()) return self::doRpcOp($path,"object.get");
        self::debug("Looking for object '{$path}' in storage");
        if (!array_key_exists($path->path,self::$_omstorage)) {
            self::debug("No matching object found for '{$path}'");
            return false;
        }
        if (is_array(self::$_omstorage[$path->path])) {
            if (empty($path->name)) return self::$_omstorage[$path->path];
            if (!array_key_exists($path->name,self::$_omstorage[$path->path])) {
                self::debug("No matching object found for '{$path->name}' in '{$path->path}'");
                return false;
            }
            return self::$_omstorage[$path->path][$path->name];
        } else {
            $obj = self::$_omstorage[$path->path]->omiGetObject($path);
            $rec = new ObjectRecord($path,$obj);
            return $rec;
        }
    }
    public static function getObjectProperties($path) {
        // if (!self::$_ovfs) return null;
        if (!($path instanceof ObjectUri)) $path = new ObjectUri($path);
        if (!$path->isLocal()) return self::doRpcOp($path,"object.getprops");
        self::debug("Looking for object '{$path}' in storage");
        if (!$path->name) {
            throw new \UnexpectedValueException("path for getObject must contain an object name");
        }
        if (!array_key_exists($path->path,self::$_omstorage)) {
            self::debug("No matching object found for '{$path}'");
            return false;
        }
        if (is_array(self::$_omstorage[$path->path])) {
            if (!array_key_exists($path->name,self::$_omstorage[$path->path])) {
                self::debug("No matching object found for '{$path->name}' in '{$path->path}'");
                return false;
            }
            return self::$_omstorage[$path->path][$path->name];
        } else {
            return self::$_omstorage[$path->path]->omiGetObjectProperties($path);
        }
    }
}

