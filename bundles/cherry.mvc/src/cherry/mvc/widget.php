<?php

namespace Cherry\Mvc;

use App;

abstract class Widget {

    const
            SCOPE_REQUEST   = 'request',    /// Only live through the request
            SCOPE_SESSION   = 'session',    /// Live in the user session
            SCOPE_USER      = 'user',       /// Attach to the user
            SCOPE_GLOBAL    = 'global';     /// Global, shared between requests


    private
            $options = [
                'scope' => self::SCOPE_REQUEST
            ]
    ;

    public static function getWidget($id, array $options = null) {
        switch($this->options['scope']) {
            case self::SCOPE_GLOBAL:
                // Save to appcontext
                break;
            case self::SCOPE_SESSION:
                // Check session for object
                if (App::session()->hasKey('cherrytree.widgets.state',$id)) {
                    $obj = unserialize(App::session()->getKey('cherrytree.widgets.state',$id));
                    if (!is_object($obj))
                        $obj = new self($id, $options);
                }
                break;
            case self::SCOPE_USER:
                // Check user record for widget data, if it exists
                // load and unserialize it.
                break;
            case self::SCOPE_REQUEST;
                $obj = new self($id, $options);
                break;
        }

    }

    public function __construct($id, array $options = null) {
        $this->options = array_merge($this->options, $options);
    }

    public function __destruct() {
        $state = serialize($this);
        switch($this->options['scope']) {
            case self::SCOPE_GLOBAL:
                // Save to appcontext
                break;
            case self::SCOPE_SESSION:
                // Save to session
                break;
            case self::SCOPE_USER:
                // Save to user record
                break;
        }
    }

    protected function setRefreshTimer($seconds) {
        $this->refreshtimer = $seconds;
    }

    abstract public function init();

    abstract public function render();

    public function __sleep() {
        return [];
    }

    public function __wakeup() {

    }

}
