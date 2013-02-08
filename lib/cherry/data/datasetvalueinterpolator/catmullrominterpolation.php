<?php

/**
 * 
 * Paul Breeuwsma proposes the following coefficients for a smoother
 * interpolated curve, which uses the slope between the previous
 * point and the next as the derivative at the current point. This
 * results in what are generally referred to as Catmull-Rom splines.
 *
 *   a0 = -0.5*y0 + 1.5*y1 - 1.5*y2 + 0.5*y3;
 *   a1 = y0 - 2.5*y1 + 2*y2 - 0.5*y3;
 *   a2 = -0.5*y0 + 0.5*y2;
 *   a3 = y1;
 *   
 * Source: http://paulbourke.net/miscellaneous/interpolation/
 */