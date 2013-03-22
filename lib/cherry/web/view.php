<?php

namespace Cherry\Web;

class View {

    private $data = [];
    private $content = null;

    public function __construct($file=null) {
        if ($file)
            $this->loadView($file);
    }
    
    public function loadView($file) {
        // TODO: Better parsing
        if (fnmatch("*.atl",$file))
            $loader = new \Cherry\Web\Loaders\AtlLoader();
        elseif (fnmatch("*.php",$file))
            $loader = new \Cherry\Web\Loaders\PhpLoader();
        else
            $loader = null;
        if ($loader) {
            $this->content = $loader->load($file,false);
        
        }
    }

    public function __toString() {
        return (string)$this->content;
    }

}
