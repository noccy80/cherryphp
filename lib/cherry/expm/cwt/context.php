<?php

namespace Cherry\Expm\Cwt;

/**
 * @class Context
 */
class Context {
    use \Cherry\Traits\SingletonAccess;
    private $nc = null;
    private $screen = null;
    public function __construct() {
        $this->nc = ncurses_init();
        if (!ncurses_has_colors()) {
            $this->onTerminate();
            echo "No color support.\n";
        }
        ncurses_start_color();
        $this->screen = ncurses_newwin(0,0,0,0);
        $this->setCursorVisible(false);
        assert($this->screen);
        ncurses_init_pair(Widget\Widget::COLOR_DIALOGBG,NCURSES_COLOR_YELLOW, NCURSES_COLOR_BLUE);
        ncurses_init_pair(Widget\Widget::COLOR_DIALOGTEXT,NCURSES_COLOR_WHITE, NCURSES_COLOR_BLUE);
        ncurses_refresh();
    }
    public function onTerminate() {
        if ($this->nc)
            ncurses_end();
        $this->nc = null;
    }
    public function __destruct() {
        $this->onTerminate();
    }
    public function getDimensions() {
        $height = 0; $width = 0;
        ncurses_getmaxyx(STDSCR, $height, $width);
        return [ $width, $height ];
    }
    public function getScreen() {
        return $this->screen;
    }
    public function textAt($x,$y,$text) {
        ncurses_mvwaddstr($this->screen,$x,$y,$text);
    }
    public function setCursorVisible($visible=true) {
        ncurses_curs_set((bool)$visible);

    }
}
