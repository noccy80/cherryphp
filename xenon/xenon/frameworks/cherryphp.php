<?php

$path = getenv("CHERRY_LIB");
require_once $path . '/lib/bootstrap.php';
\Cherry\Base\PathResolver::getInstance()->setAppPath(XENON_ROOT);
