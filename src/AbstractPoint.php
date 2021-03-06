<?php namespace Academe\Proj;

/**
 * Methods and defines common to all (or most) points.
 */

use Exception;

abstract class AbstractPoint implements PointInterface
{
    /**
     * Degrees to/from radians conversion.
     * Deprecated (using PHP conversion functions).
     */

    const D2R = 0.01745329251994329577;
    const R2D = 57.29577951308232088;

    /**
     * The Proj4 name of the ellipsoid parameter.
     */

    const ELLIPSOID_PARAM_NAME = 'ellps';
    const DATUM_PARAM_NAME = 'datum';

    /**
     * The datum for this point.
     */
     protected $datum;

    /**
     * Set a new elllipsoid without doing any conversion.
     * FIXME: we are setting this in the Datum now. Do we need it here at all?
     */
    public function withEllipsoid(Ellipsoid $ellipsoid)
    {
        $clone = clone $this;
        $clone->ellipsoid = $ellipsoid;
        return $clone;
    }

    /**
     * Return the ellipsoid.
     */
    public function getEllipsoid()
    {
        return $this->datum->getEllipsoid();
    }

    /**
     * Set a new datum without doing any conversion.
     */
    public function withDatum(Datum $datum)
    {
        $clone = clone $this;
        $clone->datum = $datum;
        return $clone;
    }

    /**
     * Return the datum.
     */
    public function getDatum()
    {
        return $this->datum;
    }
}
