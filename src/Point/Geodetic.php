<?php namespace Proj4\Point;

/**
 * Defines a geodetic point.
 * These are defined by a latitude (northing), longitude (easting) and optional
 * height (elevation from the reference ellipsoid).
 * We should always know whay datum we are using, so we know what conversions are (and are not)
 * necesaary when we are transforming coordinates.
 */

use Exception;

use Proj4\Ellipsoid;
use Proj4\AbstractPoint;

class Geodetic extends AbstractPoint
{
    /**
     * The coordinates.
     */

    protected $lat;
    protected $lon;
    protected $height;

    /**
     * Initialise with (lat, long, height) parameters or [lat, long, height] array.
     */
    public function __construct($lat, $lon = null, $height = null, Ellipsoid $ellipsoid = null)
    {
        $this->setOrdinates($lat, $lon, $height);

        // Was an ellipsoid passed in as an array element?
        if (is_array($lat) && isset($lat[static::ELLIPSOID_PARAM_NAME])) {
            $ellsp = $lat[static::ELLIPSOID_PARAM_NAME];

            if ($ellsp instanceof Ellipsoid) {
                // Ellipoid object supplied.
                $ellipsoid = $lat[static::ELLIPSOID_PARAM_NAME];
            } elseif (is_array($ellsp)) {
                // Array provided, so turn this into an Ellipsoid.
                $ellipsoid = new Ellipsoid($ellsp);
            }
        }

        // If no ellipsoid supplied, then create a default (will be WGS84).
        if ( ! isset($ellipsoid)) {
            $ellipsoid = new Ellipsoid;
        }

        $this->ellipsoid = $ellipsoid;
    }

    /**
     * Set the (x, y, z) values.
     * This is protected for immutability.
     */
    protected function setOrdinates($lat, $lon = null, $height = null)
    {
        if (is_array($lat)) {
            $values = array_values($lat);

            if (isset($values[0])) {
                $lat = $values[0];
            }

            if (isset($values[1])) {
                $lon = $values[1];
            }

            if (isset($values[2])) {
                $height = $values[2];
            }
        }

        $this->lat = $lat;
        $this->lon = $lon;
        $this->height = $height;
    }

    /**
     * Clone the object with new coordinates.
     */
    public function withOrdinates($lat, $lon = null, $height = null)
    {
        $clone = clone $this;
        $clone->setOrdinates($lat, $lon, $height);
        return $clone;
    }

    /**
     * Return as an array.
     * The format should be in a format suitable for the constructor.
     */
    public function asArray()
    {
        return [
            'lat' => $this->lat,
            'lon' => $this->lon,
            'height' => $this->height,
            static::ELLIPSOID_PARAM_NAME => $this->ellipsoid->asArray(),
        ];
    }

    /*
     * The function Convert_Geodetic_To_Geocentric converts geodetic coordinates
     * (latitude, longitude, and height) to geocentric coordinates (X, Y, Z),
     * according to the current ellipsoid parameters.
     *
     *    Latitude  : Geodetic latitude in radians                     (input)
     *    Longitude : Geodetic longitude in radians                    (input)
     *    Height    : Geodetic height, in meters                       (input)
     *    X         : Calculated Geocentric X coordinate, in meters    (output)
     *    Y         : Calculated Geocentric Y coordinate, in meters    (output)
     *    Z         : Calculated Geocentric Z coordinate, in meters    (output)
     *
     */
    public function toGeocentric()
    {
        // Convert to radians.
        $lat = $this->lat * static::D2R;
        $lon = $this->lon * static::D2R;

        // Z value not always supplied
        $height = (isset($this->height) ? $this->height : 0);

        // GEOCENT_NO_ERROR;
        $Error_Code = 0;

        /*
         * * Don't blow up if Latitude is just a little out of the value
         * * range as it may just be a rounding issue.  Also removed longitude
         * * test, it should be wrapped by cos() and sin().  NFW for PROJ.4, Sep/2001.
         */

        if ($lat < -M_PI_2 && $lat > -1.001 * M_PI_2) {
            $lat = -M_PI_2;
        } elseif ($lat > M_PI_2 && $lat < 1.001 * M_PI_2) {
            $lat = M_PI_2;
        } elseif (($lat < -M_PI_2) || ($lat > M_PI_2)) {
            // Latitude out of range.
            throw new Exception (sprintf('geocent:lat (%s) out of range', $lat));
        }

        if ($lon > M_PI) {
            $lon -= (2 * M_PI);
        }

        $sin_lat = sin($lat);

        $cos_lat = cos($lat);

        // Square of sin(lat)
        $Sin2_Lat = $sin_lat * $sin_lat;

        $a = $this->ellipsoid->a;
        $es = $this->ellipsoid->es;

        // Earth radius at location
        $Rn = $a / (sqrt(1.0e0 - $es * $Sin2_Lat));

        $x = ($Rn + $height) * $cos_lat * cos($lon);
        $y = ($Rn + $height) * $cos_lat * sin($lon);
        $z = (($Rn * (1 - $es)) + $height) * $sin_lat;

        return new Geocentric($x, $y, $z, $this->ellipsoid);
    }

    /**
     * Create a Geodetic point from a Geocentric point.
     */
    public static function fromGeocentric(Geocentric $point)
    {
        // local defintions and variables
        // end-criterium of loop, accuracy of sin(Latitude)

        $genau = 1.0E-12;
        $genau2 = $genau * $genau;
        $maxiter = 30;

        $X = $point->x;
        $Y = $point->y;

        // Z value not always supplied
        $Z = ($point->z ? $point->z : 0.0);

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
        $lon;
        $lat;
        $height;
        */

        $a = $point->getEllipsoid()->a;
        $b = $point->getEllipsoid()->b;

        $at_pole = false;
        // The distance from the line joining the poles.
        $P = sqrt($X * $X + $Y * $Y);
        $RR = sqrt($X * $X + $Y * $Y + $Z * $Z);

        // Special cases for latitude and lonitude
        if ($P / $a < $genau) {
            // Special case: at the poles if P=0. (X=0, Y=0)
            $at_pole = true;
            $lon = 0.0;

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
            $lon = atan2($Y, $X);
        }

        // The eccentricity squared is used a lot in the calculations.
        $es = $point->getEllipsoid()->es;

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

        $geodetic_point = new static(
            $lat * static::R2D,
            $lon * static::R2D,
            $height,
            $point->getEllipsoid()
        );

        return $geodetic_point;
    }
}