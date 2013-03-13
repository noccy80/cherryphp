<?php

namespace Cherry\Crypto\OpenSSL;

class CSR {

    private $dn = [];
    private $pkey = null;
    private $pkeypass = null;
    
    public function __construct(array $dn = null) {
        \debug("OpenSSL CSR: Creating new CSR");
        if ($dn) $this->setCertificateDn($dn);
    }
    
    public function setCertificateDn(array $dn) {
        $this->dn = $dn;
        $out = []; foreach($dn as $k=>$v) $out[] = "{$k}={$v}"; $out = join(", ",$out);
        \debug("OpenSSL CSR: Setting up Certificate DN: {$out}");
    }
    public function setPrivateKey(KeyPair $pk) {
        \debug("OpenSSL CSR: Assigning private key");
        $this->pkey = $pk->getKey();
        $this->pkeypass = $pk->getPassphrase();
    }
    
    public function setSerial($serial) {
        \debug("OpenSSL CSR: Setting certificate serial to {$serial}");
        $this->serial = $serial;
    }

    public function signCertificate($cacert=null,$days=365) {
        \debug("OpenSSL CSR: Signing certificate (cacert=%s, days=%d)", ($cacert)?'yes':'no', $days);
        $this->csr = openssl_csr_new($this->dn, $this->pkey);
        // Default serial to 0
        if ($this->serial)
            $serial = $this->serial;
        else $serial = 0;
            $this->signed = openssl_csr_sign($this->csr,$cacert,$this->pkey,$days, [], $serial);
        // Return true on success
        return true;
    }
    
    public function exportCertificatePem($file) {
        \debug("OpenSSL CSR: Exporting certificate as PEM: {$file}");
        $pem = [];
        openssl_x509_export($this->signed, $pem[0]);
        openssl_pkey_export($this->pkey, $pem[1], $this->pkeypass);
        $pem = implode($pem);
        
        file_put_contents($file, $pem);        
    }
    public function exportCertificatePkcs12($file) {
        \debug("OpenSSL CSR: Exporting certificate as PKCS12: {$file}");
        $pkcs = null;
        openssl_pkcs12_export($this->signed,$pkcs,$this->pkey,$this->pkeypass,[ "friendly_names"=>true ]);
        file_put_contents($file, $pkcs);        
    }
    
    public function exportCsr($file) {
        $out = null;
        openssl_csr_export($this->csr,$out);
        file_put_contents($file,$out);
    }

}
