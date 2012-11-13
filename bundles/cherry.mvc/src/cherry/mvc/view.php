<?php

namespace Cherry\Mvc;

abstract class View {

    protected
            $subviews = array(),
            $contentview = null,
            $isCacheable = false,
            $observer = null;
    
    public function __construct() {
        if (defined('IS_PROFILING'))
            $this->observer = \App::profiler()->enter('Rendering view: '.__CLASS__);
    }
    
    public function __destruct() {
        if (defined('IS_PROFILING')) {
            \App::profiler()->log("Destroying view observer");
            if ($this->observer) unset($this->observer);
        }
    }
    
    public function __toString() {
        return $this->render(true);
    }

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

    public function setView($key, View $view) {
        $this->subviews[$key] = $view;
    }

    public function setContentView(View $view) {
        $this->contentview = $view;
    }

    abstract function render($return=false);

}
