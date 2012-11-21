<?php

namespace CherryTree\Widgets;

use Cherry\Mvc\Html;
use Cherry\Mvc\Widget;

class Sidebar extends Widget {

    function init() {

        $this->setRefreshTimer(10);

    }

    function render() {

        $boxes = [];

        $boxes[] = html::div(
            [
                html::div('CherryTree',[ 'style'=>'font-weight:bold;' ]).
                html::ul([
                    html::li(html::a('Why CherryTree?', ['href'=>'#'] )),
                    html::li(html::a('Features', ['href'=>'#'] )),
                    html::li(html::a('CherryTree on GitHub', ['href'=>'#'] )),
                ])
            ],[
                'class'=>'sidebar-box'
            ]
        );

        $boxes[] = html::div(
            [
                html::div('Navigation',[ 'style'=>'font-weight:bold;' ]).
                html::ul([
                    html::li(html::a('First option', ['href'=>'#'] )),
                    html::li(html::a('Second option', ['href'=>'#'] )),
                    html::li(html::a('Third option', ['href'=>'#'] )),
                    html::li(html::a('Fourth option', ['href'=>'#'] ))
                ])
            ],[
                'class'=>'sidebar-box'
            ]
        );

        return join($boxes);

    }

}
