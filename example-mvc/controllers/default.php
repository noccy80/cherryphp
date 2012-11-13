<?php

namespace CherryTree\Controllers;

use Cherry\Mvc\Controller;
use Cherry\Mvc\Request;
use Cherry\Mvc\Response;
use Cherry\Mvc\Document;
use Cherry\Mvc\Html;

class DefaultController extends Controller {
    public function setup() {
        $doc = $this->document;
        // Meta headers, scripts and styles.
        $doc->setMeta('language','english');
        $doc->setMeta('noccylabs.sdip','sdip=1;hash=2;salt=2');
        $doc->setTitle('Untitled Document');
        $doc->addScript('//ajax.googleapis.com/ajax/libs/prototype/1.7.1.0/prototype.js');
        $doc->addScript('//ajax.googleapis.com/ajax/libs/scriptaculous/1.9.0/scriptaculous.js');
        $doc->addScript('/js/panel.js');
        $doc->addStyleSheet('//fonts.googleapis.com/css?family=Average+Sans|Sanchez');
        $doc->addStyleSheet('/css/main.css');
        $js = 'function yay() { alert("Wohoo!"); }';
        $doc->addInlineScript($js);
    }
    public function idAction($id = null) {

        echo "Displaying ID ".$id;

    }
    public function indexAction($sub1 = null, $sub2 = null) {

        $this->document->addInlineScript('console.log("foobar");');
        $this->document->view = new \Cherry\Mvc\View\Php(APP_ROOT.'/views/static.phtml');
        $this->document->view->setView('foot', new \Cherry\Mvc\View\StringView('Hello World'));

    }
    
    public function viewAction() {
        
        $this->document->setView( new \Cherry\Mvc\View\Php() );
        
    }
    
    public function templatedAction() {

        $this->document->setCacheControl('public,max-age=3600');
        $this->document->setCachePolicy('public');
        echo html::div(
            html::h1('Welcome to CherryPHP').
            html::div(
                html::p(
                    "Hello World! Click {link} for awesomeness! {span}",
                    [ 'class'=>'foo', 'style'=>'color:red;' ],
                    [
                        'link' => html::a('Here',[ 'href'=>'javascript:yay();' ]),
                        'span' => html::span('This is a text span.',[ 'style'=>'color:blue;' ]),
                    ]
                ).
                html::p(
                    "Arguments: 1:{first} 2:{second}", [],
                    [
                        'first' => $sub1,
                        'second' => $sub2
                    ]
                ).
                html::p(
                    "You just did a <strong>HTTP {method}</strong> to the URI {uri}<br>Your user agent is {ua}",
                    [],
                    [
                        'method' => $this->request->getMethod(),
                        'uri' => $this->request->getUri(),
                        'ua' => $this->request->getHeader('User-Agent')
                    ]
                )
            ).
            html::pre(print_r($_SERVER,true), [ 'style'=>'background-color:#F0F0F0; border:solid 1px #808080; padding:5px;' ]),
            [ 'id' => 'wrap' ]
        );

        
    }

    public function testAction(Document $doc) {

        echo html::p('Hello World from '.__CLASS__.':'.__FUNCTION__.'!');

    }

    public function debugAction(Document $doc) {

        echo html::h1('Debug Info');

        echo html::h2('$_SERVER');
        echo html::pre(print_r($_SERVER,true));

        echo html::h2('get_defined_constants()');
        echo html::pre(print_r(get_defined_constants(),true));
    }

}
