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

use Proj4\Ellipsoid;
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
    public function __construct($x, $y = null, $z = null, Ellipsoid $ellipsoid = null)
    {
        $this->setOrdinates($x, $y, $z);

        // Was an ellipsoid passed in as an array element?
        // TODO: if the ellipse is an array of values, then use the array
        // to create a new Ellipse object.
        if (is_array($x) && isset($x[static::ELLIPSOID_PARAM_NAME]) && $x[static::ELLIPSOID_PARAM_NAME] instanceof Ellipsoid) {
            $ellipsoid = $x[static::ELLIPSOID_PARAM_NAME];
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
            static::ELLIPSOID_PARAM_NAME => $this->ellipsoid->asArray(),
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
}