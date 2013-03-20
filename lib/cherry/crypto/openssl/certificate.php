<?php

namespace Cherry\Crypto\OpenSSL;

class Certificate {

    use \Cherry\Traits\TDebug;

    private $certfile = null;
    private $certpass = null;
    private $certmeta = [];
    private $certstr = null;

    public function __construct($file,$password=null) {
        if (!file_exists($file))
            throw new CertificateException("Certificate not found: {$file}");
        $this->certfile = realpath($file);
        $this->debug("Reading certificate '{$this->certfile}'");
        $this->certmeta = $this->getCertificateInfo();
        $this->certpass = $password;
    }

    public function getCertificateInfo() {
        if (!$this->certstr) $this->certstr = file_get_contents($this->certfile);
        $info = openssl_x509_parse($this->certstr);
        return $info;
    }

    public function isSelfSigned() {
        if ($this->certmeta["subject"]==$this->certmeta["issuer"])
            return true;
    }

    public function getValidity() {
        $from = date(\DateTime::RFC822, $this->certmeta["validFrom_time_t"]);
        $to   = date(\DateTime::RFC822, $this->certmeta["validTo_time_t"]);
        return [ $from, $to ];
    }

    public function getCertificateText() {
        $info = $this->getCertificateInfo();
        return $this->_getCertText($info);
    }

    private function _getCertText($info,$indent=0) {
        $ind = str_repeat(" ",$indent);
        $str = "";
        foreach($info as $name=>$value) {
            if (is_array($value)) {
                $str.= $ind.$name.":\n";
                $str.= $this->_getCertText($value,$indent+4);
            } else {
                $str.= $ind.$name.": ".$value."\n";
            }
        }
        return $str;
    }

    public function getStreamContext() {
        $this->debug("Certificate: Generating stream context for certificate '{$this->certfile}'");
        $context = stream_context_create();
        // local_cert must be in PEM format
        stream_context_set_option($context, 'ssl', 'local_cert', $this->certfile);
        // Pass Phrase (password) of private key
        stream_context_set_option($context, 'ssl', 'passphrase', $this->certpass);
        stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
        stream_context_set_option($context, 'ssl', 'verify_peer', false);
        return $context;
    }

}

class CertificateException extends \Exception {
}
