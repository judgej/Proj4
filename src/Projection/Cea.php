<?php namespace Academe\Proj\Projection;

/**
 * CEA projection.
 */

use Academe\Proj\AbstractProjection;
use Academe\Proj\Point\Geodetic;
use Academe\Proj\PointInterface;
use Academe\Proj\Point\Projected;

class Cea extends AbstractProjection
{
    /**
     * The name of the projection.
     */
    protected $projection_name = 'CEA';

    /**
     * Coordinate can be supplied as:
     *  [$x, $y]
     *  ['x' => $x, 'y' => $y]
     */
    protected $coord_names = [
        'x' => ['x', '0'],
        'y' => ['y', '1'],
    ];

    /*
     * Initialisation parameters.
     * These are used in the transform calculations.
     */
    protected $params = [
        'x0' => 0,
        'y0' => 0,
        'a' => 0,
        'long0' => 0.0,
        'lat_ts' => 0.0,
    ];

    /**
     * Convert from a Geodetic point to a Cea point.
     * Just returns the array data for initialising a CEA point.
     */
    public function forward(Geodetic $point)
    {
        // The Geodetic points will ne degrees, not radians. Maybe support latrad and lonrad
        // to convert on the way out of Geodetic?
        $lat = $point->latrad;
        $lon = $point->lonrad;

        $dlon = $this->adjustLon($lon - $this->long0);

        $x = $this->x0 + $this->a * $dlon * cos($this->lat_ts);
        $y = $this->y0 + $this->a * sin($lat) / cos($this->lat_ts);

        /* Elliptical Forward Transform
          Not implemented due to a lack of a matchign inverse function
          {
          $Sin_Lat = sin(lat);
          $Rn = $this->a * (sqrt(1.0e0 - $this->es * Sin_Lat * Sin_Lat ));
          x = $this->x0 + $this->a * dlon * cos($this->lat_ts);
          y = $this->y0 + Rn * sin(lat) / cos($this->lat_ts);
          }
         */

        return ['x' => $x, 'y' => $y];
    }

    /**
     * Convert a CEA point back to a geodetic point.
     * Just returns the array data for initialising a Geodetic point.
     */
    public function inverse(Projected $point)
    {
        $x = $point->x - $this->x0;
        $y = $point->y - $this->y0;

        $lon = $this->adjustLon($this->long0 + ($x / $this->a) / cos($this->lat_ts));
        $lat = asin(($y / $this->a) * cos($this->lat_ts));

        // The result is given in radians.
        return ['latrad' => $lat, 'lonrad' => $lon];
    }
}
