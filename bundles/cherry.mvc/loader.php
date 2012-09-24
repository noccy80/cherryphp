<?php

return array(
    'autoload' => array(
        'Cherry\Mvc\Application',
        'Cherry\Mvc\Session',
        'Cherry\Mvc\Response',
        'Cherry\Mvc\Request',
        'Cherry\Mvc\Cookies'
    ),
    'depends' => array(
        'cherry.db'
    )
);
