<?php

define("XENON", "cherryphp/trunk");
require("xenon/xenon.php");

use Cherry\Cli\ConsoleApplication;
use Cherry\Web\HtmlTag as h;
use Cherry\Web\HtmlDocument;

class HelloCherry extends ConsoleApplication {

    public function main() {
        // Create a simple document and set the title
        $doc = new HtmlDocument();
        $doc->setTitle("Oh hello!");
        $doc->stylesheets->addLink("/css/main.css");

        // Write to the document using the htmltag helper
        $doc(h::p("Hello World!")->setStyle('font-weight:bold'));
        $doc(h::div(
                h::input()->makecheckbox(),
                h::input()->makecheckbox()->disabled()
            )->setStyle('border:solid 1px red; background-color:#fee;'));

        // And finally output it
        $doc->output(true);
    }

}

App::run(new HelloCherry());
