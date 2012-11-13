<?php

namespace CherryTree\Widgets;

use Cherry\Mvc\Html;

class Sidebar {
    
    function render() {
        
        $boxes = [];
        $nav =  html::li(html::a('First option', ['href'=>'#'] )).
                html::li(html::a('Second option', ['href'=>'#'] ));

        $boxes[] = html::div(
            html::div('Navigation',[ 'style'=>'font-weight:bold;' ]).
            html::ul($nav),
            [
                'class'=>'sidebar-box'
            ]
        );

        $nav =  html::li(html::a('First option', ['href'=>'#'] )).
                html::li(html::a('Second option', ['href'=>'#'] )).
                html::li(html::a('Third option', ['href'=>'#'] )).
                html::li(html::a('Fourth option', ['href'=>'#'] ));

        $boxes[] = html::div(
            html::div('Navigation',[ 'style'=>'font-weight:bold;' ]).
            html::ul($nav),
            [
                'class'=>'sidebar-box'
            ]
        );
        
        return join($boxes);
        
    }
    
}