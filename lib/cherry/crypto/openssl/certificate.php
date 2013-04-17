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

    public function getCertificateInfo($shortnames=true) {
        if (!$this->certstr) $this->certstr = file_get_contents($this->certfile);
        $info = openssl_x509_parse($this->certstr,$shortnames);
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
        $info = $this->getCertificateInfo(false);
        
        $subject    = $info["subject"];
        $asubject   = [];
        foreach($subject as $k=>$v) $asubject[] = "    {$k}: {$v}";
        $ssubject   = join("\n",$asubject);
        $issuer     = $info["issuer"];
        $aissuer    = [];
        foreach($issuer as $k=>$v) $aissuer[] = "    {$k}: {$v}";
        $sissuer    = join("\n",$aissuer);
        $hash       = $info["hash"];
        list($svalidfrom,$svalidto) = $this->getValidity();
        $purposes   = $info["purposes"];
        foreach($purposes as $purpose) {
            $types = [];
            if ($purpose[0]) $types[] = "GA"; // General availability
            if ($purpose[1]) $types[] = "T"; // Tested
            if (count($types) == 0) $types[] = "None";
            $type = join(",", $types);
            $apurposes[] = "    {$purpose[2]} ({$type})";
        }
        $spurpose   = join("\n", $apurposes);
        $subjectkey = trim($info["extensions"]["subjectKeyIdentifier"]);
        $issuerkey  = trim($info["extensions"]["authorityKeyIdentifier"]);
        
        $txt        = "Hash: 0x{$hash}\nValidity:\n    From: {$svalidfrom}\n    To:   {$svalidto}\nSubject:\n{$ssubject}\nIssued by:\n{$sissuer}\nPurposes:\n{$spurpose}\nSubjectKey: {$subjectkey}\nIssuerKey: {$issuerkey}\n\n";
        
        return $txt; // $str;
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
