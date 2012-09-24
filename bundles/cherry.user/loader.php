<?php
namespace Cherry\User;
use Cherry\Base\Event;

Event::observe(\Cherry\Mvc\EventsEnum::REQUEST_CREATE, function(\Cherry\Mvc\Request $request) {
    
    $request->user = \Cherry\User::getActiveUser();
    
});

return array(
    'autoload' => array(
        'Cherry\User\Authentication'
    )
);
