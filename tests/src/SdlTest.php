<?php

require_once "PHPUnit/Autoload.php";
// use Cherry\CherryUnit\TestCase;

//require_once "lib/cherry/data/ddl/sdlnode.php";

use Cherry\Data\Ddl\SdlNode;

class SdlTestCase extends \PHPUnit_Framework_TestCase {

    private $node = null;

    public function setup() { }
    public function teardown() { }
    
    public function testCreateContent() {
        $node = new SdlNode();
        $this->assertTrue($node instanceof SdlNode);
    }
    
    public function testCreateNamed() {
        $node = new SdlNode("root");
        $this->assertTrue($node instanceof SdlNode);
    }
    
    public function testCreateValue() {
        $node = new SdlNode(null, "foo");
        $this->assertTrue($node instanceof SdlNode);
        $this->assertEquals(count($node->getValues()), 1);
    }

    public function testCreateValues() {
        $node = new SdlNode(null,[ "foo", "bar" ]);
        $this->assertTrue($node instanceof SdlNode);
        $this->assertEquals(count($node->getValues()), 2);
        $this->assertEquals($node[0],"foo");
        $this->assertEquals($node[1],"bar");
    }
    
    public function testCreateTree() {
        $node1 = new SdlNode("root");
        $node2 = new SdlNode("subnode","foo");
        $node1->addChild($node2);
        $this->node = $node1;
    }
    
    public function testSerialize() {
        $node1 = new SdlNode("root");
        $node2 = new SdlNode("subnode","foo");
        $node1->addChild($node2);
        $ser = $node1->encode();
        $this->assertTrue(is_string($ser));
        $node = new SdlNode("root");
        $node->loadString($ser);
        $this->assertTrue($node->getChild("root") != null);
        $sn = $node->getChild("root")->getChild("subnode");
        $this->assertTrue($node->getChild("root")->getChild("subnode") != null);
    }
    
    public function testReadFile() {
        $fnam = tempnam("/tmp","phpunit");
        $sdl =
<<<EOT
// tag comment
sdl:tag {
    // foo comment
    foo "value1" "value2" "value3" attr1="string" attr2=true attr3=null
    // bar comment
    bar "value1"
    // baz comment
    baz attr="value"
}
EOT;
        file_put_contents($fnam,$sdl);
        $node = new SdlNode("root");
        $node->loadFile($fnam);
        $enc = $node->getChild(0)->encode();
        $this->assertEquals(trim($sdl),trim($enc),"Encoded data does not match decoded data");
    }
    
    public function testReadBinaryFile() {
        // This is a node with base64-encoded data.
        $sdl =
<<<EOT
testnode [SGVsbG8gV29ybGQh];
EOT;
        $sdl3 =
<<<EOT
root {
    testnode [SGVsbG8gV29ybGQh]
}
EOT;
        $sdl2 =
<<<EOT
root {
    testnode [SGVsbG8gV29ybGQh]
    testnode2 [UHV0dGluZyBiaW5hcnkgZGF0YSBpbiB0aGUgU0RMIG5vZGU=]
}
EOT;
        
        // Read it back out again
        $test = new SdlNode("root");
        $test->loadString($sdl);
        $enc = $test->encode();
        $this->assertEquals(trim($sdl3),trim($enc),"Encoded data does not match decoded data");

    }
    
    public function testBinaryData() {

        // This is a node with base64-encoded data.
        $sdl =
<<<EOT
testnode [SGVsbG8gV29ybGQh];
EOT;
        $sdl3 =
<<<EOT
root {
    testnode [SGVsbG8gV29ybGQh]
}
EOT;
        $sdl2 =
<<<EOT
root {
    testnode [SGVsbG8gV29ybGQh]
    testnode2 [UHV0dGluZyBiaW5hcnkgZGF0YSBpbiB0aGUgU0RMIG5vZGU=]
}
EOT;

        // Read it back out again
        $test = new SdlNode("root");
        $test->loadString($sdl);
        $enc = $test->encode();
        $this->assertEquals(trim($sdl3),trim($enc),"Encoded data does not match decoded data");

        $node = new SdlNode("testnode2");
        $testvalue = "Putting binary data in the SDL node";
        $node->setValue($testvalue, 0, SdlNode::LT_BINARY);
        $test->addChild($node);
        $this->assertEquals($testvalue,$node[0],"Set binary string does not match retrieved binary string");
        
        $enc2 = $test->encode();
        $this->assertEquals(trim($sdl2),trim($enc2),"Encoded data after adding binary data does not match expected data");
        
    }

    public function testBooleanValues() {

        $sdl = "root false false\n";

        // Read it back out again
        $test = new SdlNode("root");

        $test->setValue(true);
        $test->setValue("yes",1,SdlNode::LT_BOOLEAN);
        $this->assertEquals(true,$test[0],"Boolean value does not match");
        $this->assertEquals(true,$test[1],"Boolean value does not match");

        $test->setValue(false);
        $test->setValue("no",1,SdlNode::LT_BOOLEAN);
        $this->assertEquals(false,$test[0],"Boolean value does not match");
        $this->assertEquals(false,$test[1],"Boolean value does not match");
        
        $enc = $test->encode();
        $this->assertEquals(trim($sdl),trim($enc),"Encoded data after adding noolean values does not match expected data");
        
    }

    public function testNullValues() {

        $sdl = "root null\n";

        // Read it back out again
        $test = new SdlNode("root");

        $test->setValue(null);
        $this->assertEquals(null,$test[0],"Null value does not match?!");
        
        $enc = $test->encode();
        $this->assertEquals(trim($sdl),trim($enc),"Encoded data after adding null values does not match expected data");
        
    }

    public function testNumericValues() {

        $sdl = "root 1 2 3.14\n";

        $test = new SdlNode("root");

        $test->addValue(1);
        $test->addValue(2);
        $test->addValue(3.14);
        $this->assertEquals(1,$test[0],"First value in tag is not 1");
        
        $enc = $test->encode();
        $this->assertEquals(trim($sdl),trim($enc),"Encoded data after adding integer values does not match expected data");
        
    }
    
}

//TestCase::register('SdlTestCase');
