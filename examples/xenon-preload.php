<?php

require_once("xenon/xenon.php");
xenon\xenon::config("framework.preload",[ '\Cherry\Graphics\Canvas' ]);
xenon\xenon::framework("cherryphp");

echo "Was the canvas class pre-loaded? ";
echo (class_exists('\Cherry\Graphics\Canvas',false)?'Yes':'No')."\n";
