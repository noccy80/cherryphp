#!/usr/bin/php
<?php
namespace MyApp;

define('APPLICATION','Application');
define('APP_NS','MyApp');
define('APP_FQCN','/'.APP_NS.'/'.APPLICATION);

require_once('lib/bootstrap.php');

$lepton = new \cherry\Lepton(__FILE__);

// We need this to set up a CLI application
require_once('lib/cherry/cli/application.php');

use cherry\Base\Event;

class Application extends \cherry\Cli\Application {
    protected $apppath = null;
    protected $route = false;
    protected $config = 'development';

    /**
     * Function to return some basic application information
     */
    function getApplicationInfo() {
        return array(
            'appname' => 'CherryTool',
            'version' => '1.0',
            'description' => 'CherryPHP helper tool',
            'copyright' => "Copyright (c) 2012, The CherryPHP Project\nDistributed under GNU GPL version 3"
        );
    }

    /**
     * Init is called when the application is created. This is the perfect place to
     * register command line arguments, hook events and set up defaults.
     */
    function init() {
    
        // Help:  -h or --help
        $this->addArgument('h','help',
                            'Show this help');
        // Application path:  -a path or --app path
        // Bound to $this->apppath
        $this->addArgument('v','verbose',
                            'Verbose mode',
                            array($this,'verbose'));
        $this->addCommand('action','Perform an action');

    }

    /**
     * Main function, when we get here everything has been parsed and loaded Ok.
     */
    function main() {

        if (($this->hasArgument('h'))
        || (count($this->parameters) == 0)) {
            $this->usage();
            return 1;
        }

    }

}

$lepton->runApplication(new \MyApp\Application());
