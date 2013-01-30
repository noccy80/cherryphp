<?php

define("XENON","cherryphp/trunk");
require_once("xenon/xenon.php");

use Cherry\BinaryLog;

class BinaryLogExample extends \Cherry\Cli\ConsoleApplication {

    function main() {
        $this->writelog();
        $this->readlog();
    }

    function writelog() {
        // Open the binary log test.slf
        $sl = new BinaryLog("test.slf","w+");
        // Header function is invoked for every write to the log. You can also
        // specify the header block as the 2nd parameter to write(). The true at
        // the end indicates that the function should be pushed onto the stack
        // rather than replacing the current headerfunc. This way, setting the
        // headerfunc to null will pop the previously used headerfunc. Use this
        // if you intend to pass around your log class.
        $sl->setHeaderFunc(function() {
            return [ 'time'=>time(), 'coffeemachine'=>'recroom' ];
        },true);
        // Write some data to the log. The headerfunc is called for each write,
        // adding timestamp and metadata.
        $sl->write([
            'type'=>'status',
            'status'=>'Making a cup of coffee.'
        ]);
        $sl->write([
            'type'=>'error',
            'status'=>'Error, out of milk!'
        ]);
        // Close the log
        unset($sl);
    }

    function readlog() {
        // Open the binary log test.slf
        $sl = new BinaryLog("test.slf","r");
        $hdr = null;
        // Read until we get a null
        while (($log = $sl->read($hdr))) {
            // Use the header info to read date and machine
            $date = date(DateTime::RFC822,$hdr['time']);
            $machine = $hdr['coffeemachine'];
            // The status message is part of the log message.
            $status = $log['status'];
            $type = $log['type'];
            // Output it.
            echo "{$date} ({$machine}): {$type} - {$status}\n";
        }
        // Close the log
        unset($sl);
    }

}

App::run(new BinaryLogExample());
