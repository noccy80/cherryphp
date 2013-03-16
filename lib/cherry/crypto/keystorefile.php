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
class KeyStoreFile {

    private $keys = [];
    private $key = null;
    private $lasterror = null;

    const ERR_KEYSTORE_ERROR = 0x01;

    public function __construct($store, $key=null, $crypto='tripledes') {
        if ($key == true) {
            $ca = \Cherry\Cli\Console::getAdapter();
            $key = $ca->readpass("Password for keystore ".basename($store).": ");
        }
        if (!$key) $key = 0xDEADBEEF;
        $this->key = $this->deriveKey($key);
        if (file_exists($store)) {
            debug("KeyStore: Opening %s", $store);
            $buf = file_get_contents($store);
            $buf = Crypto::tripledes($this->key)->decrypt($buf);
            $buf = gzuncompress($buf);
            if (!$buf) {
                $this->lasterror = self::ERR_KEYSTORE_ERROR;
                $this->keys = [];
            } else
                $this->keys = @unserialize($buf);
        }

        $this->store = $store;
    }

    public function derivekey($key) {
        return substr(sha1($key),0,20);
    }

    public function getError() {
        $err = $this->lasterror;
        $this->lasterror = null;
        return $err;
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
        if (array_key_exists($key,$this->keys)) {
            $this->keys[$key]->value = $value;
            $acl = array_unique(array_merge($allow,$this->keys[$key]->rules));
            $this->keys[$key]->rules = $acl;
        }
        $this->keys[$key] = (object)[
            'value' => $value,
            'rules' => array_unique($allow)
        ];
    }

    public function addAcl($key,$rule) {
        if (array_key_exists($key,$this->keys)) {
            $acl = array_unique(array_merge([$rule],$this->keys[$key]->rules));
            $this->keys[$key]->rules = $acl;
            return true;
        }
        return false;
    }

    public function getCredentials() {
        return array_keys($this->keys);
    }

    public function getAcl() {
        $ret = [];
        foreach($this->keys as $key=>$value) {
            $ret[$key] = $value->rules;
        }
        return $ret;
    }

}
