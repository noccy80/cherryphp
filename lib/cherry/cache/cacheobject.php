<?php

namespace Cherry\Cache;

use App;
use Cherry\DateTime\Duration;

class CacheObject {

    const CO_USE_DEFAULT    = 0x00; /// Default storage
    const CO_USE_MEMORY     = 0x01; /// Use memory cache
    const CO_USE_DISK       = 0x02; /// Use disk cache
    const CO_USE_AUTO       = 0x03; /// Automatically decide best placement
    const CO_FLUSH          = 0x04; /// Flush the cache key
    const CO_DELAY          = 0x08; /// Delay generation until access (get)
    const CO_FILE_GENERATOR = 0x10; /// Generator is a file
    const CO_COMPRESS       = 0x20; /// Compress the cache object

    const CS_EMPTY          = 0x00; /// No data
    const CS_MISS           = 0x01; /// Cache miss
    const CS_HIT            = 0x02; /// Cache hit
    const CS_UPDATED        = 0x04; /// Data updated

    private $cache_queried = false,
            $cache_hit = false,
            $content = null,
            $contenttype = null,
            $expires = null,
            $asset = null,
            $generator = null,
            $variant = null,
            $flags = 0x00,
            $objecturi = null,
            $objectid = null,
            $state = self::CS_EMPTY;

    /**
     *
     */
    public function __construct($assetid, $flags = self::CO_DEFAULT, $generator = null, $variant = null) {
        $this->flags = $flags;
        $this->generator = $generator;
        $this->variant = $variant;
        if (!$flags & self::CO_DELAY) $this->query();
    }

    public function __destruct() {
        $this->setCacheRecord();
    }

    private function query() {

        // Bail if the cache has already been queried
        if ($this->cache_queried) return;

        // Generate the object ID and look it up
        $variantdata = [];
        if (is_array($this->variant)) {
            foreach($this->variant as $k=>$v) $variantdata[] = $k.'='.$v;
        } else {
            $variantdata = [ $this->variant ];
        }
        $ostr = $this->asset.':'.join(';',$variantdata);
        $this->objectid = sprintf('%s%02x',sha1($ostr), strlen($ostr));
        if (($this->flags & self::CO_USE_AUTO) == self::CO_USE_AUTO) {
            // Query memory cache for an asset record with key ar:assetid
            $this->objecturi = 'cache:auto:'.$this->objectid;
        } elseif (($this->flags & self::CO_USE_MEMORY) == self::CO_USE_MEMORY) {
            // Query memory cache
            $this->objecturi = 'cache:mem:'.$this->objectid;
        } elseif (($this->flags & self::CO_USE_DISK) == self::CO_USE_DISK) {
            // Query disk cache
            $this->objecturi = 'cache:disk:'.$this->objectid;
        } else {
            user_error("Flags don't define caching: ".$this->flags);
        }
        $this->getCacheRecord();

        // If we got no hit in cache, and we got a generator assigned we go
        // ahead and generate the content and update the cache.
        if (($this->cache_hit == false) && ($this->generator)) {
            list($this->content,$this->contenttype,$this->expires) = $this->generate();
        } elseif ($this->cache_hit == false) {
            $this->content = '';
            $this->contenttype = 'text/html';
        }

        // Update state
        $this->cache_queried = true;

    }

    public function isCached() {
        return $this->cache_hit;
    }

    public function setContent($content,$contenttype) {
        $this->content = $content;
        $this->contenttype = $contenttype;
    }

    public function bufferContent($contenttype='text/html') {
        ob_start(array($this,'_ob_buffer_func'));
        $this->contenttype = $contenttype;
    }

    public function bufferEnd() {
        ob_end_flush();
    }

    public function _ob_buffer_func($buffer,$flags) {
        $this->content.=$buffer;
        return $buffer;
    }

    /**
     * @brief Generate the data for the object.
     *
     * Data can be a valid callback, a file, or a text string, and it is
     * set in the call to the constructor. This method is internal, and is called
     * when the cache miss occurs.
     */
    private function generate() {

        // Default expiry time
        $config = App::config();
        $def_expiry = $config->query('cache/default_expiry', '30m');

        // We don't want to call the generator function if the generator is
        // explicitly set to be a file
        if (is_callable($this->generator) && !($this->flags & self::CO_FILE_GENERATOR)) {
            @list($content,$contenttype,$expires) = call_user_func($this->generator,(object)$this->variant);
            if (empty($contenttype)) $contenttype = 'application/octet-stream';
            if (empty($expires)) $expires = $def_expiry;
            return [$content,$contenttype,$expires];
        } elseif (is_file($this->generator)) {
            // Read the file
            // Find the content type

        } else {
            // Return the generator string
            return [(string)$this->generator,'text/html',$def_expiry];
        }

    }

    /**
     * @brief Request a record from the cache.
     *
     * This will read the complete object from the cache and prepare it for
     * access.
     */
    private function getCacheRecord() {

        assert(!empty($this->objecturi));
        @list($type,$cache,$id) = explode(':',$this->objecturi);

        if ($type == 'cache') {
            if ($cache == 'disk') {
                // Check cache dir for object
                $path = App::config()->query('paths.cache');
                $blobpath = $path._DS_.$id;
                if (file_exists($blobpath)) {
                    $blob = file_get_contents($blobpath);
                    list($header,$content) = explode('####',$blob,2);
                    $header = (array)json_decode($header);
                    $this->cache_hit = true;
                    $this->content = $content;
                    $this->expires = $header['expires'];
                    $this->contenttype = $header['content-type'];
                    $this->cache_hit = true;
                    \Cherry\debug("Cache hit: %s (%s)", $id, $blobpath);
                } else {
                    $this->cache_hit = false;
                    \Cherry\debug("Cache miss: %s (%s)", $id, $blobpath);
                }
            } elseif ($cache == 'ram') {
                // check memcached
            } elseif ($cache == 'auto') {
                // check memcached for metadata, then read entry from disk.
            }
        }

    }

    private function setCacheRecord() {

        assert(!empty($this->objecturi));
        list($type,$cache,$id) = explode(':',$this->objecturi);

        $key_cache = 'entry:'.$id;
        $key_meta = 'meta:'.$id;

        if ($type == 'cache') {
            if ($cache == 'disk') {
                // Check cache dir for object
                $path = App::config()->query('paths.cache');
                if (!is_dir($path))
                    user_error("Cache directory {$path} does not exist!");
                $header = [
                    'content-type' => $this->contenttype,
                    'content-length' => strlen($this->content),
                    'expires' => Duration::toSeconds($this->expires,time())
                ];
                $blob = json_encode((object)$header).'####'.$this->content;
                file_put_contents($path._DS_.$id, $blob);
            } elseif ($cache == 'ram') {
                // check memcached
            } elseif ($cache == 'auto') {
                // check memcached for metadata, then read entry from disk.
            }
        }

    }

    public function output() {
        if ($this->flags & self::CO_DELAY) $this->query();
        if (!headers_sent()) header('Content-Type: '.$this->contenttype);
        echo $this->content;
        return $this->cache_hit;
    }

    public function getContent() {
        if ($this->flags & self::CO_DELAY) $this->query();
        return $this->content;
    }

    public function getContentType() {
        if ($this->flags & self::CO_DELAY) $this->query();
        return $this->contenttype;
    }

}
