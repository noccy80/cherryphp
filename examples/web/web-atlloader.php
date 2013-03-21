<?php

require_once "cherryphp";

use \Cherry\Web\Loaders\AtlLoader;

$ldr = new AtlLoader();
$ldr->load("test.atl", true);
