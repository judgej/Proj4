<?php namespace Academe\Proj;

/**
 * The abstract projection providing base details that all projections share.
 * CHECKME: The projection - is it a point? Or is it something that a projected point inherits?
 */

use Academe\Proj\Traits\ProjectionTrait;
use Exception;
use Academe\Proj\Point\Geodetic;
use Academe\Proj\ProjectionInterface;

abstract class AbstractProjection implements ProjectionInterface
{
    use ProjectionTrait;
    // M_PI*2

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


}
