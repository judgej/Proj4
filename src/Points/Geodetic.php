<?php namespace Proj4\Points;

/**
 * Defines a geodetic point.
 * These are defined by a latitude (northing), longitude (easting) and optional
 * height (elevation from the reference ellipsoid).
 * We should always know whay datum we are using, so we know what conversions are (and are not)
 * necesaary when we are transforming coordinates.
 */

use Exception;

use Proj4\Ellipsoid;

class Geodetic
{
    /**
     * Degrees to radians.
     */

    const D2R = 0.01745329251994329577;

    /**
     * The coordinates.
     */

    protected $long;
    protected $lat;
    protected $height;

    /**
     * The ellipsoid for this point.
     */
     protected $ellipsoid;

    /**
     * Initialise with (x, y, z) or [x, y, z].
     */
    public function __construct($long, $lat = null, $height = null, Ellipsoid $ellipsoid = null)
    {
        $this->setOrdinates($long, $lat, $height);

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
    protected function setOrdinates($long, $lat = null, $height = null)
    {
        if (is_array($long)) {
            $values = array_values($long);

            if (isset($values[2])) {
                $height = $values[2];
            }

            if (isset($values[1])) {
                $lat = $values[1];
            }

            if (isset($values[0])) {
                $long = $values[0];
            }
        }

        $this->long = $long;
        $this->lat = $lat;
        $this->height = $height;
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
        $long = $this->long * static::D2R;
        $lat = $this->lat * static::D2R;

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

        if ($long > M_PI) {
            $long -= (2 * M_PI);
        }

        $sin_lat = sin($lat);

        $cos_lat = cos($lat);

        // Square of sin(lat)
        $Sin2_Lat = $sin_lat * $sin_lat;

        $a = $this->ellipsoid->a;
        $es = $this->ellipsoid->es;

        // Earth radius at location
        $Rn = $a / (sqrt(1.0e0 - $es * $Sin2_Lat));

        $x = ($Rn + $height) * $cos_lat * cos($long);
        $y = ($Rn + $height) * $cos_lat * sin($long);
        $z = (($Rn * (1 - $es)) + $height) * $sin_lat;

        return new Geocentric($x, $y, $z, $this->ellipsoid);
    }
}