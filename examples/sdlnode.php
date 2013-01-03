<?php

require getenv('CHERRY_LIB').'/lib/bootstrap.php';

use Cherry\Data\Ddl\SdlNode;

// Create a new root node, we need this.
$root = new SdlNode("cyanogenmod");
$root->setComment("These are the latest three CyanogenMod versions with some info");

// You can create nodes and set attributes manually
$cm9 = new SdlNode("version", "9");
$cm9->multiuser = false;
$cm9->android = "4.0.x";
$cm9->setComment("Based on AOSP 4.0.x");
$root->addChild($cm9);

// Or provide them to the constructor
$cm10 = new SdlNode("version", "10", [
    'multiuser'=>false,
    'android'=>"4.1.x"
]);
$cm10->setComment("Based on AOSP 4.1.x");
$root->addChild($cm10);

// Comments can also go as the 5th argument
$cm101 = new SdlNode("version", "10.1", [
    'multiuser'=>true,
    'android'=>"4.2.x",
    'note'=>"New and hot!"
],null,"Based on AOSP 4.2.x");
$root->addChild($cm101);

// And the data can be serialized to SDL
echo $root->encode();
