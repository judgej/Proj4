<?php namespace Academe\Proj;

/**
 * The abstract projection providing base details that all projections share.
 * CHECKME: The projection - is it a point? Or is it something that a projected point inherits?
 */

use Exception;
use Academe\Proj\Point\Geodetic;
use Academe\Proj\ProjectionInterface;

abstract class AbstractProjection implements ProjectionInterface
{
    // M_PI*2
    const TWO_PI = 6.283185307179586477;

    const EPSLN = 1.0e-10;

    /**
     * The names of coordinate elements.
     * Each element can be a name, with a numeric key, e.g. ['x', 'y']
     * or a name as the key and a list of aliases, e.g. ['lat' => ['lat', 'latitude', 0]]
     * When creating a Projected point, this defines the names of the parts that make
     * up a coordinate in that projection.
     */
    protected $coord_names = [];

    /**
     * The long name of the projection.
     * Override for each projection.
     */
    protected $projection_name = 'AbstractProjection';

    /**
     * The parameters for a projection.
     */
    protected $params = [];

    /**
     * Initialise the projection with conversion parameters, defaulting
     * where necessary.
     * The concrete class will provide metadata to tell us what parameters
     * to support and how to validate them. Reflection may help here to give
     * us a list of properties at least.
     */
    public function __construct(array $params = null)
    {
        // Set up initial parameters.
        foreach($params as $name => $value) {
            $this->setParam($name, $value);
        }
    }

    /*
     * Return the list of possible parameter names that a coordinate
     * is constructed from.
     */
    public function coordinateNames()
    {
        return $this->coord_names;
    }

    /**
     * The name of the projection.
     */
    public function getName()
    {
        return $this->projection_name;
    }

    /**
     * Set a simgle parameter.
     * This is protected to support immutability.
     * We can also implement validation here, so make sure numbers
     * are in range etc.
     */
    protected function setParam($name, $value)
    {
        // Check the parameter exists by trying to get it.
        $old_value = $this->$name;

        // If no exception raised before we get here, then we should be able to set it.
        $this->params[$name] = $value;
    }

    /**
     * Set one or more parameters.
     */

    /**
     * Magic method to get a parameter.
     */
    public function __get($name)
    {
        if (array_key_exists($name, $this->params)) {
            return $this->params[$name];
        } else {
            throw new Exception(sprintf(
                'The parameter "%s" for projection "%s" is not recognised; supported parameters are %s',
                $name,
                $this->getName(),
                ! empty($this->params) ? '"' . implode('", "', array_keys($this->params)) . '"' : '<none>'
            ));
        }
    }

    /**
     * Magic method to tell if a parameter is set.
     */
    public function __isset($name)
    {
        $value = $this->$name;

        return isset($value);
    }

    /**
     * Return parameters as an array.
     */
    public function asArray()
    {
        return $this->params;
    }

    /**
     * Utility methods used by many of the forward/inverse conversions.
     */

    protected function adjust_lon($x)
    {
        return (abs($x) < M_PI) ? $x : ($x - ($this->sign($x) * statis::TWO_PI));
    }
 
    protected function sign($x)
    {
        return $x < 0.0 ? -1 : 1;
    }


    /**
     * following functions from gctpc cproj.c for transverse mercator projections
     * 
     * @param type $x
     * @return type
     */
    protected function e0fn($x)
    {
        return (1.0 - 0.25 * $x * (1.0 + $x / 16.0 * (3.0 + 1.25 * $x)));
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
