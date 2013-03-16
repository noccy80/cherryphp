<?php

namespace Cherry\Core;

use Cherry\Database\DatabaseConnection;

define("SM_TABLE_SERVICES",
<<<EOD
table "services" {
    column "id" type="char:256"
    column "uuid" type="char:36"
    column "serviceclass" type="char:256";
    column "baseinstance" type="blob";
}
EOD
);

define("SM_TABLE_INSTANCES",
<<<EOD
table "instances" {
    column "id" type="char:256"
    column "uuid" type="char:36"
    column "serviceclass" type="char:256";
    column "status" type="int"
}
EOD
);

/**
 * ServiceManager links in with ObjectManager, providing access to the services
 * via a path such as:
 *     "local:/services/<svcid>"
 */
class ServiceManager implements IObjectManagerInterface {

    use \Cherry\Traits\TStaticDebug;

    private static $servicedb;
    private static $instances = [];
    private static $sinstances = [];
    private static function getDbInstance() {
        if (!self::$servicedb) {
            DatabaseConnection::register("servicemanager", "sqlite://service.db");
            self::$servicedb = DatabaseConnection::getInstance("servicemanager");
        }
        static $initd;
        if (!$initd) {
            $sm = self::$servicedb->getSchemaManager();
            $tl = $sm->getTableList();
            if (!in_array("services", $tl))
                $sm->applyTableSdlString(SM_TABLE_SERVICES);
            if (!in_array("instances", $tl))
                $sm->applyTableSdlString(SM_TABLE_INSTANCES);
            $initd = true;
        }
        return self::$servicedb;
    }
    public static function addServiceInstance(ServiceInstance $i) {
        $id = $i->getServiceId();
        $i->on("service.starting", "\\Cherry\\Core\\ServiceManager::onServiceStarting");
        $i->on("service.stopping", "\\Cherry\\Core\\ServiceManager::onServiceStopping");
        $i->on("service.reloading", "\\Cherry\\Core\\ServiceManager::onServiceReloading");
        self::$instances[$id] = $i;
    }
    public static function registerService($i,$upgrade=false) {
        $db = self::getDbInstance();
        $id = $i->getServiceId();
        $cn = get_class($i);
        if (count($db->query("SELECT * FROM services WHERE id=%s",$id)->fetchAll())>0) {
            if (!$upgrade)
                throw new \Exception("Service with id {$id} already registered.");
            $db->query("DELETE FROM services WHERE id=%s", $id);
        }
        $db->query("INSERT INTO services (id,uuid,serviceclass,baseinstance) VALUES (%s,%s,%s,%s)",
                    $id, \Cherry\Crypto\UuidGenerator::uuid(), $cn, base64_encode(serialize($i))
                   );
        //self::$sinstances[$id] = serialize($i);
    }
    public static function queryServiceRecord($id) {
        $db = self::getDbInstance();
        return (count($db->query("SELECT * FROM services WHRE ")->fetchAll())>0);
    }
    public static function startAll() { }
    public function omiGetNodeList($path) {
        $db = self::getDbInstance();
    }
    public function omiGetObject($path) {
        if (array_key_exists($path->name,self::$instances)) {
            $rc = self::$instances[$path->name];
            self::debug("Returning class '%s' for '%s'", get_class($rc), $path->name);
            return $rc;
        }
        if (self::queryServiceRecord($path->name)) {
            $db = self::getDbInstance();
            $rec = $db->query("SELECT * FROM services where id=%s OR uuid=%s",
                        $path->name, $path->name)->fetch();
            $obj = unserialize(base64_decode($rec['baseinstance']));
            $obj->setPidFile("/tmp/{$rec['uuid']}.pid");
            $obj->setUuid($rec['uuid']);
            $obj->on("service.starting", "\\Cherry\\Core\\ServiceManager::onServiceStarting");
            $obj->on("service.stopping", "\\Cherry\\Core\\ServiceManager::onServiceStopping");
            $obj->on("service.reloading", "\\Cherry\\Core\\ServiceManager::onServiceReloading");
            return $obj;
        }
        self::debug("No such class registered: '%s'", $path->name);
        return null;
    }
    public static function register() {
        ObjectManager::registerObjectRoot("/services/", new ServiceManager());
    }
    public static function onServiceStarting($event) {
        $id = $event->data[0];
        self::debug("The service {$id} is starting");
    }
    public static function onServiceStopping($event) {
        $id = $event->data[0];
        self::debug("The service {$id} is stopping");
    }
    public static function onServiceReloading($event) {
        $id = $event->data[0];
        self::debug("The service {$id} is reloading");
    }
}

