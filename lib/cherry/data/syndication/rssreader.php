<?php

namespace Cherry\Data\Syndication;

use \Cherry\Net\Http\HttpRequest;
use \Cherry\Cache\CacheObject;

class RssReader {

    private $_title;
    private $_link;
    private $_description;
    private $_channel = [];

    public function __construct() {
    }

    public function loadUrl($url,array $options=null) {
        $co = new CacheObject($url, CacheObject::CO_COMPRESS | CacheObject::CO_USE_DISK, function() use ($url) {
            $http = new HttpRequest();
            $http->open("GET", $url);
            \debug("RssReader: Loading RSS from {$url}");
            if ($http->send()) {
                return($http->getResponseText());
            }
        });
        $this->loadString( $co->getContent() );
    }

    public function loadString($string) {
        $root = new \SimpleXMLElement($string);
        // Check if this is a valid RSS document
        if ($root->getName() != "rss")
            throw new RssReaderException("Not an RSS feed");
        // Validate the version
        $version = $root['version'];
        if (!in_array($version,["0.91", "0.92", "2.0"]))
            throw new RssReaderException("Unsupported RSS version {$version}");
        // Parse the metadata
        $channel = $root->channel;
        $this->_title = $channel->title;
        $this->_link = $channel->link;
        $this->_description = $channel->description;
        \debug("RssReader: Parsing RSS {$version} feed '{$this->_title}'");
        foreach($channel->xpath("item") as $nd) {
            if ($nd->getName() == 'item') {
                $this->pushItem($nd);
            }
        }
    }

    private function pushItem(\SimpleXMLElement $item) {
        $item = (object)[
            'title' => (string)$item->title?:'No title',
            'link' => (string)$item->link,
            'description' => (string)$item->description,
            'pubDate' => strtotime($item->pubDate),
            'guid' => (string)$item->guid?:(string)$item->link,
            'author' => (string)$item->author
        ];
        \debug("RssReader: Found item '{$item->title}' ({$item->guid})");
        $this->channel[] = $item;
    }

}

class RssReaderException extends \Exception {}
