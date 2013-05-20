<?php

namespace Cherry\Net;

use ArrayAccess;

/**
 * @brief URL wrapper
 *
 * Parses and builds URLs and facilitates a way of manipulating the different
 * components of them.
 *
 * @author Christopher Vagnetoft <noccy@chillat.net>
 */
class Url implements ArrayAccess {

    private $scheme = null;
    private $host = null;
    private $port = null;
    private $user = null;
    private $pass = null;
    private $path = null;
    private $query = array();
    private $fragment = null;

    /**
     * @brief Constructor
     *
     * @param
     */
    function __construct($url = null) {

        // Parse the URL if we got any
        if ((strpos($url,":///")!==false)
            || (strpos($url,"://.")!==false)) {
            list($proto,$path) = explode("://",$url);
            $this->scheme = $proto;
            if (strpos($path,"?")!==false) {
                list($path,$query) = explode("?",$path,2);
                $this->query = $this->qs_parse($query);
            } else {
                $this->query = [];
            }
            $this->path = $path;
            $this->user = null;
            $this->pass = null;
            $this->fragment = null;
        } elseif ($url) {
            $c = parse_url($url);
            if (array_key_exists('scheme',$c)) $this->scheme = $c['scheme'];
            if (array_key_exists('host',$c)) $this->host = $c['host'];
            if (array_key_exists('port',$c)) $this->port = $c['port'];
            if (array_key_exists('user',$c)) $this->user = $c['user'];
            if (array_key_exists('pass',$c)) $this->pass = $c['pass'];
            if (array_key_exists('path',$c)) $this->path = $c['path'];
            if (array_key_exists('query',$c)) $this->query = $this->qs_parse($c['query']);
            if (array_key_exists('fragment',$c)) $this->fragment = $c['fragment'];
        }
        
    }

    /**
     * @brief Parse query string and return array
     *
     * @param
     * @return
     */
    private function qs_parse($query) {

        $queries = array();
        parse_str($query,$queries);
        return $queries;

    }

    /**
     * @brief Property getter
     *
     * @param
     * @return
     */
    public function __get($key) {
        switch($key) {
        case 'scheme':
        case 'schema':
        case 'protocol':
            return $this->scheme;
            break;
        case 'host':
            return $this->host;
            break;
        case 'hostport':
            if ($this->port)
                return $this->host.':'.$this->port;
            return $this->host;
            break;
        case 'port':
            return $this->port;
            break;
        case 'user':
            return $this->user;
            break;
        case 'pass':
            return $this->pass;
            break;
        case 'path':
            return $this->path;
            break;
        case 'query':
            return http_build_query($this->query);
            break;
        case 'fragment':
            return $this->fragment;
            break;
        default:
            throw new \BadMethodCallException("No property ".$key." on URL");
        }
    }

    /**
     * @brief Property setter
     *
     * @param
     * @param
     */
    public function __set($key,$value) {
        switch($key) {
        case 'scheme':
        case 'schema':
        case 'protocol':
            $this->scheme = $value;
            break;
        case 'host':
            $this->host = $value;
            break;
        case 'port':
            $this->port = $value;
            break;
        case 'user':
            $this->user = $value;
            break;
        case 'pass':
            $this->pass = $value;
            break;
        case 'path':
            $this->path = $value;
            break;
        case 'query':
            $this->query = $this->qs_parse($value);
            break;
        case 'fragment':
            $this->fragment = $value;
            break;
        default:
            throw new \BadMethodCallException("No property ".$key." on URL");
        }
    }

    /**
     * @brief Assign a component of the query string
     *
     * @param
     * @param
     */
    public function setParameter($key,$value) {
        $this->query[$key] = $value;
    }

    /**
     * @brief Retrieve a component of the query string
     *
     * @param
     * @return
     */
    public function getParameter($key) {
        if (array_key_exists($key,$this->query)) return $this->query[$key];
        return null;
    }
    
