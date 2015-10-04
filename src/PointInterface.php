<?php namespace Proj4;

/**
 * Interface for a geographic point.
 */

use Exception;

use Proj4\Point\Geocentric;

interface PointInterface
{
    /**
     * Set a new elllipsoid without doing any conversion.
     * Returns: clone of self
     */
    public function withEllipsoid(Ellipsoid $ellipsoid);

    /**
     * Return the current ellispoid.
     * Returns: Ellipsoid
     */
    public function getEllipsoid();

    /**
     * Returns the point converted to a Geocentric (cartesian) point.
     * Returns: object Geocentric
     */
    public function toGeocentric();

    /**
     * Create a Geodetic (lat/lon/height) point from a Geocentric point.
     * Returns: object Geodetic
     */
    public static function fromGeocentric(Geocentric $point);
}
