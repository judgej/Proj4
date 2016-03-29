<?php namespace Academe\Proj\Projection;

/**
 * UTM projection.
 * TODO: this should simply extend the tmerc projection.
 */
/*******************************************************************************
  NAME                            TRANSVERSE MERCATOR

  PURPOSE:	Transforms input longitude and latitude to Easting and
  Northing for the Transverse Mercator projection.  The
  longitude and latitude must be in radians.  The Easting
  and Northing values will be returned in meters.

  ALGORITHM REFERENCES

  1.  Snyder, John P., "Map Projections--A Working Manual", U.S. Geological
  Survey Professional Paper 1395 (Supersedes USGS Bulletin 1532), United
  State Government Printing Office, Washington D.C., 1987.

  2.  Snyder, John P. and Voxland, Philip M., "An Album of Map Projections",
  U.S. Geological Survey Professional Paper 1453 , United State Government
  Printing Office, Washington D.C., 1989.
*******************************************************************************/

use Academe\Proj\AbstractProjection;
use Academe\Proj\Point\Geodetic;
use Academe\Proj\PointInterface;
use Academe\Proj\Point\Projected;

class Utm extends Tmerc
{
    /**
     * The name of the projection.
     */
    protected $projection_name = 'Universal Transverse Mercator System';

    /**
     * Coordinate can be supplied as: ['x' => $x, 'y' => $y, 'zone' => $zone]
     * TODO: include the north/south indicator here.
     */
    protected $coord_names = [
        'x' => ['x', 'e', 'easting'],
        'y' => ['y', 'n', 'northing'],
        'zone',
    ];

    /*
     * Initialisation parameters.
     * These are used in the transform calculations.
     */
    protected $params = [
        'lat0' => 0.0,
        'long0' => 0.0,
        'a' => 0.0,
        'ep2' => 0.0,
        'x0' => 500000.0,
        'y0' => 0.0,
        'k0' => 0.9996,
        'ml0' => 0.0,
        'e0' => 0.0,
        'e1' => 0.0,
        'e2' => 0.0,
        'e3' => 0.0,
        // TODO: rename this to simply "south".
        // Is this actually part of the coordinate? e.g. the CN Tower is at "17N 630084 4833438" using
        // North/South designator, or "17T 630084 4833438" using alphabetic lattitude desigator. So yes,
        // this certainly does look like a coordiate. The way the UTM projection has been implemented,
        // it looks like a square peg pushed into a round hole. I'd like to make the hole square too.
        'utmSouth' => false,
    ];


    // TODO: init should not really be needed.
    // An initialisation of the parent projection should be done first.
    // Also initialisation should be done each time values are changed (which if
    // done as an immutable object, should mean on instantiation, so long as that is
    // the only point at which parameters are set).
    // FIXME: there is actually no real "init" here - the zone and hemisphere indicators
    // come from the points, and are so only needed then.
    public function init() {
        parent::init();

        // The zone is only used here.
        // FIXME: The zone is now on the *point*.
        $this->long0 = ((6 * abs($this->zone)) - 183) * Common::D2R;

        // FIXME: Where does utmSouth come from? It seems to be a boolean def parameter of "south"
        // (true if present). We should just call it "south".
        $this->y0 = $this->utmSouth ? 10000000.0 : 0.0;
    }


    /**
     * Convert from a Geodetic point to a UTM x/y point.
     * CHECKME: the zone needs to be taken into account.
     * Just returns the array data for initialising a CEA point.
     */
    public function forward(Geodetic $point)
    {
        return parent::forward($point);

        $lat = $point->latrad;
        $lon = $point->lomrad;

        // Delta longitude.
        $delta_lon = $this->adjust_lon($lon - $this->long0);

        // $con = 0;    // cone constant
        // $x = 0;
        // $y = 0;

        $sin_phi = sin($lat);
        $cos_phi = cos($lat);

        // TODO: how to derived $this->sphere?
        if (isset($this->sphere) && $this->sphere === true) {
            // spherical form
            $b = $cos_phi * sin($delta_lon);
            if ((abs(abs($b) - 1.0)) < .0000000001) {
                throw new Exception('tmerc:forward: Point projects into infinity');
                return(93); // CHECKME: what is this?
            } else {
                $x = 0.5 * $this->a * $this->k0 * log((1.0 + $b) / (1.0 - $b));
                $con = acos($cos_phi * cos($delta_lon) / sqrt(1.0 - $b * $b));
                if ($lat < 0) {
                    $con =- $con;
                }
                $y = $this->a * $this->k0 * ($con - $this->lat0);
            }
        } else {
            $al = $cos_phi * $delta_lon;
            $als = pow( $al, 2 );
            $c = $this->ep2 * pow( $cos_phi, 2 );
            $tq = tan( $lat );
            $t = pow( $tq, 2 );
            $con = 1.0 - $this->es * pow( $sin_phi, 2 );
            $n = $this->a / sqrt( $con );

            $ml = $this->a * $this->mlfn($this->e0, $this->e1, $this->e2, $this->e3, $lat);

            $x = $this->k0 * $n * $al * (1.0 + $als / 6.0 * (1.0 - $t + $c + $als / 20.0 * (5.0 - 18.0 * $t + pow($t, 2) + 72.0 * $c - 58.0 * $this->ep2))) + $this->x0;
            $y = $this->k0 * ($ml - $this->ml0 + $n * $tq * ($als * (0.5 + $als / 24.0 * (5.0 - $t + 9.0 * $c + 4.0 * pow($c, 2) + $als / 30.0 * (61.0 - 58.0 * $t + pow($t, 2) + 600.0 * $c - 330.0 * $this->ep2))))) + $this->y0;
        }

        return ['x' => $x, 'y' => $y];
    }

