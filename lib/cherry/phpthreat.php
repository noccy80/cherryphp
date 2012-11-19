<?php

namespace Cherry;

class PhpThreat {

    /**
     * Security Domain Levels determine what security model should be applied
     * to a script, and
     */
    const
            DOMAIN_SAFE = 0,        /// Script is safe for execution
            DOMAIN_PRIVILEGED = 1,  ///
            DOMAIN_SUSPICIOUS = 2;  /// 

    public function __construct($file) {
        $this->scan($file);

    }

    private function scan($file) {
        $toks = token_get_all(file_get_contents($file));
        $hs = new HeuristicScanner($toks);
        return $hs;
    }

    public static function defuse($file,$include=false) {
        // return file after tokenizing and replacing disallowed functions with warnings
        // if $include is true, save to temp file and include the safe version.
    }


}

class HeuristicScanner {

    private
            $susp_cmd = [
                'glob', 'opendir'
            ],
            $susp_str = [
                'passwd', 'shadow', 'rm', 'bash', 'sh'
            ],
            $unsafe_cmd = [
                'unlink', 'rmdir', 'exec'
            ];

    public function __construct($tokens) {
        // The stack, each entry should look like:
        //  [ $func, $index
        $stack = [];
        $history = [];
        // Need a first sweep to detect all possible entry points into the code
        $entrypoints = [];
        foreach($tokens as $tok) {
            // Find all "function" tokens and the first code lines after opening tags
        }
        // Scann all the tokens here, try to follow execution flow building a stack and matching it against criteria
        foreach($tokens as $tok) {
            switch($tok[0]) {
                case T_WHITESPACE:
                    break;
                default:
                    if (is_string($tok[0])) {
                        printf("Skipping stringtoken '%s'\n", $tok[0]);
                    } else {
                        printf("Skipping token %s (%s)\n", token_name($tok[0]), "'".join("','",array_slice($tok,1))."'");
                    }
                    break;
            }
        }
    }

    public function getStatus() { }

}

class PhpVm {

    // Execute the next instruction
    public function step() { }

    // Get the current instruction
    public function getInstruction() { }

    // Get the instruction pointer
    public function getIp() { }

    // Set the instruction pointer
    public function setIp($x) { }

}

$hs = new PhpThreat(__FILE__);
