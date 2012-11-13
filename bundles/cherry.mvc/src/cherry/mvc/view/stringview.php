<?php
// !status; might be renamed
// !stability; unstable

namespace Cherry\Mvc\View;

use Cherry\Mvc\View;
use Cherry\Base\Event;

class StringView extends View {

    private
            $view = null,
            $content = null;

    public function render($return=false) {
        if ($return)
            return $this->content;
        else
            echo $this->content;
    }
    
    public function __construct($content = null,array $options = null) {
        parent::__construct();
        // Constructor
        $this->content = $content;
    }

}
