<?php
namespace Academe\Proj\Traits;

/**
 * Utility methods used by many of the forward/inverse conversions.
 */
trait ProjectionTrait
{
    /**
     * Adjust longitude to -PI to +PI (-180 to +180 degrees); input in radians.
     */
    protected function adjustLon($lon)
    {
        return (abs($lon) < M_PI) ? $lon : ($lon - ($this->sign($lon) * M_PI * 2));
    }

    /**
     * Adjust latitude to -PI/2 to +PI/2 (-90 to +90 degrees); input in radians.
     */
    protected function adjustLat($lat)
    {
        return (abs($lat) < M_PI_2) ? $lat : ($lat - ($this->sign($lat) * M_PI));
    }

    protected function sign($num)
    {
        return $num < 0.0 ? -1 : 1;
    }

    /**
     * following functions from gctpc cproj.c for transverse mercator projections
     *
     * @param type $x == es == essentricity squared
     * @return type
     */
    protected function e0fn($x)
    {
        return (1.0 - ($x / 4.0) * (1.0 + $x / 16.0 * (3.0 + 1.25 * $x)));
    }

    /**
     * @param type $x
     * @return type
     */
    protected function e1fn($x)
    {
        return (0.375 * $x * (1.0 + 0.25 * $x * (1.0 + 0.46875 * $x)));
    }

    /**
     * @param type $x
     * @return type
     */
    protected function e2fn($x)
    {
        return (0.05859375 * $x * $x * (1.0 + 0.75 * $x));
    }

    /**
     * @param type $x
     * @return type
     */
    protected function e3fn($x)
    {
        return ($x * $x * $x * (35.0 / 3072.0));
    }

    /**
     * Function to eliminate roundoff errors in asin
     *
     * @param type $x
     * @return type
     */
    protected function asinz($x)
    {
        return asin(
            abs($x) > 1.0 ? ($x > 1.0 ? 1.0 : -1.0) : $x
        );

        //if( abs( $x ) > 1.0 ) {
        //    $x = ($x > 1.0) ? 1.0 : -1.0;
        //}
        //return asin( $x );
    }

    /**
     * @param type $e0
     * @param type $e1
     * @param type $e2
     * @param type $e3
     * @param type $phi
     * @return type
     */
    protected function mlfn($e0, $e1, $e2, $e3, $phi)
    {
        return ($e0 * $phi - $e1 * sin(2.0 * $phi) + $e2 * sin(4.0 * $phi) - $e3 * sin(6.0 * $phi));
    }
}