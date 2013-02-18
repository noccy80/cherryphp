<?php

namespace Cherry\Cache;

define('_CACHE_SEP_','#CACHE#');

use App;
use Cherry\DateTime\Duration;

/**
 *
 *
 *
 * Storage settings:
 *  - CO_USE_DEFAULT - Will default to CO_USE_AUTO or the value of the config
 *      key "cache/cacheobject/default
 *  - CO_USE_MEMORY - Object will always be placed in memory cache
 *  - CO_USE_DISK - Object will always be placed in disk cache
 *  - CO_USE_AUTO - Store object metadata in memory cache while automatically
 *      determining whether to store the blob in disk or memory cache based
 *      on file size.
 *
 * Additional flags:
 *  - CO_FLUSH - Flush the key if it exists, forcing re-generation of the content.
 *  - CO_DELAY - Don't check (or generate) the cache before the object is
 *      explicitly requested. Non-requested objects will thus not be generated.
 *  - CO_GEN_FILE - Disable type detection of generator and always assume
 *      it is a file path.
 *  - CO_GEN_PHPFILE - Like CO_GEN_FILE except the content will be included rather
 *      than read.
 *  - CO_COMPRESS - Compress the blob before saving the blob (and uncompress on
 *      fetch).
 *  - CO_USEMTIME - Use file modification time to determine whether the content
 *      need to be generated.
 */
class CacheObject {

    const CO_USE_DEFAULT    = 0x00; /// Default storage
    const CO_USE_MEMORY     = 0x01; /// Use memory cache
    const CO_USE_DISK       = 0x02; /// Use disk cache
    const CO_USE_AUTO       = 0x03; /// Automatically decide best placement
    const CO_FLUSH          = 0x04; /// Flush the cache key
    const CO_DELAY          = 0x08; /// Delay generation until access (get)
    const CO_GEN_FILE       = 0x10; /// Generator is a text file
    const CO_GEN_PHPFILE    = 0x10; /// Generator is a PHP file
    const CO_COMPRESS       = 0x20; /// Compress the cache object
    const CO_USEMTIME       = 0x40; /// Use file modification time to check freshness

    const CS_EMPTY          = 0x00; /// No data
    const CS_MISS           = 0x01; /// Cache miss
    const CS_HIT            = 0x02; /// Cache hit
    const CS_UPDATED        = 0x04; /// Data updated
    const CS_EXPIRED        = 0x08; /// Data expired when checked

    private $cache_queried  = false,/// If the cache has been queried.
            $cache_hit      = false,/// If the cache has the data ready.
            $content        = null, /// The actual content of the object
            $contenttype    = null, /// The content type of the object
            $expires        = null, /// Unix time when the object expires.
            $expiresecs     = null, /// Number of seconds til expiry.
            $asset          = null, ///
            $generator      = null, /// Generator, either method, file, string or null.
            $params         = [],
            $variant        = [],   /// Variant specification, f.ex. size or color.
                                    /// Relevant to lookup of specific variation of the
                                    /// object from the cache.
            $flags          = 0x00, /// Flags, bitwise combination of CO_* above.
            $objecturi      = null, /// Uri of the object in cache (cache:<type>:<id>)
            $objectid       = null, /// Id of the object (sha1+4 bytes size as hex)
            $state          = self::CS_EMPTY, /// State, one of CS_* above.
            $cachepath      = null; /// Path to cache dir

    /**
     * @brief Create a new CacheObject.
     *
     * The $assetid should be a unique string which identifies the asset in the
     * cache. It will be concatenated with the keys and values of the $variant
     * argument is present, thus producing something similar to the string
     * "myimage.jpg;color=blue" which is then hashed to perform the lookup.
     *
     * The flags are defined above as CO_* constants.
     *
     * The generator will be called if it is a method call, and the returning data
     * is supposed to come as either a single value (in which the content-type is
     * set to text/html) or as an array having three values for content, content-type
     * and the expiry time.
     */
    public function __construct($assetid, $flags = self::CO_DEFAULT, $generator = null, array $variant = null, array $params = null) {
        $this->flags = $flags;
        $this->generator = $generator;
        $this->variant = (array)$variant;
        $this->assetid = $assetid;
        $this->params = (array)$params;
        ksort($this->variant);
        $path = ((array)App::config()->get('paths.cache'));
        $path = $path[0];
        if (!$path)
            $path = "/tmp/cherrycache";
        $this->cachepath = $path;
        if (!\file_exists($path))
            \mkdir($path,0700,true);
        if (!($flags & self::CO_DELAY)) $this->query();

    }

