<?php namespace Academe\Proj\Projection;

/**
 * Author : Julien Moquet
 * 
 * Inspired by Proj4JS from Mike Adair madairATdmsolutions.ca
 *                      and Richard Greenwood rich@greenwoodma$p->com 
 * License: LGPL as per: http://www.gnu.org/copyleft/lesser.html 
 */
/*******************************************************************************
  NAME TRANSVERSE MERCATOR

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

/**
  Initialize Transverse Mercator projection
 */

use Exception;

use Academe\Proj\AbstractProjection;
use Academe\Proj\Ellipsoid;
use Academe\Proj\Point\Geodetic;
use Academe\Proj\PointInterface;
use Academe\Proj\Point\Projected;

class Tmerc extends AbstractProjection
{
    /**
     * The name of the projection.
     */
    protected $projection_name = 'Transverse Mercator';

    /**
     * Coordinate can be supplied as: ['x' => $x, 'y' => $y]
     */
    protected $coord_names = [
        'x' => ['x', 'e', 'easting'],
        'y' => ['y', 'n', 'northing'],
    ];

    /*
     * Initialisation parameters.
     * These are used in the transform calculations.
     * FIXME: many Projj4 parameters have underscores in, such as lat_0 and x_0. Where does that get#
     * translated to lat0 and x0? Also lon_0 instead of lon0, and k vs both k0 and k_0.
     */
    protected $params = [
        // These two angles are in radians, not degrees.
        // Latitude of origin, aka lat_0.
        'lat0' => 0.0,
        // Central meridian, aka lon_0.
        'lon0' => 0.0,

        // Both required, but will be derived from other sources if not set directly.
        // es, essentricity squared is 2f -f^2, where f is the flattening.
        'a' => null,
        'es' => null,

        // Alternative ellipsoid parameters used to derive "a" and "es" (optional).
        'b' => null,
        'rf' => null,
        'ellps' => null,

        // What is this?
        // Found this in proj4phpProj.php:
        // $this->ep2 = ($this->a2 - $this->b2) / $this->b2; // used in geocentric
        // Is it the inverse reciprocal flattening, or something?
        'ep2' => 0.0,

        // False easting, aka x_0.
        'x0' => 500000,
        // False northing, aka y_0.
        'y0' => 0.0,
        // Central meridian scale factor, aka k.
        'k0' => 1.0, // 0.9996 for UTM
    ];

    /**
     * Calculated values derived from other parameters in init().
     */
    protected $is_sphere = true;

    protected $e0 = 0.0;
    protected $e1 = 0.0;
    protected $e2 = 0.0;
    protected $e3 = 0.0;
    protected $ml0 = 0.0;

    // Maximun number of iterations in the inverse conversion.
    protected $max_iter = 6;

    /**
     * Initialise some derived values.
     */
    public function init(Geodetic $point = null)
    {
        // We need 'a' and 'es' - the ellipsoid semi-major axis and essentricity squared.
        // Also need to know if this is a sphere.
        // That can come from a number of sources, so we will work through what we
        // have to find or derive it.
        // Maybe we just create an ellipsoid with what we have, and read off what we need?
        // That why we *always* have an ellipsoid to work from.
        // We are either given an ellisoid, given ellisoid parameters, or have one in the point.
        // TODO: think about whether all this helpful deriving should be done elsewhere and making
        // a ready-made ellipsoid *or* a+es mandatory parameters.

        // If we don't have "a" and "es" directly, then we need to derive them.
        if ( ! isset($this->a) || ! isset($this->es)) {
            if (isset($this->ellps) && $this->ellps instanceof Ellipsoid) {
                // An ellisoid was passed in.

                $ellps = $this->ellps;
            } elseif (isset($this->a) || isset($this->es) || isset($this->b) || isset($this->rf) || isset($this->es)) {
                // Create an ellipsoid from the parameters provided.

                $ellps = new Ellipsoid([
                    'a' => $this->a,
                    'b' => $this->b,
                    'rf' => $this->rf,
                    'es' => $this->es,
                ]);
            } elseif (isset($point)) {
                // Get the ellipsoid from the geodetic point passed in.

                $ellps = $point->getEllipsoid();
            }

            // If we have an ellipsoid now, then read off what we need.
            if ( ! empty($ellps)) {
                $this->setParam('a', $ellps->a);
                $this->setParam('es', $ellps->es);
                $this->is_sphere = $ellps->isSphere();
            } else {
                throw new Exception(sprintf(
                    'Missing "a" and "es", or alternative ellipsoid details'
                ));
            }
        }

        $this->e0 = $this->e0fn($this->es);
        $this->e1 = $this->e1fn($this->es);
        $this->e2 = $this->e2fn($this->es);
        $this->e3 = $this->e3fn($this->es);

        $this->ml0 = $this->a * $this->mlfn($this->e0, $this->e1, $this->e2, $this->e3, $this->lat0);
    }

