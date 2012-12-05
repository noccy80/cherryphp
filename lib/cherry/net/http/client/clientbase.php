<?php

namespace Cherry\Net\Http\Client;

use Cherry\Base\EventEmitter;

abstract class ClientBase extends EventEmitter {
    protected
        $url = null;
    private
        $cookies = [],
        $cookiejar = null;
    abstract public function setMethod($method);
    abstract public function setHeader($header,$value);
    abstract public function setPostData($contenttype, $postdata);
    abstract public function execute();
    abstract public function getLastError();
    
    public function setUrl($url) {
        // if (stream_is_local($url))
        //     user_error("StreamClient can't open local resources");
        $this->url = (string)$url;
    }

    public function getUrl() {
        return $this->url;
    }
    
    public function setCookieJar($jar) {
        $this->cookiejar = $jar;
        if (is_readable($jar)) {
            $this->cookies = explode("\n",file_get_contents($jar));
        }
    }
    public function setCookie($k,$v,$p=null) {
        if ($p) $p='; '.$p;
        $this->cookies[$k] = "{$k}={$v}{$p}";
        if (!empty($this->cookiejar)) {
            file_put_contents($this->cookiejar,join("\n",$this->cookies));
        }
    }
    public function setCookieRaw($ks) {
        if (strpos($ks,'; ')!==null) {
            list($cookie,$p) = explode('; ',$ks,2);
            list($k,$v) = explode('=',$cookie,2);
            $this->setCookie($k,$v,$p);
        } else {
            list($k,$v) = explode('=',$ks,2);
            $this->setCookie($k,$v);
        }
    }
    public function getCookie($k) {
        if (array_key_exists($this->cookies,$k)) {
            $ks = $this->cookies[$k];
            if (strpos($ks,'; ')!==null) {
                list($cookie,$p) = explode('; ',$ks,2);
                list($k,$v) = explode('=',$cookie,2);
                return $v;
            } else {
                list($k,$v) = explode('=',$ks,2);
                return $v;
            }
        } else {
            return null;
        }
    }
    public function getCookieRaw($k) {
        if (array_key_exists($this->cookies,$k)) {
            return $this->cookies[$k];
        }
    }
    public function getCookiesForRequest() {
        return array_values($this->cookies);
    }
    
    public function setAuthentication($type,$params) {
    
    }
}