    /**
     * Convert a CEA point back to a geodetic point.
     * Just returns the array data for initialising a Geodetic point.
     */
    public function inverse(Projected $point)
    {
        return parent::inverse($point);

        // temporary angles
        // $phi;
        // difference between longitudes
        // $delta_phi;

        // maximun number of iterations
        $max_iter = 6;
        if (isset($this->sphere) && $this->sphere === true) {
            // spherical form
            $f = exp($point->x / ($this->a * $this->k0));
            $g = 0.5 * ($f - 1 / $f);
            $temp = $this->lat0 + $point->y / ($this->a * $this->k0);
            $h = cos($temp);
            $con = sqrt((1.0 - $h * $h) / (1.0 + $g * $g));
            $lat = $this->asinz($con);
            if ($temp < 0)
                $lat = -$lat;
            if (($g == 0) && ($h == 0)) {
                $lon = $this->long0;
            } else {
                $lon = $this->adjust_lon(atan2($g, $h) + $this->long0);
            }
        } else {
            // ellipsoidal form
            $x = $point->x - $this->x0;
            $y = $point->y - $this->y0;

            $con = ($this->ml0 + $y / $this->k0) / $this->a;
            $phi = $con;

            for ($i = 0; true; $i++ ) {
                $delta_phi = (($con + $this->e1 * sin(2.0 * $phi) - $this->e2 * sin(4.0 * $phi) + $this->e3 * sin(6.0 * $phi)) / $this->e0) - $phi;
                $phi += $delta_phi;

                if (abs($delta_phi) <= static::EPSLN) {
                    break;
                }

                if ($i >= $max_iter) {
                    throw new Exception('tmerc:inverse: Latitude failed to converge');
                }
            }


            if (abs($phi) < M_PI_2) {
                // sincos(phi, &sin_phi, &cos_phi);
                $sin_phi = sin($phi);
                $cos_phi = cos($phi);
                $tan_phi = tan($phi);
                $c = $this->ep2 * pow($cos_phi, 2);
                $cs = pow($c, 2);
                $t = pow($tan_phi, 2);
                $ts = pow($t, 2);
                $con = 1.0 - $this->es * pow($sin_phi, 2);
                $n = $this->a / sqrt($con);
                $r = $n * (1.0 - $this->es) / $con;
                $d = $x / ($n * $this->k0);
                $ds = pow($d, 2);
                $lat = $phi - ($n * $tan_phi * $ds / $r) * (0.5 - $ds / 24.0 * (5.0 + 3.0 * $t + 10.0 * $c - 4.0 * $cs - 9.0 * $this->ep2 - $ds / 30.0 * (61.0 + 90.0 * $t + 298.0 * $c + 45.0 * $ts - 252.0 * $this->ep2 - 3.0 * $cs)));
                $lon = $this->adjust_lon($this->long0 + ($d * (1.0 - $ds / 6.0 * (1.0 + 2.0 * $t + $c - $ds / 20.0 * (5.0 - 2.0 * $c + 28.0 * $t - 3.0 * $cs + 8.0 * $this->ep2 + 24.0 * $ts))) / $cos_phi));
            } else {
                $lat = M_PI_2 * $this->sign($y);
                $lon = $this->long0;
            }
        }

        // The result is given in radians.
        return ['latrad' => $lat, 'lonrad' => $lon];
    }
}
