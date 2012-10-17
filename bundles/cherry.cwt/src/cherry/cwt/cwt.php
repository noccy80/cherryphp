<?php

namespace Cherry\Cwt;

use Cherry\Base\EventEmitter;
use Cherry\Cwt\Widgets\Widget;

class Cwt extends EventEmitter {

    const ON_RESIZE = 'cwt:resize';
    const ON_STARTUP = 'cwt:startup';
    const ON_AFTER_DRAW = 'cwt:draw.after';
    const ON_BEFORE_DRAW = 'cwt:draw.before';
    const ON_DEBUG = 'cwt:debug';

    private static $cwt;
    private $desktop = null;
    private $fps = 20;
    private $fpin = null;
    private $running = false;
    private $buffer = null;

    function __construct() {
        if (!self::$cwt) self::$cwt = $this;
        $this->buffer = new \Data\FifoQueue(50);
        ncurses_init();
        if (ncurses_has_colors()) {
            ncurses_start_color();
            ncurses_init_pair(1, NCURSES_COLOR_WHITE, NCURSES_COLOR_BLUE);
            ncurses_init_pair(2, NCURSES_COLOR_BLACK, NCURSES_COLOR_BLUE);
        }
        ncurses_curs_set(0);
        ncurses_noecho();
        ncurses_mousemask(NCURSES_ALL_MOUSE_EVENTS);
        $oldmask = null;
        $newmask = NCURSES_BUTTON1_CLICKED | NCURSES_BUTTON1_RELEASED | NCURSES_BUTTON1_PRESSED |
                    NCURSES_BUTTON2_CLICKED | NCURSES_BUTTON2_RELEASED | NCURSES_BUTTON1_PRESSED |
                    NCURSES_BUTTON3_CLICKED | NCURSES_BUTTON3_RELEASED | NCURSES_BUTTON1_PRESSED |
                    NCURSES_BUTTON4_CLICKED | NCURSES_BUTTON4_RELEASED | NCURSES_BUTTON1_PRESSED;
        $mask = ncurses_mousemask($newmask, &$oldmask);
        $this->fpin = fopen("php://stdin","r");     //open direct input stream for reading
        stream_set_blocking($this->fpin,0);        //set non-blocking mode

    }

    public function debug() {
        $args = func_get_args();
        $msg = call_user_func_array('sprintf',$args);
        $this->buffer->push($msg);
        $this->emit(Cwt::ON_DEBUG,$msg);
    }

    public function getDebug() {
        return $this->buffer->peek();
    }

    public static function cwt() {
        if (!self::$cwt) self::$cwt = new Cwt();
        return self::$cwt;
    }

    function __destruct() {
        ncurses_end();
        fclose($this->fpin);
    }

    function setDesktop(Widget $foo) {
        $this->desktop = $foo;
    }

    function desktop() {
        return $this->desktop;
    }

    function running() {
        return $this->running;
    }

    function setRefreshRate($fps) {
        $this->fps = $fps;
    }
    function getRefreshRate() {
        return $this->fps;
    }

    function quit() {
        $this->running = false;
    }

    function run() {

        $this->running = true;
        while($this->running) {
            $t1 = microtime(true);
            $this->draw();
            $this->handleinput();
            $dp = 1000/$this->fps;
            $t2 = microtime(true);
            $ds = $dp - ($t2 - $t1);
            if ($ds > 0)
                usleep($ds*1000);
        }


    }

