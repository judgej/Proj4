<?php namespace Proj4\Points;

/**
 * Defines a geocentric point.
 * These are defined by an x/y/z coordinate from the centre of the reference ellipsoid.
 * CHECKME: is this where the reference prime meridiams come in, to specify where the
 * "front" of the earth is?
 *
 * The cartesian coordinate system is a right-hand, rectangular, three-dimensional,
 * earth-fixed coordinate system with an origin at (0, 0, 0).
 * The Z-axis, is parrallel to the axis of rotation of the earth.
 * The Z-coordinate is positive toward the North pole.
 * The X-Y plane lies in the equatorial plane.
 * The X-axis lies along the intersection of the plane containing the prime meridian
 * and the equatorial plane. The X-coordinate is positive toward the intersection
 * of the prime meridian and equator.
 */

use Exception;

use Proj4\Ellipsoid;

class Geocentric
{
    /**
     * Radians to degrees.
     */

    const R2D = 57.29577951308232088;

    /**
     * The coordinates.
     */

    protected $x;
    protected $y;
    protected $z;

    /**
     * The ellipsoid for this point.
     */
    protected $ellipsoid;

    /**
     * Initialise with (x, y, z) or [x, y, z].
     */
    public function __construct($x, $y = null, $z = null, Ellipsoid $ellipsoid = null)
    {
        $this->setOrdinates($x, $y, $z);

        // If no ellipsoid supplied, then create a default (will be WGS84).
        if ( ! isset($ellipsoid)) {
            $ellipsoid = new Ellipsoid;
        }

        $this->ellipsoid = $ellipsoid;
    }

    /**
     * Set a new elllipsoide without doing any conversion.
     */
    public function withEllipsoid(Ellipsoid $ellipsoid)
    {
        $clone = clone $this;
        $clone->ellipsoid = $ellipsoid;
        return $clone;
    }

    /**
     * Set the (x, y, z) values.
     * This is protected for immutability.
     */
    protected function setOrdinates($x, $y = null, $z = null)
    {
        if (is_array($x)) {
            $values = array_values($x);

            if (isset($values[2])) {
                $z = $values[2];
            }

            if (isset($values[1])) {
                $y = $values[1];
            }

            if (isset($values[0])) {
                $x = $values[0];
            }
        }

        $this->x = $x;
        $this->y = $y;
        $this->z = $z;
    }

    /**
     * Clone the object with new coordinates.
     */
    public function withOrdinates($x, $y = null, $z = null)
    {
        $clone = clone $this;
        $clone->setOrdinates($x, $y, $z);
        return $clone;
    }

    /**
     * Access the ordinates as properties.
     */
    public function __get($name)
    {
        if ($name == 'x' || $name == 'y' || $name == 'z') {
            return $this->$name;
        }

        // TODO: exception - invalid property.
    }

    /**
     * Return as an array.
     */
    public function asArray()
    {
        return [$this->x, $this->y, $this->z];
    }

    /**
     * Convert to a gendetic (lat/long) coordinate.
     * So, is this the right place to do this, and what do we need?
     *  - An ellipsoid.
     */
    public function toGeodetic()
    {
        // local defintions and variables
        // end-criterium of loop, accuracy of sin(Latitude)

        $genau = 1.0E-12;
        $genau2 = ($genau * $genau);
        $maxiter = 30;

        $X = $this->x;
        $Y = $this->y;

        // Z value not always supplied
        $Z = ($this->z ? $this->z : 0.0);

        /*
        $P;        // distance between semi-minor axis and location 
        $RR;       // distance between center and location
        $CT;       // sin of geocentric latitude 
        $ST;       // cos of geocentric latitude 
        $RX;
        $RK;
        $RN;       // Earth radius at location 
        $CPHI0;    // cos of start or old geodetic latitude in iterations 
        $SPHI0;    // sin of start or old geodetic latitude in iterations 
        $CPHI;     // cos of searched geodetic latitude
        $SPHI;     // sin of searched geodetic latitude 
        $SDPHI;    // end-criterium: addition-theorem of sin(Latitude(iter)-Latitude(iter-1)) 
        $at_pole;     // indicates location is in polar region 
        $iter;        // of continous iteration, max. 30 is always enough (s.a.) 
        $long;
        $lat;
        $height;
        */

        $a = $this->ellipsoid->a;
        $b = $this->ellipsoid->b;

        $at_pole = false;
        // The distance from the line joining the poles.
        $P = sqrt($X * $X + $Y * $Y);
        $RR = sqrt($X * $X + $Y * $Y + $Z * $Z);

        // Special cases for latitude and longitude
        if ($P / $a < $genau) {
            // Special case: at the poles if P=0. (X=0, Y=0)
            $at_pole = true;
            $long = 0.0;

            // If (X,Y,Z)=(0,0,0) then Height becomes semi-minor axis
            // of ellipsoid (=center of mass) and Latitude becomes PI/2

            if ($RR / $a < $genau) {
                $lat = M_PI_2;
                $height = -$b;
                return;
            }
        } else {
            // Ellipsoidal (geodetic) longitude interval:
            // -PI < Longitude <= +PI
            $long = atan2($Y, $X);
        }

        // The eccentricity squared is used a lot in the calculations.
        $es = $this->ellipsoid->es;

        /* --------------------------------------------------------------
         * Following iterative algorithm was developped by
         * "Institut für Erdmessung", University of Hannover, July 1988.
         * Internet: www.ife.uni-hannover.de
         * Iterative computation of CPHI,SPHI and Height.
         * Iteration of CPHI and SPHI to 10**-12 radian res$p->
         * 2*10**-7 arcsec.
         * --------------------------------------------------------------
         */

        $CT = $Z / $RR;
        $ST = $P / $RR;
        $RX = 1.0 / sqrt(1.0 - $es * (2.0 - $es) * $ST * $ST);
        $CPHI0 = $ST * (1.0 - $es) * $RX;
        $SPHI0 = $CT * $RX;
        $iter = 0;

        // Loop to find sin(Latitude) res $p-> Latitude
        // until |sin(Latitude(iter)-Latitude(iter-1))| < genau

        do {
            ++$iter;

            $RN = $a / sqrt(1.0 - $es * $SPHI0 * $SPHI0);

            // Ellipsoidal (geodetic) height
            $height = $P * $CPHI0 + $Z * $SPHI0 - $RN * (1.0 - $es * $SPHI0 * $SPHI0);

            $RK = $es * $RN / ($RN + $height);
            $RX = 1.0 / sqrt(1.0 - $RK * (2.0 - $RK) * $ST * $ST);
            $CPHI = $ST * (1.0 - $RK) * $RX;
            $SPHI = $CT * $RX;
            $SDPHI = $SPHI * $CPHI0 - $CPHI * $SPHI0;
            $CPHI0 = $CPHI;
            $SPHI0 = $SPHI;
        } while ($SDPHI * $SDPHI > $genau2 && $iter < $maxiter);

        // Ellipsoidal (geodetic) latitude
        $lat = atan($SPHI / abs($CPHI));

        // Create a new Geodetic coordinate.
        // Give it same datum and ellipsoid as the current point.

        $point = new Geodetic($long * static::R2D, $lat * static::R2D, $height, $this->ellipsoid);

        return $point;
    }
}