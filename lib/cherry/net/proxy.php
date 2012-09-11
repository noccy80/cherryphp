<?php

namespace cherry\net\proxy;

abstract class Proxy {
    protected $credentials = null;
    abstract public function write($data);
    abstract public function read();
    public function setAuthCredentials(Array $cred) {
        $this->credentials = $cred;
    }
    public function __construct($host,$port,$flags=0x00) {
        \lepton\log(\lepton\LOG_DEBUG,'Creating new proxy (%s)',get_class($this));
    }
    public function getSocket() { }
    public function connect() { }
    public function disconnect() { }
}

class SocksProxy extends Proxy {
    public function write($data) { }
    public function read() { }
}
