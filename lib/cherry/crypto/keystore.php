<?php

namespace Cherry\Crypto;

use Cherry\Traits\SingletonAccess;
use Cherry\Crypto\Algorithm as Crypto;
use App;
use debug;

/**
 * @brief Protected key store.
 *
 * This is a key store with limited access. When credentials are added, the
 * allowed code paths are provided and from that point any read from an
 * unallowed code path will return false.
 *
 * This is intended to protect passwords and tokens in a shared environment in
 * such a way that they can be assigned during the configuration phase and then
 * only accessed by the specific classes and functions provided. Tokens are not
 * encrypted.
 *
 *
 */
class KeyStore {

    use SingletonAccess;
    private $keys = [];

    public function __construct($store=null, $key=null) {
        if (!$store) $store = \Cherry\Base\PathResolver::getInstance()->getPath("{DATA}/default.cks");
        if (file_exists($store)) {
            $this->attachFile($store,$key);
        }
    }

    public function attachFile($store, $key=null) {
        if (file_exists($store)) {
            debug("KeyStore: Opening %s", $store);
            $buf = file_get_contents($store);
            $buf = Crypto::tripledes($key)->decrypt($buf);
            if ($buf) {
                $buf = gzuncompress($buf);
                $keys = unserialize($buf);
                $this->keys = array_merge($this->keys,(array)$keys);
                return true;
            }
        }
        return false;
    }

    /**
     *
     * @param array $rules The rules to check
     */
    private function checkAccess(array $rules) {
        $bt = \debug_backtrace();
        $bt = array_slice($bt,2,1);
        $bc = \array_map(function($v) { return (!empty($v['class'])?$v['class'].'::':'').$v['function']; }, $bt);
        foreach($bc as $func) {
            debug("KeyStore: Checking {$func}");
            foreach($rules as $rule) {
                //debug(" - {$rule}");
                if (\fnmatch($rule,$func,\FNM_NOESCAPE)) return true;
            }
        }
        return false;
    }

    /**
     *
     * @param string $key The key to add
     */
    public function addCredentials($key,$value,array $allow=null) {
        $cfgallow = (array)App::config()->get("keystore.overrides.".$key);
        $this->keys[$key] = (object)[
            'value' => $value,
            'rules' => array_unique(array_merge($allow,$cfgallow))
        ];
    }

    /**
     *
     * @param string $key The key to query
     */
    public function queryCredentials($key) {
        if (array_key_exists($key,$this->keys)) {
            if (!$this->checkAccess($this->keys[$key]->rules)) {
                \debug("KeyStore: Access denied for {$key}");
                throw new \Exception("Improper keystore access from non-allowed path for key {$key}");
            }
            \debug("KeyStore: Query ok for {$key}");
            return $this->keys[$key]->value;
        }
        \debug("KeyStore: No match for {$key}");
        return false;
    }

}