    public function offsetGet($key) {
        return $this->getParameter($key);
    }
    
    public function offsetSet($key,$value) {
        $this->setParameter($key,$value);
    }
    
    public function offsetExists($key) {
        return ($this->getParameter($key)!==null);
    }
    
    public function offsetUnset($key) {
        $this->setParameter($key,null);
    }

    /**
     * @brief Cast the URL back into a string (magic method)
     *
     * @return
     */
    public function __toString() {
        return $this->toString();
    }

    /**
     * @brief Cast the URL back into a string
     *
     * @return
     */
    public function toString() {
        if ($this->scheme != null) { $scheme = $this->scheme . '://'; }
            else { $scheme = ''; }
        if ($this->host != null) {
            if ($this->port != null) {
                $host = $this->host.':'.$this->port;
            } else {
                $host = $this->host;
            }
        } else {
        	$host = null;
        }
        if ($this->user != null) {
            if ($ths->password != null) {
                $auth = $this->user.':'.$this->pass.'@';
            } else {
                $auth = $this->user.'@';
            }
        } else {
            $auth = '';
        }
        if ($this->path != null) {
            $path = $this->path;
        } else {
            $path = '';
        }
        if (count($this->query)>0) {
            $query = '?'.http_build_query($this->query);
        } else {
            $query = '';
        }
        if ($this->fragment != null) {
            $fragment = '#'.$this->fragment;
        } else {
            $fragment = '';
        }
        $url = $scheme.$auth.$host.$path.$query.$fragment;
        return $url;
    }

    /**
     * @brief Return the URL with one or more parameters updated.
     *
     * This method does NOT update the original querystring.
     *
     * @param array $params The parameters as an assoc array (k=>v)
     * @return url The new URL
     */
    public function getWith(array $params) {
        $bk = $this->query;
        foreach($params as $k=>$v) {
            $this->query[$k] = $v;
        }
        $str = $this->toString();
        $this->query = $bk;
        return url($str);
    }

    /**
     * @brief Helper function to return a URL object from the current URL
     *
     * Will return an empty URL if the request object is not present.
     *
     * @static
     * @return Url The URL object
     */
    static function createFromCurrent() {
        return new Url($_SERVER['REQUEST_URI']);
    }

    /**
     * @brief Apply a relative path to an URL
     *
     * Will return the new URL. Does not modify the original URL.
     *
     * @param string $url The relative URL
     * @return url The new URL
     */
    public function apply($url) {
        $url = (string)$url;
        $qurl = new Url($url);

        // If we got a scheme, we return the URL straight away
        if ($qurl->scheme != null) {
            return $qurl;
        }

        $ret = new Url((string)$this);

        // If the url begins with a / we ditch the old path, otherwise we have
        // to process the path to create a new url.
        if (substr($url,0,1) == '/') {
            $ret->path = $qurl->path;
        } else {
            $retpath = explode('/',$ret->path);
            $newpath = explode('/',$url);

            // If the last item in the list is not empty, we need to strip it
            if ($retpath[count($retpath)-1] != '') {
                $retpath = array_slice($retpath,0,count($retpath)-1);
            }
            $retpath = array_merge($retpath,$newpath);

            // Go over the path and strip out any backlinks (..)
            $outpath = array('');
            for($i = 0; $i < count($retpath); $i++) {
                if ($i < count($retpath) - 1) {
                    if ($retpath[$i+1] == '..') { $i++; } else { $outpath[] = $retpath[$i]; }
                } else {
                    $outpath[] = $retpath[$i];
                }
            }

            $path = join('/',$outpath);
            $ret->path = $path;
        }
        return $ret;
    }

    /**
     * @brief Match the URL against a regular expression
     *
     * @param string $expression The regular expression
     * @return bool True if the URL match
     */
    public function like($expression) {
            return preg_match($expression,(string)$this);
    }

}
