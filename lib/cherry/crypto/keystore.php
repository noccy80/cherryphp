<?php

namespace Cherry\Crypto;

use Cherry\Traits\TSingletonAccess;
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

    use \Cherry\Traits\TDebug;
    use TSingletonAccess;
    private $keys = [];

    public function __construct($store=null, $key=null) {
        if (!$store) $store = \Cherry\Base\PathResolver::getInstance()->getPath("{DATA}/default.cks");
        if (file_exists($store)) {
            $this->attachFile($store,$key);
        }
    }

    public function attachFile($store, $key=null) {
        if (file_exists($store)) {
            $this->debug("Opening %s", $store);
            $buf = file_get_contents($store);
            if (!$key) $key = 0xDEADBEEF;
            $key = $this->derivekey($key);
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

    public function derivekey($key) {
        return substr(sha1($key),0,20);
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
            $this->debug("Checking {$func}");
            foreach($rules as $rule) {
                $this->debug("Matching {$rule}");
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
                $this->debug("Access denied for {$key}");
                throw new \Exception("Improper keystore access from non-allowed path for key {$key}");
            }
            $this->debug("Query ok for {$key}");
            return $this->keys[$key]->value;
        }
        $this->debug("No match for {$key}");
        return false;
    }

}
