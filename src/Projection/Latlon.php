<?php namespace Academe\Proj\Projection;

/**
 * Lat/lon projection.
 */

use Academe\Proj\AbstractProjection;
use Academe\Proj\Point\Geodetic;
use Academe\Proj\PointInterface;
use Academe\Proj\Point\Projected;

class Latlon extends AbstractProjection
{
    public function forward(Geodetic $point)
    {
        return clone $point;
    }

    public function inverse(Projected $point)
    {
        return clone $point;
    }
}
