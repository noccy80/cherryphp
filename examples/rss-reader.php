<?php

require_once "xenon/xenon.php";
xenon\frameworks\cherryphp::bootstrap(__DIR__);

use Cherry\Data\Syndication\RssReader;

$rss = new RssReader();
//$rss->loadString( file_get_contents("feed2.xml") );
$rss->loadUrl("http://www.metro.se/rss.xml");

foreach($rss as $item) {
    //var_dump($item);
}
