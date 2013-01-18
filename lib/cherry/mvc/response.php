<?php

namespace Cherry\Mvc;

use Cherry\Mvc\Document;
use Cherry\Cache\CacheObject;

class Response {

    private
            $document = null,
            $cachable = false,
            $status = 200,
            $contenttype = 'text/html',
            $contentlength = 0,
            $protocol = null;

    public function setDocument(Document $document) {
        $this->document = $document;
    }

    public function getDocument() {
        return $this->document;
    }

    public function __construct($protocol) {
        $this->protocol = $protocol;
    }

    public function setStatus($code,$status) {
        header(join(' ',[
            $this->protocol,
            $code,
            $status
        ]), true, $code);
        $this->status = $code;
    }

    public function setHeader($header,$value) {
        if (headers_sent()) return false;
        header($header.': '.$value, true);
        return true;
    }

    public function send404($file) {
        while(ob_get_level()>0) ob_end_clean();
        $this->setStatus(404,'File not found');
        $errstr = "<h1>File not found.</h1>\n<p>The resource could not be found.</p>\n";
        $this->contentlength = strlen($errstr);
        $this->contenttype = 'text/html';
        echo $errstr;
    }

    public function setCacheControl($cachecontrol) {
        $this->setHeader('Cache-Control', $cachecontrol);
    }

    public function sendFile($file) {
        $lastmod = filemtime($file);
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            $ifmod = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
            if ($ifmod >= $lastmod) {
                header('Not Modified',true,304);
                return;
            }
        }
        $ct = null;
        // Apply content type
        foreach([
            '*.css' => 'text/css',
            '*.js' => 'text/javascript'
        ] as $ptn => $ct)
            if (fnmatch($ptn,$file))
                $ctype = $ct;
        // If no match, try to determine
        if (empty($ctype)) $ctype = mime_content_type($file);
        // Set headers
        header('Content-Type: '.$ctype);
        header('Content-Length: '.filesize($file));
        header('Last-Modified: '.gmdate('D, d M Y H:i:s \G\M\T', $lastmod));
        $this->contentlength = filesize($file);
        readfile($file);
        return;
    }

    public function output() {
        //ini_set('zlib.output_compression',1);
        $compress = false;
        if ($this->document) {
            $doc = $this->document->getContent();
            $this->contentlength = strlen($doc);
            if ($compress) {
                // Check if browser accepts gzip
                header("Content-Encoding: gzip");
                $doc = gzencode($doc,9,true);
            }
            echo $doc;
        }
    }
    
    public function getCachable() {
        return $this->cachable;
    }

    public function setCachable($state) {
        // Check if the document is cachable
        $this->cachable = $state;
        return false;
    }

    public function __toString() {
        return "{$this->status} - {$this->contentlength} bytes ({$this->contenttype})";
    }

}