    /**
     * Transverse Mercator Forward  - lon/lat to x/y
     */
    public function forward(Geodetic $point)
    {
        $lat = $point->latrad;
        $lon = $point->lonrad;

        // Initialise some calculated values.
        $this->init($point);

        // Delta longitude
        $delta_lon = $this->adjustLon($lon - $this->lon0);

        $sin_phi = sin($lat);
        $cos_phi = cos($lat);

        // The point comes with its ellipsoid, so that is where we can get the details from
        // if not overridden.
        // Ask the ellipsoid whether it is a sphere.

        if ($this->is_sphere) {
            //
            // Spherical form.
            //

            $b = $cos_phi * sin($delta_lon);

            if ((abs(abs($b) - 1.0)) < .0000000001) {
                throw new Exception('tmerc:forward: Point projects into infinity');
            } else {
                $x = 0.5 * $this->a * $this->k0 * log((1.0 + $b) / (1.0 - $b));
                $con = acos($cos_phi * cos($delta_lon) / sqrt(1.0 - $b * $b));

                if ($lat < 0) {
                    // Chnage the sign.
                    $con = -$con;
                }

                $y = $this->a * $this->k0 * ($con - $this->lat0);
            }
        } else {
            //
            // Ellipsoidal form.
            //

            $al = $cos_phi * $delta_lon;
            $als = pow($al, 2);
            $c = $this->ep2 * pow($cos_phi, 2);
            $tq = tan($lat);
            $t = pow($tq, 2);
            $con = 1.0 - $this->es * pow($sin_phi, 2);
            $n = $this->a / sqrt($con);

            $ml = $this->a * $this->mlfn($this->e0, $this->e1, $this->e2, $this->e3, $lat);

            $x = $this->k0 * $n * $al
                * (1.0 + $als / 6.0 * (1.0 - $t + $c + $als / 20.0 * (5.0 - 18.0 * $t + pow($t, 2) + 72.0 * $c - 58.0 * $this->ep2)))
                + $this->x0;

            $y = $this->k0
                * ($ml - $this->ml0 + $n * $tq * ($als * (0.5 + $als / 24.0 * (5.0 - $t + 9.0 * $c + 4.0 * pow( $c, 2 ) + $als / 30.0 * (61.0 - 58.0 * $t + pow( $t, 2 ) + 600.0 * $c - 330.0 * $this->ep2)))))
                + $this->y0;
        }

        return ['x' => $x, 'y' => $y];
    }

    /**
     * Transverse Mercator Inverse  -  x/y to lon/lat (in radians).
     * FIXME: the x and y have limits, as the resulting longitude must be
     * within 4 degrees of the central meridian. Without bound checking, the
     * results will be wildly wrong when going beyond about 450km on the x axis.
     * Similarly, the y is limited to a distance from lat0 to within about
     * 15 degrees of the poles (actually that is probably wrong, as that should
     * apply to the standard mercator projection; with transverse we can go right
     * up to the poles, with the distance to the poles depending on the ellipsoid
     * dimensiona and lat0).
     */
    public function inverse(Projected $point)
    {
        // Initialise some calculated values.
        $this->init();

        if ($this->is_sphere) {
            //
            // Spherical form.
            //

            $f = exp($point->x / ($this->a * $this->k0));
            $g = 0.5 * ($f - 1 / $f);
            $temp = $this->lat0 + $point->y / ($this->a * $this->k0);
            $h = cos($temp);
            $con = sqrt((1.0 - $h * $h) / (1.0 + $g * $g));
            $lat = $this->asinz($con);

            if ($temp < 0) {
                $lat = -$lat;
            }

            if (($g == 0) && ($h == 0)) {
                $lon = $this->lon0;
            } else {
                $lon = $this->adjustLon(atan2($g, $h) + $this->lon0);
            }
        } else {
            //
            // Ellipsoidal form.
            //

            $x = $point->x - $this->x0;
            $y = $point->y - $this->y0;

            $con = ($this->ml0 + $y / $this->k0) / $this->a;
            $phi = $con;

            for ($i = 0; true; $i++) {
                $delta_phi = (($con + $this->e1 * sin(2.0 * $phi) - $this->e2 * sin(4.0 * $phi) + $this->e3 * sin(6.0 * $phi)) / $this->e0) - $phi;
                $phi += $delta_phi;

                if (abs($delta_phi) <= static::EPSLN) {
                    break;
                }

                if ($i >= $this->max_iter) {
                    throw new Exception(sprintf(
                        'tmerc:inverse: Latitude failed to converge after %d iterations',
                        $this->max_iter
                    ));
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
                $lon = $this->adjustLon($this->lon0 + ($d * (1.0 - $ds / 6.0 * (1.0 + 2.0 * $t + $c - $ds / 20.0 * (5.0 - 2.0 * $c + 28.0 * $t - 3.0 * $cs + 8.0 * $this->ep2 + 24.0 * $ts))) / $cos_phi));
            } else {
                $lat = M_PI_2 * $this->sign($y);
                $lon = $this->lon0;
            }
        }

        // The result is given in radians.
        return ['latrad' => $lat, 'lonrad' => $lon];
    }
}
