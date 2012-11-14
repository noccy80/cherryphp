<?php

namespace CherryTree\Widgets;

use Cherry\Mvc\Html;

class AboutCherry {
    
    function init() {
        
    }
    
    function render() {
        
        return join([
        
        '<div>Powered by <strong>CherryTree</strong> and <strong>PHP '.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'</strong></div>',
        '<div><em>OpenSource Inside&trade;</em></div>'
        
        ]);
        
    }
    
}