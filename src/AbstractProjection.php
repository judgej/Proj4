<?php namespace Academe\Proj;

/**
 * The abstract projection providing base details that all projections share.
 * CHECKME: The projection - is it a point? Or is it something that a projected point inherits?
 */

use Exception;
use Academe\Proj\Point\Geodetic;
use Academe\Proj\ProjectionInterface;

abstract class AbstractProjection implements ProjectionInterface
{
    // M_PI*2
    const TWO_PI = 6.283185307179586477;

    /**
     * Initialise the projection with conversion parameters, defaulting
     * where necessary.
     * The concrete class will provide metadata to tell us what parameters
     * to support and how to validate them. Reflection may help here to give
     * us a list of properties at least.
     */
    public function __construct(array $params)
    {
    }

    /**
     * Utility methods used by many of the forward/inverse conversions.
     */
    protected function adjust_lon($x)
    {
        return (abs($x) < M_PI) ? $x : ($x - ($this->sign($x) * statis::TWO_PI));
    }
 
    protected function sign($x)
    {
        return $x < 0.0 ? -1 : 1;
    }
}