    /**
     * @brief Update the cache if modified.
     *
     */
    public function __destruct() {
        $this->setCacheRecord();
    }

    /**
     * @brief Called internally to fetch the object from cache.
     *
     * Will do all the magic including updating.
     */
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
        // We generate an object id from a concatenation of the sha1 hash and
        // the size of the object as 16 bits hex. This should make the key
        // unique enough.
        $ostr = $this->assetid.':'.join(';',$variantdata);
        $this->objectid = sprintf('%s%04x',sha1($ostr), strlen($ostr));
        if ($this->flags == 0) {
            $this->flags |= self::CO_USE_AUTO;
        }
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
        \debug("CacheObject: Querying object; uri={$this->objecturi}");
        $this->getCacheRecord();

        // If we got no hit in cache, and we got a generator assigned we go
        // ahead and generate the content and update the cache.
        if (($this->cache_hit == false) && ($this->generator)) {
            $gen = $this->generate();
            if (!is_array($gen)) return;
            list($this->content,$this->contenttype,$this->expires) = $gen;
            $this->state = self::CS_UPDATED;
        } elseif ($this->cache_hit == false) {
            $this->content = '';
            $this->contenttype = 'text/html';
            $this->state = self::CS_EMPTY;
        }

        // Update state
        $this->cache_queried = true;

    }

    /**
     * @brief Check if an objects data is available.
     *
     * Returns false if the key is not present. Calling this will also call
     * on query() if CO_DELAY is set.
     *
     * @return bool True if the data is available.
     */
    public function isCached() {
        if ($this->flags & self::CO_DELAY) $this->query();
        return $this->cache_hit;
    }

    /**
     * @brief Manually set content and content-type of object.
     *
     * @param string $content The content
     * @param string $content-type The content-type
     */
    public function setContent($content,$contenttype='text/html') {
        $this->content = $content;
        $this->contenttype = $contenttype;
        $this->state = self::CS_UPDATED;
    }

    /**
     * @brief Create the cache object via output buffering.
     *
     * This allows you to simply include or output any data that you would like
     * to add to the cache for the object.
     *
     * @param string $contenttype The content-type of the object
     */
    public function bufferContent($contenttype='text/html') {
        $this->content = '';
        ob_start(array($this,'_ob_buffer_func'));
        $this->contenttype = $contenttype;
        $this->state = self::CS_UPDATED;
        $this->cache_hit = true;
    }

    /**
     * @brief End buffering operation for cache object.
     *
     */
    public function bufferEnd() {
        ob_end_flush();
    }

    /**
     * @brief Internal function for output buffering callback.
     * @internal
     */
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

        \Cherry\debug("Generating content...");
        // Default expiry time
        $config = App::config();
        $def_expiry = $config->get('cache.default-expiry', '30m');
        // We don't want to call the generator function if the generator is
        // explicitly set to be a file
        if (is_callable($this->generator) && !($this->flags & (self::CO_GEN_FILE | self::CO_GEN_PHPFILE))) {
            $ret = call_user_func_array($this->generator,array_merge([ $this->assetid, (object)$this->variant ], $this->params));
            if (count($ret) > 2) {
                list($content,$contenttype,$expires) = $ret;
            } elseif (count($ret) > 1) {
                list($content,$contenttype) = $ret;
                $expires = null;
            } elseif (count($ret) > 0) {
                $content = $ret;
                $expires = null;
                $contenttype = null;
            } elseif ($ret === null) {
                return null;
            } else {
                throw new \UnexpectedValueException("cache generator return value invalid.");
            }
            if (empty($contenttype)) $contenttype = 'application/octet-stream';
            if (empty($expires)) $expires = $def_expiry;
            return [$content,$contenttype,$expires];
        } elseif (is_file($this->generator)) {
            // Read or include the file
            if ($this->flags & self::CO_GEN_PHPFILE) {
                // Evaluate the PHP file into the content.
                $this->bufferContent();
                $meta = @include $this->generator;
                $this->bufferEnd();
                // Use the metadata returned to determine content type.
                if (($meta) && (!is_array($meta))) {
                    $contenttype = $meta;
                } elseif ($meta) {
                    $contenttype = $meta['content-type']?:'text/html';
                } else {
                    \Cherry\debug("Warning: Generated content from {$this->generator} does not return ['content-type'], 'text/html' assumed.");
                    $contenttype = 'text/html';
                }
                return [$content,$contenttype,$def_expiry];
            } else {
                // Raw file
                $content = file_get_contents($this->generator);
                $fi = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
                $contenttype = finfo_file($fi,$this->generator);
                finfo_close($fi);
                return [$content,$contenttype,$def_expiry];
            }
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
                $path = $this->cachepath;
                $blobpath = $path._DS_.$id;
                if (file_exists($blobpath)) {
                    $blob = file_get_contents($blobpath);
                    list($header,$content) = explode(_CACHE_SEP_,$blob,2);
                    $header = unserialize($header);
                    $this->cache_hit = true;
                    if (!empty($header['compressed'])) {
                        switch ((string)$header['compressed']) {
                        case 'gzip':
                            $this->content = gzuncompress($content);
                            break;
                        default:
                            \Cherry\debug("Unsupported compression of cache object. Invalidating blob.");
                            unlink($blobpath);
                            $this->state = self::CS_EMPTY;
                            $this->cache_hit = false;
                        }
                    } else {
                        $this->content = $content;
                    }
                    $this->expiresecs = $header['expires'];
                    $this->contenttype = $header['content-type'];
                    $this->cache_hit = true;
                    $this->state = self::CS_HIT;
                    \Cherry\debug("Cache hit: %s (%s)", $id, $blobpath);
                } else {
                    $this->cache_hit = false;
                    $this->state = self::CS_MISS;
                    \Cherry\debug("Cache miss: %s (%s)", $id, $blobpath);
                }
            } elseif ($cache == 'mem') {
                // check memcached
                \Cherry\debug("Memcached not implemented: %s (%s)", $id, $blobpath);
                throw new \LogicException("Memcached not implemented");
            } elseif ($cache == 'auto') {
                // check memcached for metadata, then read entry from disk.
                \Cherry\debug("Hybrid cache not implemented: %s (%s)", $id, $blobpath);
                throw new \LogicException("Hybrid cache not implemented");
            }
            if ((!empty($blobpath)) && ($this->expiresecs > 0) && (time() > $this->expiresecs)) {
                \Cherry\debug("Entry expired %d seconds ago: %s (%s)", (time()-$this->expiresecs), $id, $blobpath);
                $this->state = self::CS_EXPIRED;
                $this->cache_hit = false;
                // TODO: This is not gonna work for memcache, so need rewrite.
                @unlink($blobpath);
            }
        }

    }

    private function setCacheRecord() {

        assert(!empty($this->objecturi));
        list($type,$cache,$id) = explode(':',$this->objecturi);
        if ($this->state != self::CS_UPDATED) {
            \Cherry\debug("Cache entry not updated for {$id}");
            return;
        }
        \Cherry\debug("Updating cache entry for {$id}");

        $key_cache = 'entry:'.$id;
        $key_meta = 'meta:'.$id;

        if (!$this->expires)
            $this->expires = App::config()->get('cache.default-expiry','30m');

        if ($type == 'cache') {
            if ($cache == 'disk') {
                // Check cache dir for object
                $path = $this->cachepath;
                if ((!is_dir($path)) && (!mkdir($path)))
                    user_error("Cache directory {$path} does not exist and mkdir failed!");
                $header = [
                    'content-type' => $this->contenttype,
                    'content-length' => strlen($this->content),
                    'expires' => Duration::toSeconds($this->expires,time())
                ];
                if ($this->flags & self::CO_COMPRESS) {
                    $header['compressed'] = 'gzip';
                    $content = gzcompress($this->content);
                } else {
                    $content = $this->content;
                }
                $blob = serialize($header)._CACHE_SEP_.$content;
                file_put_contents($path._DS_.$id, $blob);
            } elseif ($cache == 'ram') {
                // check memcache
            } elseif ($cache == 'auto') {
                // check memcache for metadata, then read entry from disk or
                // memcache.
                $max_blob = App::config()->get('cache.max-blob-size');
            }
        }

    }

    /**
     * @brief Flush the cache entry to the client with content-type set.
     *
     * This method will return true if the cache has content and false
     * otherwise, allowing you to seed the cache entry with setContent()
     * or by calling bufferContent().
     *
     * @return bool True if object exist in cache.
     */
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

    public static function getUrl($url,$refresh=false) {
        $flags = self::CO_USE_DISK|self::CO_COMPRESS;
        if ($refresh) $flags |= self::CO_FLUSH;
        $co = new CacheObject($url,$flags,function($assetid,$var){
            $doc = file_get_contents($assetid);
            return [ $doc, 'text/html', '30m'];
        });
        return $co->getContent();
    }

}
