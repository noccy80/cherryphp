<?php

namespace Cherry\Crypto;

class OpenSSL {

    

}

class Pkey {

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

class CSR {

    public function __construct() {
    
    }

    public function generate() {
        $this->csr = openssl_csr_new($this->dn, $this->pkey);
    }
    
    public function sign($cacert=null,$days=365) {
        if (empty($this->csr))
            user_error("CSR not generate()'d");
        $this->signed = openssl_csr_sign($this->csr,$cacert,$this->privkey,$days);
    }
    
    public function exportCsr($file) {
        $out = null;
        openssl_csr_export($this->csr,$out);
        file_put_contents($file,$out);
    }

    public function exportPublicKey($file) {
        $out = null;
        openssl_x509_export($this->csr,$out);
        file_put_contents($file,$out);
    }
    
    public function exportPrivateKey($file) {
        $out = null;
        openssl_pkey_export($this->csr,$out,$);
        file_put_contents($file,$out);
    }

}
