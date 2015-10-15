<?php namespace Academe\Proj\Point;

/**
 * Define a projected point.
 * A projected point has a projection and a coordinate in whatever
 * units define it.
 */

use Exception;

use Academe\Proj\ProjectionInterface;

class Projected
{
    /**
     * The coordinate value - an array of parts.
     */

    protected $coords = [];

    /**
     * The possible coordinate value names.
     * This list comes from the projection.
     */
    protected $coordinate_names = [];

    /**
     * The projection object.
     */

    protected $projection;

    /**
     * The $projectino will contain metadata to tell us what params are
     * supported and how they should be validated.
     * CHECKME: do we need to intialise anything in the projection? For example,
     * do any of the projection parameters depend on the ellipsoid of the point?
     * Probably not, as the point will have to be WGS84 to go throwgh these conversions.
     */
    public function __construct(array $params, ProjectionInterface $projection)
    {
        // TODO: allow the projecion to be passed in with the params, as an
        // object or array to initialise an object.

        $this->projection = $projection;

        // Get the supported coordinate names.
        // The assumption for now is that that are not all mandatory.
        $this->coordinate_names = $projection->coordinateNames();

        // Now go through the coordinates and find the ones we need.
        foreach($params as $name => $value) {
            // Go through the possible names and aliases.
            $ordinate_name = null;

            foreach($this->coordinate_names as $k => $v) {
                if (is_numeric($k) && is_string($v) && $v == $name) {
                    // If the key is numeric and the value is a string,
                    // then there are no aliases - just check the value.
                    $ordinate_name = $name;
                    break;
                } elseif (is_string($v) && $v == $name) {
                    // No alias but use the key.
                    $ordinate_name = $k;
                    break;
                } elseif (is_array($v) && in_array(strtolower($name), $v)) {
                    // List of aliases is provided.
                    // Aliases are all lowercase.
                    $ordinate_name = $k;
                    break;
                }
            }

            if (isset($ordinate_name)) {
                $this->coords[$ordinate_name] = $value;
            } else {
                // We don't know what this parameter is.
                // Raise an exception.
                throw new Exception(sprintf(
                    'Unknown coordinate parameter "%s" for projection "%s"',
                    $name,
                    $this->projection->getName()
                ));
            }
        }

        // Now check if the coordiates are valid - within range, complete etc.
        // The projectino is in a position to do this.
        // The check will also enforce any datatypes and letter-case where necessary.
        // ...
    }

    /**
     * Translate invervse, i.e. back to a LatLon
     */
    public function inverse()
    {
        return $this->projection->inverse($this);
    }

    /**
     * Magic method to get a coordinate.
     */
    public function __get($name)
    {
        $lname = strtolower($name);

        if (array_key_exists($lname, $this->coords)) {
            return $this->coords[$lname];
        }

        $names = $this->supportedCoordinateNames();

        // Coordinate name not recognised.
        throw new Exception(sprintf(
            'Unknown coordinate part "%s" on projected point of type "%s"; supported properties are %s',
            $name,
            $this->projection->getName(),
            ( ! empty($names) ? '"' . implode('", "', $names) . '"' : '<none>')
        ));
    }

    /**
     * Get a list of supported coordinate part names - the underlying names, not the aliases.
     */
    public function supportedCoordinateNames()
    {
        $names = [];

        foreach($this->coordinate_names as $k => $v) {
            if (is_numeric($k) && is_string($v)) {
                $names[] = $v;
            } elseif (is_string($k) && is_array($v)) {
                $names[] = $k;
            }
        }

        return $names;
    }

    public function asArray()
    {
        return $this->coords;
    }
}
