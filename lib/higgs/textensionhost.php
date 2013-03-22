<?php

namespace Higgs;

trait TExtensionHost {

    private $_teh_extn = [];
    public function addExtension(Extension $ext) {
        $this->debug("Attaching extension: %s", get_class($ext));
        $this->_teh_extn[$ext->getUuid()] = $ext;
    }
    protected function initExtensions() {
        foreach($this->_teh_extn as $ext) {
            $this->debug("Initializing extension: %s", get_class($ext));
            $ext->attach($this);
        }
    }
}


