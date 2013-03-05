<?php

namespace Cherry\Expm\Cwt;

use \Cherry\Types\Queue\FifoQueue;
use \Cherry\Expm\Components as c;

class CwtApplication extends \Cherry\Cli\ConsoleApplication {

    const CWT_AS_UNINITIALIZED = 0; ///< @var Application state is not initialized
    const CWT_AS_RUNNING = 1; ///< @var Application state is running
    const CWT_AS_EXCEPTION = 2; ///< @var Exception occured

    public $cwtApplicationState = self::CWT_AS_UNINITIALIZED;
    private $cwtEventQueue;
    private $cwtDesktop = null;

    /**
     * The desktop widget will always be the size of the actual console window.
     * This widget will receive the onResize event, and it is it's reponsibility
     * to pass it on to the child widgets.
     *
     * @param Widget\Widget $widget The widget to render as desktop
     */
    public function setDesktop(Widget\Widget $widget) {
        $this->cwtDesktop = $widget;
    }

    public function runDesktop() {
        c::set("cwt:messagequeue", new FifoQueue());
        $running = true;
        while($running) {
            $this->cwtDesktop->onTick();
            $this->cwtDesktop->onDraw();
            $time = date(\DateTime::RFC822);
            \Cherry\Expm\Cwt\Context::getInstance()->textAt(2,2,"Hello World! {$time}");
            ncurses_refresh();
            \usleep(10000);
        }

    }

}
