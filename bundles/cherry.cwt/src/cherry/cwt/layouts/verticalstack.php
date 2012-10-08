<?php

namespace Cherry\Cwt\Layouts;

class VerticalStack extends Stack {

    public function __construct(array $layout=null) {

        $this->initprops(array(
            'layout' => 'string null',
            'keys' => 'string',
            'fill' => 'boolean'
        ),array(
            'layout' => null,
            'fill' => false,
            'keys' => '',
        ));

        if ($layout) {
            $this->fill = true;
            $this->layout = join(',',array_values($layout));
            $this->keys = join(',',array_keys($layout));
            foreach(array_keys($layout) as $key)
                $this->registerprop($key,null);
        } else {

        }

    }

    public function draw() {

    }

}
