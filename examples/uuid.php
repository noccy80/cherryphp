<?php

require getenv('CHERRY_LIB').'/lib/bootstrap.php';

use Cherry\Crypto\Uuid;

$uuid = Uuid::getInstance();

printf("v1: %s\n", $uuid->generate(Uuid::UUID_V1));
printf("v3: %s\n", $uuid->generate(Uuid::UUID_V3));
printf("v4: %s\n", $uuid->generate(Uuid::UUID_V4));
printf("v5: %s\n", $uuid->generate(Uuid::UUID_V5));