    function handleinput() {

        static $lastchars = array();

        do {
            $ch = ncurses_getch();

            if ($ch == NCURSES_KEY_MOUSE) {
                if (ncurses_getmouse($mevent)){
                    $mx = $mevent["x"]; // Save mouse position
                    $my = $mevent["y"];
                    $ctl = $this->desktop->hitTest($mx,$my);
                    $mtd = null;
                    if ($ctl) {
                        if ($mevent["mmask"] & NCURSES_BUTTON1_PRESSED) {
                            $mtd = 'onMouseDown';
                            $arg = array(1,$mx,$my);
                        } elseif ($mevent["mmask"] & NCURSES_BUTTON2_PRESSED) {
                            $mtd = 'onMouseDown';
                            $arg = array(2,$mx,$my);
                        } elseif ($mevent["mmask"] & NCURSES_BUTTON3_PRESSED) {
                            $mtd = 'onMouseDown';
                            $arg = array(3,$mx,$my);
                        } elseif ($mevent["mmask"] & NCURSES_BUTTON4_PRESSED) {
                            $mtd = 'onMouseDown';
                            $arg = array(4,$mx,$my);
                        } elseif ($mevent["mmask"] & NCURSES_BUTTON1_RELEASED) {
                            $mtd = 'onMouseUp';
                            $arg = array(1,$mx,$my);
                        } elseif ($mevent["mmask"] & NCURSES_BUTTON2_RELEASED) {
                            $mtd = 'onMouseUp';
                            $arg = array(2,$mx,$my);
                        } elseif ($mevent["mmask"] & NCURSES_BUTTON3_RELEASED) {
                            $mtd = 'onMouseUp';
                            $arg = array(3,$mx,$my);
                        } elseif ($mevent["mmask"] & NCURSES_BUTTON4_RELEASED) {
                            $mtd = 'onMouseUp';
                            $arg = array(4,$mx,$my);
                        } elseif ($mevent["mmask"] & NCURSES_BUTTON1_CLICKED) {
                            $mtd = 'onClick';
                            $arg = array(1,$mx,$my);
                        } elseif ($mevent["mmask"] & NCURSES_BUTTON2_CLICKED) {
                            $mtd = 'onClick';
                            $arg = array(2,$mx,$my);
                        } elseif ($mevent["mmask"] & NCURSES_BUTTON3_CLICKED) {
                            $mtd = 'onClick';
                            $arg = array(3,$mx,$my);
                        } elseif ($mevent["mmask"] & NCURSES_BUTTON4_CLICKED) {
                            $mtd = 'onClick';
                            $arg = array(4,$mx,$my);
                        }
                        if (($mtd) && (is_callable(array(&$ctl,$mtd)))) {
                            call_user_func_array(array(&$ctl,$mtd),$arg);
                        }
                    }
                    Cwt::cwt()->debug('Event at %3dx%3d. Hittest: %5s. Mtd:%-25s', $mx, $my, !empty($ctl)?'Yes':'No',$mtd);
                }
            }
            ncurses_mvaddstr(5,5,"Char: ".sprintf('%02x',$ch)."             ");
            if ($ch>=0) {
                array_unshift($lastchars, sprintf('%02x',$ch));
                ncurses_mvaddstr(6,5,"Last: ".join(', ',$lastchars)."             ");
                $lastchars = array_slice($lastchars,0,6);
            }
        } while ($ch != -1);

        ncurses_refresh();
        if ($ch == ' ')
            $this->quit();

    }

    function draw() {
        static $lx, $ly;
        $cx = null;
        $cy = null;
        ncurses_getmaxyx(STDSCR, $cx, $cy);
        if (($cx != $lx) || ($cy != $ly)) {
            $this->emit(Cwt::ON_RESIZE,array('x' => $cx, 'y' => $cy));
            $lx = $cx; $ly = $cy;
        }
        $this->desktop->resize($cy,$cx);
        $this->emit(Cwt::ON_BEFORE_DRAW);
        $this->desktop->draw();
        $this->emit(Cwt::ON_AFTER_DRAW);
        ncurses_refresh();
    }

}

class Rect {
    private $l, $t, $w, $h;
    function __construct($l,$t,$w,$h) {
        $this->l = $l;
        $this->t = $t;
        $this->w = $w;
        $this->h = $h;
    }
}
function rect($l,$t,$w,$h) {

}
