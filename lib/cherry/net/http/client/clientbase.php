<?php

namespace Cherry\Net\Http\Client;

use Cherry\Base\EventEmitter;

abstract class ClientBase extends EventEmitter {
    protected
        $url                = null;
        
    private
        $cookies            = [],
        $cookiejar          = null,
        $auth_type          = null,
        $auth_params        = null;
        
    const
        HTTP_PROXY          = 'HTTP_PROXY',         ///< (string) Use HTTP proxy for requests
        HTTP_ALL_COOKIES    = 'HTTP_ALL_COOKIES',   ///< (bool) Send all cookies, not just the ones for the domain.
        HTTPS_VERIFY_CERT   = 'HTTPS_VERIFY_CERT',  ///< (bool) Verify the remote certificate
        HTTPS_VERIFY_FP     = 'HTTPS_VERIFY_FP';    ///< (string) Match fingerprint against value
        
    /**
     * @brief Set the request method.
     * @abstract
     * @param 
     */
    abstract public function setMethod($method);
    /**
     * @brief Set a header field
     * @abstract
     * @param 
     * @param 
     */
    abstract public function setHeader($header,$value);
    /**
     * @brief
     * @abstract
     * @param 
     * @param 
     */
    abstract public function setPostData($contenttype, $postdata);
    /**
     * @brief
     * @abstract
     * @return
     */
    abstract public function execute();
    /**
     * @brief
     * @abstract
     * @return
     */
    abstract public function getLastError();
    /**
     * @brief
     * @abstract
     */
    abstract public function setOption($koa,$v=null);
    /**
     * @brief
     * @abstract
     * @param
     * @return
     */
    abstract public function getOption($k);
    
    /**
     * @brief Set the URL for the request.
     *
     * @param string $url The URL to request.
     */
    public function setUrl($url) {
        // if (stream_is_local($url))
        //     user_error("StreamClient can't open local resources");
        $this->url = (string)$url;
    }

    /**
     * @brief Get the URL for the request.
     *
     * @return string The URL.
     */
    public function getUrl() {
        return $this->url;
    }
    
    /**
     * @brief Assign a cookie jar
     *
     *
     */
    public function setCookieJar($jar) {
        $this->cookiejar = $jar;
        if (is_readable($jar)) {
            $cs = explode("\n",file_get_contents($jar));
            foreach($cs as $c) $this->setCookieRaw($c);
        }
    }
    
    /**
     *
     */
    public function setCookie($k,$v,$p=null) {
        if ($p) $p='; '.$p;
        $this->cookies[$k] = "{$k}={$v}{$p}";
        if (!empty($this->cookiejar)) {
            file_put_contents($this->cookiejar,join("\n",$this->cookies));
        }
        \Cherry\Debug("ClientBase: Set cookie %s", $this->cookies[$k]);
    }

    /**
     *
     */
    public function setCookieRaw($ks) {
        if (trim($ks) == '') return;
        if (strpos($ks,'; ')!==null) {
            list($cookie,$p) = explode('; ',$ks,2);
            list($k,$v) = explode('=',$cookie,2);
            $this->setCookie($k,$v,$p);
        } else {
            list($k,$v) = explode('=',$ks,2);
            $this->setCookie($k,$v);
        }
    }

    /**
     *
     */
    public function getAllCookies() {
        return $this->cookies;
    }

    /**
     *
     */
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

    /**
     *
     */
    public function getCookieRaw($k) {
        if (array_key_exists($this->cookies,$k)) {
            return $this->cookies[$k];
        }
    }
    
    /**
     * @brief Return valid cookies for the request
     *
     */
    public function getCookiesForRequest() {
        return array_values($this->cookies);
    }
    
    /**
     * @brief Set up HTTP authentication.
     *
     * @param string $type The authentication type (basic, challenge, ..)
     * @param array $params The authentication params (depends on type)
     */
    public function setAuthentication($type,array $params) {
        $this->auth_type = $type;
        $this->auth_params = $params;
    }
    
    /**
     * @brief Get the authentication type.
     *
     * @return string The authentication type
     */
    public function getAuthenticationType() {
        return $this->auth_type;
    }
    
    /**
     * @brief Retrieve the authentication params.
     *
     * @return array The authentication params
     */
    public function getAuthenticationParams() {
        return $this->auth_params;
    }
}
