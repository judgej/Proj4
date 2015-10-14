<?php namespace Academe\Proj\Projection;

/**
 * Lat/lon projection.
 */

use Academe\Proj\AbstractProjection;
use Academe\Proj\Point\Geodetic;
use Academe\Proj\PointInterface;
use Academe\Proj\Point\Projection;

class Latlon extends AbstractProjection
{
    /**
     * The name of the projection.
     */
    protected $projection_name = 'Lat/Long';

    /**
     * Coordinate can be supplied as:
     *  [$lat, $lon, $height]
     *  ['lat' => $lat, 'lon' => $lon, 'height' => $height]
     */
    protected $coord_names = [
        'lat' => ['lat', 'latitude', '0'],
        'lon' => ['lon', 'long', 'longitude', '1'],
        'height'  => ['height', 'h', '2'],
    ];

    public function forward(Geodetic $point)
    {
        return clone $point;
    }

    public function inverse(Projection $point)
    {
        return clone $point;
    }
}
