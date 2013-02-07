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
        $this->assertEquals("foo",$node[0]);
        $this->assertEquals("bar",$node[1]);
        $this->assertEquals("foo",$node->getValue(0));
        $this->assertEquals("bar",$node->getValue(1));
    }
    
    public function testCreateTree() {
        $node1 = new SdlNode("root");
        $node2 = new SdlNode("subnode","foo");
        $node1->addChild($node2);
        $this->assertTrue($node1->hasChildren());
        $this->assertInstanceOf('\Cherry\Data\Ddl\SdlNode',$node1->getChild("subnode"));
        $cl = $node1->getChildren("subnode");
        $this->assertEquals(1,count($cl));
        $this->assertInstanceOf('\Cherry\Data\Ddl\SdlNode',$cl[0]);
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
    
    /**
     * @expectedException \Cherry\Data\Ddl\SdlParseException
     */
    public function testBinaryExceptions() {
        $test = new SdlNode("test");
        $test->loadString("[[");
    }

        /**
     * @expectedException \Cherry\Data\Ddl\SdlParseException
     */
    public function testBinaryExceptionsTwo() {
        $test = new SdlNode("test");
        $test->loadString("]]");
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
        $this->assertEquals(trim($sdl),trim($enc),"Encoded data after adding boolean values does not match expected data");
        
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
    
    public function testUnknownValueType() {
        $method = new ReflectionMethod(
          '\Cherry\Data\Ddl\SdlNode', 'getCastValue'
        );
        $method->setAccessible(true);
 
        $this->assertEquals('string', $method->invoke(new SdlNode, [ "string", -1 ]));        
    }

    public function testTypedValue() {
        $method = new ReflectionMethod(
          '\Cherry\Data\Ddl\SdlNode', 'getTypedValue'
        );
        $method->setAccessible(true);
        $value = null;
        $ret = $method->invokeArgs(new SdlNode, ["foo", T_STRING, &$value]);
        $this->assertEquals(["foo",1],$value);
        $this->assertEquals(true, $ret);
        
    }

    public function testEscape() {
        $method = new ReflectionMethod(
          '\Cherry\Data\Ddl\SdlNode', 'escape'
        );
        $method->setAccessible(true);
 
        $this->assertEquals('false', $method->invoke(new SdlNode, false));   
        $this->assertEquals('true', $method->invoke(new SdlNode, true));   
        $this->assertEquals('null', $method->invoke(new SdlNode, null));   
        $this->assertEquals('8192', $method->invoke(new SdlNode, 8192));   
        $this->assertEquals('3.14', $method->invoke(new SdlNode, 3.14));   
        $this->assertEquals("\"Hello\"", $method->invoke(new SdlNode, "Hello"));   
    }

    public function testNumericValues() {

        $sdl = "root 1 2 3.14\n";

        $test = new SdlNode("root");

        $test->addValue(1);
        $test->addValue(2);
        $test->addValue(3.14);
        $this->assertEquals(1,$test[0]);
        $this->assertEquals(2,$test[1]);
        $this->assertEquals(3.14,$test[2]);
        
        $enc = $test->encode();
        $this->assertEquals(trim($sdl),trim($enc),"Encoded data after adding integer values does not match expected data");
        
    }
    
    public function testNumericValueLists() {
        $sdl = "root { 1 2 3 4 5 6 }";
        $test = new SdlNode("root");
        $test->loadString($sdl);
        $this->assertEquals(1,count($test->getChildren()));
    }
    
    public function testValues() {
        
        $test = new SdlNode("root");
        $this->assertEquals(0, count($test));
        $test->addValue(0);
        $this->assertEquals(1, count($test));
        $test->addValue("Hello");
        $this->assertEquals(2, count($test));
        $this->assertEquals(null, $test[99]);
        
    }
    
    public function testAttributes() {
        
        $test = new SdlNode("root");
        $test->setAttribute("name","bob");
        $this->assertEquals("bob",$test->name);
        $test->name = "joe";
        $this->assertEquals("joe",$test->name);
        unset($test->name);
        $this->assertEquals(null,$test->name);
        $test->loadString("pet name=\"bobo\" type=\"dog\" alive=true age=14");
        $this->assertEquals("dog", $test->getChild("pet")->type, "String ttribute don't match parsed value");
        $this->assertEquals(true, $test->getChild("pet")->alive, "Bool attribute don't match parsed value");
        $this->assertEquals(14, $test->getChild("pet")->age, "Numeric attribute don't match parsed value");
        $this->assertEquals("bobo", $test->getChild("pet")->name, "String attribute don't match parsed value");

        $attr = $test->getChild("pet")->getAttributes();
        $match = [
            'name' => 'bobo',
            'type' => 'dog',
            'age' => 14,
            'alive' => true
        ];
        $this->assertEquals($match,$attr,"Unexpected attribute set returned");

    }
    
    public function testComments() {
        
        $test = new SdlNode("commented");
        $comment = "This is a comment";
        $test->setComment($comment);
        $this->assertEquals($comment,$test->getComment());
        
    }
    
    public function testNamespacesWithName()  {
        
        $test = new SdlNode("foo:test");
        $this->assertEquals("test",$test->getName());
        $this->assertEquals("foo",$test->getNamespace());
        $this->assertEquals("foo:test",$test->getNameNs());
        
        $test->setNamespace("bar");
        $this->assertEquals("bar",$test->getNamespace());
        $this->assertEquals("bar:test",$test->getNameNs());

        $test->setName("arf");
        $this->assertEquals("bar",$test->getNamespace());
        $this->assertEquals("bar:arf",$test->getNameNs());

        $test->setNamespace(null);
        $this->assertEquals(null,$test->getNamespace());
        $this->assertEquals(":arf",$test->getNameNs());
        
    }
    
}
