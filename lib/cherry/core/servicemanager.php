<?php

namespace Cherry\Core;

/**
 * ServiceManager links in with ObjectManager, providing access to the services
 * via a path such as:
 *     "local:/services/<svcid>"
 */
class ServiceManager implements IObjectManagerInterface {

    use \Cherry\Traits\TStaticDebug;

    private static $servicedb;
    private static $instances;
    private static function getDbInstance() {
        if (!self::$servicedb) {
            self::$servicedb = DatabaseConnection::get("sqlite:data/servicedb.sq3");
        }
        return self::$servicedb;
    }
    public static function addServiceInstance(ServiceInstance $i, $id=null) {
        if (!$id) {
            if (empty($i->serviceid))
                throw new \Exception("The service need to be given an id, either through Class::\$serviceid or the \$id parameter.");
            $id = $i->serviceid;
        }
        if (!$i->serviceid)
            $i->serviceid = $id;
        $i->on("service.starting", "\\Cherry\\Core\\ServiceManager::onServiceStarting");
        $i->on("service.stopping", "\\Cherry\\Core\\ServiceManager::onServiceStopping");
        self::$instances[$id] = $i;
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
}

