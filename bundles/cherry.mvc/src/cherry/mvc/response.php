<?php

namespace Cherry\Mvc;

use Cherry\Mvc\Document;
use Cherry\Cache\CacheObject;

class Response {

    private
            $document = null,
            $cachable = false;

    public function setDocument(Document $document) {
        $this->document = $document;
    }

    public function output() {
        //ini_set('zlib.output_compression',1);
        if ($this->document) {
            $doc = $this->document->getContent();
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

}
