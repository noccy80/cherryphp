<?php

require getenv('CHERRY_LIB').'/lib/bootstrap.php';

use Cherry\Data\Ddl\SdlNode;
use Cherry\Util\Timer;

// This is a node with base64-encoded data.
$sdl = <<<EOT
testnode [SGVsbG8gV29ybGQh];
EOT;

// Read it back out again
$test = new SdlNode("root");
$test->loadString($sdl);

$node = new SdlNode("testnode2");
$node->setValue("Putting binary data in the SDL node", 0, SdlNode::LT_BINARY);
$test->addChild($node);

echo "Current state of SDL tree:\n";

echo $test->encode()."\n";

echo "Node values:\n";
echo "  testnode=".$test->getChild("testnode")[0]."\n";
echo "  testnode2=".$test->getChild("testnode2")[0]."\n";
