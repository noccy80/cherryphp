<?php

namespace Cherry\Crypto;

use Cherry\Traits\SingletonAccess;
use App;

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

    /**
     *
     * @param array $rules The rules to check
     */
    private function checkAccess(array $rules) {
        $bt = \debug_backtrace();
        $bt = array_slice($bt,2,1);
        $bc = \array_map(function($v) { return (!empty($v['class'])?$v['class'].'::':'').$v['function']; }, $bt);
        foreach($bc as $func) {
            \Cherry\Debug("KeyStore: Checking {$func}");
            foreach($rules as $rule) {
                \Cherry\Debug(" .. {$rule}");
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
            'rules' => array_merge($allow,$cfgallow)
        ];
    }

    /**
     *
     * @param string $key The key to query
     */
    public function queryCredentials($key) {
        if (array_key_exists($key,$this->keys)) {
            if (!$this->checkAccess($this->keys[$key]->rules)) {
                throw new \Exception("Improper keystore access from non-allowed path for key {$key}");
            }
            return $this->keys[$key]->value;
        }
        return false;
    }
}
