<?php

namespace Cherry\Crypto;

class Algorithm {
    
    const MODE_CFB = 'cfb';
    const MODE_CBC = 'cbc'; ///< Cipher Block Chaining
    const MODE_ECB = 'ecb'; ///< Electronic Code Book
    const MODE_OFB = 'ofb';
    
    protected $algo;
    protected $key;
    protected $mode;
    
    static function __callstatic($algo,$args) {
        if (count($args) == 2) {
            return new Algorithm($algo,$args[0],$args[1]);
        } elseif (count($args) == 1) {
            return new Algorithm($algo,$args[0]);
        } else {
            return new Algorithm($algo);
        }
    }
    
    static function getAlgorithms() {
        return mcrypt_list_algorithms();
    }
    
    function __construct($algo = 'tripledes', $key = null, $mode = self::MODE_ECB) {
        $this->algo = $algo;
        $this->key = $key;
        $this->mode = $mode;
    }
    
    public function setKey($key) {
        $this->key = $key;
    }
    
    function encrypt($data) {
        return mcrypt_encrypt($this->algo, $this->key, $data, $this->mode);
    }
    
    function decrypt($data) {
        return mcrypt_decrypt($this->algo, $this->key, $data, $this->mode);
    }
    
}
