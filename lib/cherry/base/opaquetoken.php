<?php

namespace Cherry\Base;

use ArrayObject;
use Cherry\Crypto\Algorithm as Crypto;
use Cherry\Crypto\KeyStore;

class OpaqueToken extends ArrayObject {

    private $salt;

    public function __construct() {
        $this->salt = rand(0,65535);
        $this->crypto = $crypto;
        parent::__construct();
    }

    public function unfreeze($token) {
        $key = KeyStore::query('opaquetoken.key');
        $data = base64_decode($token);
        $data = Crypto::tripledes($key)->decrypt($data);
        $data = unserialize($data);
        $this->exchangeArray($data);
    }

    public function freeze() {
        $key = KeyStore::query('opaquetoken.key');
        $data = serialize($this->getArrayCopy());
        $crypt = Crypto::tripledes($key)->encrypt($data);
        return base64_encode($crypt);
    }

}

/**
 * KeyStore; use the cookie to set access rights.
 */
$cookie = KeyStore::set("opaquetoken.key", "f02nfoDer2##fC;.Rfk");
KeyStore::allow($cookie, "opaquetoken.key", [ "Cherry\\Base\\OpaqueToken" ]);
