<?php namespace Proj4;

/**
 * Methods and defines common to all (or most) points.
 */

use Exception;

abstract class AbstractPoint
{
    /**
     * Degrees to/from radians conversion.
     */

    const D2R = 0.01745329251994329577;
    const R2D = 57.29577951308232088;

    /**
     * The Proj4 name of the ellipsoid parameter.
     */

    const ELLIPSOID_PARAM_NAME = 'ellps';

    /**
     * The ellipsoid for this point.
     */
     protected $ellipsoid;

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
     * Return the ellispoid.
     */
    public function getEllipsoid()
    {
        return $this->ellipsoid;
    }

}
