<?php

namespace Cherry\Graphics\Filters;

use Cherry\Graphics\Canvas;
use Cherry\Types\Rect;

interface IFilter {

    public function applyImageFilter(Canvas $canvas, Rect $rect=null);

}
