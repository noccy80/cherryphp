<?php

require_once "../../share/include/cherryphp";

use Cherry\Data\Ddl\SdlTag;
use Cherry\Util\Timer;

// Create a new root node, we need this.
$root = new SdlTag("cyanogenmod");
$root->setComment("These are the latest three CyanogenMod versions with some info");

// You can create nodes and set attributes manually
$cm9 = new SdlTag("version", "9");
$cm9->multiuser = false;
$cm9->android = "4.0.x";
$cm9->setComment("Based on AOSP 4.0.x");
$root->addChild($cm9);

// Or provide them to the constructor
$cm10 = new SdlTag("version", "10", [
    'multiuser'=>false,
    'android'=>"4.1.x"
]);
$cm10->setComment("Based on AOSP 4.1.x");
$root->addChild($cm10);

// Comments can also go as the 5th argument
$cm101 = new SdlTag("version", "10.1", [
    'multiuser'=>true,
    'android'=>"4.2.x",
    'note'=>"New and hot!"
],null,"Based on AOSP 4.2.x");
$root->addChild($cm101);

$temp = new SdlTag("root");
$temp->addChild($root);

testspath($temp,"cyanogenmod/version[@multiuser=true]");
testspath($temp,"cyanogenmod/version[10]");
testspath($temp,"cyanogenmod/*");

function testspath($root,$expr) {
    echo "Expression: '{$expr}'\n";
    foreach($root->query($expr) as $child) {
        echo "  CM{$child[0]}: Based on Android AOSP {$child->android}.";
        if ($child->multiuser) echo " Multiple user-profiles supported.";
        echo "\n";
    }
}
