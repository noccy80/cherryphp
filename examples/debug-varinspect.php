<?php

define("XENON","cherryphp/trunk");
require("xenon/xenon.php");

echo var_inspect(new StdClass())."\n";
echo var_inspect("Hello World")."\n";
echo var_inspect(177.944)."\n";
echo var_inspect([ 'hello', 'world' ])."\n";
echo var_inspect(get_declared_classes())."\n";
echo var_inspect(true)."\n";
echo var_inspect(null)."\n";
