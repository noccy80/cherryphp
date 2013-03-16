#!/usr/bin/php
<?php

require_once "../../share/include/cherryphp";

use Cherry\Graphics\Canvas;

if (!file_exists("image.png"))
    die("Make sure a file named image.png is in the same directory.\n");

$c = new Canvas();
$c->load('image.png');
$c2 = clone $c;

$c->resize(1024,1024);
$c->save('image1.png');
$c2->resize(1024,1024,true);
$c2->save('image2.png');
