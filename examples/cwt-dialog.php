<?php

require_once "xenon/xenon.php";
xenon\xenon::framework("cherryphp");

use Cherry\Expm\Cwt\CwtApplication;
use Cherry\Expm\Cwt\Widget\Widget;
use Cherry\Expm\Cwt\Widget\Dialog;
use Cherry\Expm\Cwt\Widget\Desktop;
use Cherry\Expm\Cwt\Widget\TextArea;
use Cherry\Expm\Cwt\Widget\Button;
use Cherry\Expm\Cwt\Widget\ButtonBar;

class CwtDesktop extends Desktop {
    public function onCreate() {
        $this->addWindow(new CwtHelloDialog());
        parent::onCreate();
    }
}

class CwtHelloDialog extends Dialog {
    public function onCreate() {
        // Create the textarea
        $label = new TextArea("label");
        $label->label = "Hello World";
        // Create the button with a label
        $btnok = new Button("btnok");
        $btnok->label = "Hi yourself";
        // Create a button bar and add the button
        $bar = new ButtonBar("buttons");
        $bar->align = Widget::ALIGN_CENTER;
        $bar->pushWidget($btnok);
        // Push the widgets
        $this->pushWidget($label,$bar);
        parent::onCreate();
    }
    public function onDestroy() {

    }
    public function onMeasure() {
        return [ 50, 16 ];
    }
}

class CwtHelloApp extends CwtApplication {

    public function main() {

        $desk = new CwtDesktop();
        $this->setDesktop($desk);
        $this->runDesktop();

    }


}

\App::run(new CwtHelloApp());
