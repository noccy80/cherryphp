<?php

namespace Cherry\Crypto\OpenSSL;

class KeyPair {

    protected $privkey = null;
    protected $pubkey = null;

    public function __construct($privkey=null,$pubkey=null) {
        if (($privkey) && ($pubkey)) {
            // Load
        } else 
            $this->pkey = openssl_pkey_new();
        }
    }

}

