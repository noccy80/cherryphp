<?php

namespace Cherry\Cli;

class Ansi {

    private static $colorstack = null;
    private static $attributes = null;

    const NORMAL = "\033[0m";
    const SET_BOLD = "\033[1m";
    const CLEAR_BOLD = "\033[22m";
    const SET_FAINT = "\033[2m";
    const CLEAR_FAINT = "\033[22m";
    const SET_STANDOUT = "\033[3m";
    const CLEAR_STANDOUT = "\033[22m";
    const SET_UNDERLINE = "\033[4m";
    const CLEAR_UNDERLINE = "\033[24m";
    const SET_BLINK = "\033[5m";
    const CLEAR_BLINK = "\033[25m";
    const SET_REVERSE = "\033[7m";
    const CLEAR_REVERSE = "\033[27m";


    public static function init() {
        self::$colorstack = new \Data\FifoQueue();
        self::$attributes = 0x00;
    }

    function pushColor($fg,$bg=null) {
        if (self::$colorstack === null) return '';
    }

    function popColor() {
        if (self::$colorstack === null) return '';
        if (count(self::$colorstack) > 0) {
            $color = self::$colorstack->pop();
            return $color;
        }
    }

    function setBold() {
        if (self::$attributes === null) return '';
        return self::SET_BOLD;
    }

    function clearBold() {
        if (self::$attributes === null) return '';
        return self::CLEAR_BOLD;
    }

    function setUnderline() {
        if (self::$attributes === null) return '';
        return self::SET_UNDERLINE;
    }

    function clearUnderline() {
        if (self::$attributes === null) return '';
        return self::CLEAR_UNDERLINE;
    }

    function pushEffect($effect) {
        if (self::$attributes === null) return '';
    }

    function popEffect() {
        if (self::$attributes === null) return '';
    }

    function color($color,$string) {

    }

    function uncolor($string) {

    }

    function colorStrip($str) {

    }

    function reset() {
        if (self::$attributes === null) return '';
        self::$attributes = 0x00;
        return self::NORMAL;
    }

}
