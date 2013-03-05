<?php

namespace Cherry\Cli;

class Glyph {

    private static $glyphs = [
        'fullblock'=>'&#x2588;',
        'lightshade' => '&#x2591;',
        'mediumshade' => '&#x2592;',
        'darkshare' => '&#x2593;',
        'block1of8' => '&#x2581;',
        'block2of8' => '&#x2582;',
        'block3of8' => '&#x2583;',
        'block4of8' => '&#x2584;',
        'block5of8' => '&#x2585;',
        'block6of8' => '&#x2586;',
        'block7of8' => '&#x2587;',
        'blockmid' => '&#x2500;',
        'blockmid2' => '&#x25AC;',
        'diag1' => '&#x2571;',
        'diag2' => '&#x2572;',
        'diagcross' => '&#x2573;',
        'check0' => '&#x2B1C;',
        'check1' => '&#x2B1B;',
        'cross' => '&#x274C;',
        'arrowright' => '&#x25B6;',
        'diamond' => '&#x25C6;',
        'checkmark' => '&#x2714;',
        'ballotx' => '&#x2718;'
    ];
    private static $work = [
        'snake' => [
            '&#x2599;',
            '&#x259B;',
            '&#x259C;',
            '&#x259F;'
        ],
        'triangle' => [
            '&#x25E2;',
            '&#x25E3;',
            '&#x25E4;',
            '&#x25E5;'
        ],
        'snakesmall' => [
            '&#x231C;',
            '&#x231D;',
            '&#x231F;',
            '&#x231E;'
        ],
        'clock' => [
            '&#x25CB;',
            '&#x25D4;',
            '&#x25D1;',
            '&#x25D5;',
            '&#x25CF;',
            '&#x25C9;'
        ],
        'bars' => [
            '&#x2581;',
            '&#x2582;',
            '&#x2583;',
            '&#x2584;',
            '&#x2585;',
            '&#x2586;',
            '&#x2587;',
            '&#x2588;'
        ],
        'hatch' => [
            '&#x25A4;',
            '&#x25A5;',
            '&#x25A6;',
            '&#x25A7;',
            '&#x25A8;',
            '&#x25A9;'
        ],
        'pulse' => [
            '&#x2500;',
            '&#x2500;',
            '&#x257C;',
            '&#x2501;',
            '&#x257E;',
        ]
    ];

    public static function __callStatic($glyph,$args) {
        if (array_key_exists($glyph,self::$glyphs)) {
            return self::getGlyph(self::$glyphs[$glyph]);
        }
        return null;
    }

    public static function work($set='snake') {
        static $last = 0;
        static $step = 0;
        if ($last + 0.2 > microtime(true)) return;
        $last = microtime(true);
        $step++;
        if ($step >= count(self::$work[$set]))
            $step = 0;
        $g = self::$work[$set][$step];
        return "\x08".html_entity_decode($g, ENT_NOQUOTES, 'UTF-8');
    }

    public static function getGlyph($glyph) {
        return html_entity_decode($glyph, ENT_NOQUOTES, 'UTF-8');
        return null;
    }

}
