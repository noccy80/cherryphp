<?php

namespace Cherry\Mvc\Widgets;

use Cherry\Mvc\Widget;

class ComboBox extends Widget {

    public function __construct($id,$label,array $values,$default=null) {
        $this->init($id,array(
            'label' => 'string',
            'default' => 'string',

        ),array(
            'label' => $label,
            'default' => $default
        ));
    }


    public function render() {
        
    }
}
