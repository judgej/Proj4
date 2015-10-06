<?php namespace Proj4\Point;

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

use Proj4\Datum;
use Proj4\AbstractPoint;

class Geocentric extends AbstractPoint
{
    /**
     * The coordinates.
     */

    protected $x;
    protected $y;
    protected $z;

    /**
     * Initialise with (x, y, z) parameters or [x, y, z] array.
     */
    public function __construct($x, $y = null, $z = null, Datum $datum = null)
    {
        $this->setOrdinates($x, $y, $z);

        // Was a datum passed in as an array element?
        if (is_array($x) && isset($x[static::DATUM_PARAM_NAME])) {
            $datum_param = $x[static::DATUM_PARAM_NAME];

            if ($datum_param instanceof Datum) {
                // An Datum object was supplied.
                $datum = $datum_param;
            } elseif (is_array($datum_param)) {
                // If the datum is an array of values, then use the array
                // to create a new Datum object.
                $datum = new Datum($datum);
            }
        }

        // If no datum supplied, then create a default (will be WGS84).
        if ( ! isset($datum)) {
            $datum = new Datum;
        }

        $this->datum = $datum;
    }

    /**
     * Set the (x, y, z) values.
     * This is protected for immutability.
     */
    protected function setOrdinates($x, $y = null, $z = null)
    {
        if (is_array($x)) {
            $values = array_values($x);

            if (isset($values[0])) {
                $x = $values[0];
            }

            if (isset($values[1])) {
                $y = $values[1];
            }

            if (isset($values[2])) {
                $z = $values[2];
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

        // Exception - invalid property.
        throw new Exception(sprintf('Unknown or invalid property "%s"', $name));
    }

    /**
     * Return as an array.
     * The format should be in a format suitable for the constructor.
     */
    public function asArray()
    {
        return [
            'x' => $this->x,
            'y' => $this->y,
            'z' => $this->z,
            static::DATUM_PARAM_NAME => $this->datum->asArray(),
        ];
    }

    /**
     * No conversion necessary.
     */
    public function toGeocentric()
    {
        return clone $this;
    }

    /**
     * No conversion necessary.
     */
    public static function fromGeocentric(Geocentric $point)
    {
        return clone $point;
    }

    /**
     * Shift to the default WGS84 datum.
     * Returns: a clone of the point, shifted to the WGS84 datum.
     */
    public function toWgs84()
    {
        return $this->datum->toWgs84($this);
    }

    /**
     * Shift from WGS84 to another datum.
     * Returns: a clone of the point, shifted from the WGS84 datum.
     */
    public function toDatum(Datum $datum)
    {
        // Make sure we are WGS84 before attemping a datum shift.
        $point = $this->toWgs84();

        return $datum->fromWgs84($point);
    }
}