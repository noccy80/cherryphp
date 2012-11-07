<?php

namespace ExampleSite\Controllers;

use Cherry\Mvc\Request;
use Cherry\Mvc\Response;
use Cherry\Mvc\Document;
use Cherry\Mvc\Html;

abstract class Controller {
    protected
            $request = null,
            $response = null;

    public function __construct(Request $request, Response $response) {
        $this->request = $request;
        $this->response = $response;
    }
    public function invoke($action,$args) {
        // Begin the document, and assign it as the response document
        $doc = Document::begin(Document::DT_HTML5,'en-us','UTF-8');
        $this->response->setDocument($doc);
        $this->setup($doc);
        call_user_func_array([$this,$action.'Action'], array_merge([$doc],$args));
        //$this->indexAction($doc);
        // Output the document
        $this->response->output();
    }
    abstract function setup(Document $doc);
    protected function show_404() {

    }
}

class DefaultController extends Controller {
    public function setup(Document $doc) {
        // Meta headers, scripts and styles.
        $doc->setMeta('language','english');
        $doc->setMeta('noccylabs.sdip','sdip=1;hash=2;salt=2');
        $doc->setTitle('Untitled Document');
        $doc->addScript('/js/prototype.js','text/javascript');
        $doc->addStyleSheet('/css/main.css');
        $js = 'function yay() { alert("Wohoo!"); }';
        $doc->addInlineScript($js);
    }
    public function viewAction(Document $doc, $id = null) {

        echo "Displaying ID ".$id;

    }
    public function indexAction(Document $doc, $sub1 = null, $sub2 = null) {

        echo html::h1('Welcome to CherryPHP');
        echo html::div(
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
            ),
            [ 'class'=>'box' ]
        );

        echo html::pre(print_r($_SERVER,true), [ 'style'=>'background-color:#F0F0F0; border:solid 1px #808080; padding:5px;' ]);

    }

    public function debugAction(Document $doc) {

        echo html::h1('Debug Info');

        echo html::h2('$_SERVER');
        echo html::pre(print_r($_SERVER,true));

        echo html::h2('get_defined_constants()');
        echo html::pre(print_r(get_defined_constants(),true));
    }

}
