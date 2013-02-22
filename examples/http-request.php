<?php

require_once "xenon/xenon.php";
xenon\frameworks\cherryphp::bootstrap(__DIR__);


$h = new \Cherry\Web\Request();

echo "<p>User-agent: ".$h['user-agent']."</p>";
echo $h->asHtml();

var_dump(headers_list());

http_redirect("http://google.com");
