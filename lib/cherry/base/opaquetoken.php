<?php

namespace Cherry\Base;

use ArrayObject;
use Cherry\Crypto\Algorithm as Crypto;
use Cherry\Crypto\KeyStore;

class OpaqueToken extends ArrayObject {

    private $salt;

    public function __construct($frozen=null) {
        if ($frozen) {
            parent::__construct();
            $crypto = 'tripledes';
            $this->crypto = $crypto;
            $this->unfreeze($frozen);
        } else {
            $this->salt = rand(0,65535);
            $crypto = 'tripledes';
            $this->crypto = $crypto;
            parent::__construct();
        }
    }

    public function unfreeze($token) {
        $key = KeyStore::getInstance()->queryCredentials('opaquetoken.key');
        $data = base64_decode($token);
        $data = Crypto::tripledes($key)->decrypt($data);
        $data = gzuncompress($data);
        $data = unserialize($data);
        $this->exchangeArray($data);
    }

    public function freeze() {
        $key = KeyStore::getInstance()->queryCredentials('opaquetoken.key');
        $data = serialize($this->getArrayCopy());
        $data = gzcompress($data);
        $crypt = Crypto::tripledes($key)->encrypt($data);
        return base64_encode($crypt);
    }

}

