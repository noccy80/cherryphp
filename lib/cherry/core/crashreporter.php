<?php

namespace Cherry\Core;

/**
 * class CrashReporter
 */
abstract class CrashReporter {
    public static function addCrashReporter(CrashReporter $r) {

    }
    public static function doCrashReport(CrashReportData $data) {

    }
}

/**
 * class CrashReportData
 *
 * Contains information on a crash, such as the debug log, backtrace, exception
 * that caused the crash etc.
 */
class CrashReportData {
    function __construct(\Exception $e) {

    }
    public function initCallStack($trace, $offset = 0) {

    }
    public function getCallStack($index = NULL) {

    }
}
