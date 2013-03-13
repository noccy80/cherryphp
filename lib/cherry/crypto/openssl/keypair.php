<?php

namespace Cherry\Crypto\OpenSSL;

class KeyPair {

    protected $pkey = null;
    private $defcfg = [
        "private_key_bits" => 2048
    ];
    private $keypass = null;

    public function __construct($bits_or_keyfile = null, $password = null) {
        $this->keypass = $password;
        if (is_int($bits_or_keyfile)) {
            $this->generateKey([ 'private_key_bits' => $bits_or_keyfile ]);
        } elseif ($bits_or_keyfile) {
            $this->loadKeyFromFile($bits_or_keyfile);
        }
    }
    
    public function getPassphrase() {
        return $this->keypass;
    }

    public function generateKey(array $cfg = null) {
        $cfg = array_merge($this->defcfg,(array)$cfg);
        $this->pkey = openssl_pkey_new($cfg);
    }
    
    public function loadKeyFromFile($filename,$password=null) {
        
    }
    
    public function getPrivateKey() {
        $key = null;
        try {
            if (!openssl_pkey_export($this->pkey, $key, $this->keypass))
                return null;
        } catch (\Exception $e) {
            $key = null;
        }
        return $key;
    }

    public function getPublicKey() {
        try {
            openssl_pkey_export($this->pkey, $privatekey, $this->keypass);
            $key = openssl_pkey_get_public($this->pkey);
        } catch (\Exception $e) {
            $key = null;
        }
        return $key;
    }
    
    public function getKey() {
        return $this->pkey;
    }

}

