<?php

require getenv('CHERRY_LIB').'/lib/bootstrap.php';

use Cherry\Data\Ddl\SdlTag;
use Cherry\Util\Timer;

// This is a node with base64-encoded data.
$sdl = <<<EOT
testnode [SGVsbG8gV29ybGQh];
EOT;

// Read it back out again
$test = new SdlTag("root");
$test->loadString($sdl);

$node = new SdlTag("testnode2");
$node->setValue("Putting binary data in the SDL node", 0, SdlTag::LT_BINARY);
$test->addChild($node);

echo "Current state of SDL tree:\n";

echo $test->encode()."\n";

echo "Node values:\n";
echo "  testnode=".$test->getChild("testnode")[0]."\n";
echo "  testnode2=".$test->getChild("testnode2")[0]."\n";
