<?php

namespace cherry\mvc\view;

abstract class Base {

    protected $subviews = array();
    protected $isCacheable = false;
    
    /**
     * @brief Tell the renderer that the view is cacheable.
     *
     * This will cache the resulting view (or subview) decreasing the time
     * needed to composite the complete view.
     *
     * @param bool $cacheable True indicates the view is cacheable.
     * @return bool Last state of the cacheable flag.
     */
    protected function setCacheable($cacheable=true) {
        $old = $this->isCacheable;
        $this->isCacheable = (bool)$cacheable;
        return $old;
    }
    
    public function addView($key, Base $view) {
        $this->subviews[$key] = $view;
    }
    
    abstract function load();

}
