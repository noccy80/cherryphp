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
class KeyStoreFile {

    private $keys = [];
    private $key = null;

    public function __construct($store, $key=null, $crypto='tripledes') {
        if (!$key) $key = 0xDEADBEEF;
        if (file_exists($store)) {
            debug("KeyStore: Opening %s", $store);
            $buf = file_get_contents($store);
            $buf = Crypto::tripledes($key)->decrypt($buf);
            $buf = gzuncompress($buf);
            $this->keys = unserialize($buf);
        }

        $this->store = $store;
        $this->key = $key;
    }

    public function save() {
        $buf = serialize($this->keys);
        $buf = gzcompress($buf,9);
        $buf = Crypto::tripledes($this->key)->encrypt($buf);
        file_put_contents($this->store,$buf);
    }

    /**
     *
     * @param string $key The key to add
     */
    public function setCredentials($key,$value,array $allow=null) {
        $cfgallow = (array)App::config()->get("keystore.overrides.".$key);
        $this->keys[$key] = (object)[
            'value' => $value,
            'rules' => array_unique(array_merge($allow,$cfgallow))
        ];
    }


}
