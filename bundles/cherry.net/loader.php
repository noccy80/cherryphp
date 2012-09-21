<?php

return array(
    'autoload' => array(
        'Cherry\Net\Socket\Socket',
        'Cherry\Net\Proxy\Proxy',
        'Cherry\Net\Socket\Transport\Transport'
    ),
    'depends' => array(
        'cherry.service'
    )
);
