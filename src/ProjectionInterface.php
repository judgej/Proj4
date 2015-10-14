<?php namespace Academe\Proj;

/**
 * xxx
 */

use Exception;
use Academe\Proj\Point\Geodetic;
use Academe\Proj\Point\Projection as ProjectionPoint;

interface ProjectionInterface
{
    /**
     * Convert a deodetic (lat/lon) point to the defined projection.
     */
    public function forward(Geodetic $point);

    /**
     * Convert the defined projection point to a deodetic (lat/lon) point.
     * Returns a Geodetic point.
     */
    public function inverse(ProjectionPoint $point);
}
