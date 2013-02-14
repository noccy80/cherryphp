<?php

// New style xenon framework bootstrapping
require_once "xenon/xenon.php";
xenon\frameworks\cherryphp::bootstrap();

// Use the thumbalizr generator
use Cherry\Graphics\Generators\Thumbalizr;

// Create a new thumbalizer with the URL and width. Max width is 300, see api.thumbalizr.com
$ti = new Thumbalizr("http://www.google.com",250);

// Print some information
echo "Got thumbnail: {$ti->width}x{$ti->height} pixels, truecolor={$ti->truecolor}, mime={$ti->mimetype}\n";

// Save the image
$ti->save("thumbalizr.jpg");
