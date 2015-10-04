<?php namespace Proj4;

/**
 * Defines a datum.
 * TODO: a "from name" static method to return a datum by name, e.g. NAD83.
 * These would include an ellipsoid by name. Or maybe not - perhaps that is
 * the job of a Proj4 (or other syntax) parameter parser service. Let's just
 * stick to what the data is in these objects, and not where it comes from.
 */

use Exception;
use Proj4\Point\Geocentric;

class Datum
{
    /**
     * The datum types.
     */
    const TYPE_3TERM = 1;
    const TYPE_7TERM = 2;

    // Not used (yet).
    const TYPE_UNKNOWN = 0;
    const TYPE_GRIDSHIFT = 3;
    // WGS84 or equivalent
    const TYPE_WGS84 = 4;
    // WGS84 or equivalent
    const TYPE_NODATUM = 5;

    // Convert parts per million to a multiplier.
    const PPM_TO_MULT = 1.0000001;

    // Convert seconds of arch to radians.
    // Pi/180/3600
    const SEC_TO_RAD = 4.84813681109535993589914102357e-6;

    /**
     * Current datum type.
     */
    protected $type;

    /**
     * Current parameters.
     * The parameters define how to convert geocentric coordinates TO the
     * WGS84 datum. Converting back will involve reversing the polarity.
     *
     * The elements for 3-term parameters are:
     * 0: x geocentric translation, metres
     * 1: y geocentric translation, metres
     * 2: z geocentric translation, metres
     *
     * And for 7-term parameters datums:
     * 0: Dx geocentric translation, metres
     * 1: Dy geocentric translation, metres
     * 2: Dz geocentric translation, metres
     * 3: Rx rotation, seconds of arc (1/3600 degrees)
     * 4: Ry rotation, seconds of arc
     * 5: Rz rotation, seconds of arc
     * 6: M scaling, parts per million
     * 
     * The units will be preserved (immutable) in this property - no conversions
     * until they are needed.
     */
    protected $params;

    /**
     * The short name of the datum.
     * Example: OSGB36
     */
    protected $code;

    /**
     * The long name of the datum.
     * Example: Airy 1830
     */
    protected $name;

    /**
     * The ellipsoid for this datum. Needed for converting a point between
     * geodetic (lat/long/height) and geocentric (x/y/z) coordinates.
     */
    protected $ellipsoid;

    /**
     * A datum needs parameters to be defined.
     * These will be three parameters offering a spacial shift, or seven
     * parameters offering a spacial shift + reotations + scale.
     * Paremeters can be supplied as an array, or a CSV scring.
     * Parameters can be left empty, in which case the datum defaults to
     * WGS84 (which is the reference datum, so no transformations are needed).
     * TODO: handle the default datum (when no parameters supplied).
     * TODO: move the parsing to a separate method so parameters can be changed later.
     * TODO: handle the ellipse, as this is needed for geodetic/geocentric conversions
     * of points. Except - is the ellipse carried by the datum or the point? I think
     * it puts the point into context and is used only when converting between coordinate
     * systems (geodetic and geocentric).
     * TODO: some datums are derived from lookup tables (looking up point in grids) rather
     * than a simple numeric transform, e.g. "@conus,@alaska,@ntv2_0.gsb,@ntv1_can.dat".
     * Named datums seem to have named ellipses too. Not sure if that is necessary or whether
     * it is just a Proj.4 convenience.
     * According to definitions, a datum is a surface, and so includes an ellipsoid (or other
     * models to describe the geocentric height). So we will always include an ellipsoid
     * when defining a datum. WGS84 is the average approximate sea level ellipsoid which
     * is used as a reference. WGS84 ellipsoid is applied by default in Proj.4 if no
     * alternative is specified.
     */
    public function __construct($params = null, Ellipsoid $ellipsoid = null)
    {
        // An unspecified ellipsoid will default to WGS84.
        if (empty($ellipsoid)) {
            // The default ellipsoid is a WGS84.
            $ellipsoid = new Ellipsoid;
        }

        // Unspecified params will default to the WGS85 datum, with no transformations.
        if ( ! isset($params)) {
            $params = [0, 0, 0];
            $this->name = 'WGS84';
            $this->code = 'WGS84';
        }

        $this->ellipsoid = $ellipsoid;

        if (is_string($params)) {
            // Supplied as CSV string, with no spaces in the string.
            $params = explode(',', $params);
        }

        if ( ! is_array($params)) {
            // No idea what the parameter is.
            throw new Exception('Invalid parameter type');
        }

        if (count($params) == 3) {
            $this->type = static::TYPE_3TERM;
        } elseif(count($params) == 7) {
            $this->type = static::TYPE_7TERM;
        } else {
            // Wrong number of parameter values.
            throw new Exception(sprintf('Invalid parameter term count ("%d"); 3 or 7 terms supported', count($params)));
        }

        // Make sure each parameter is a float when storing them.
        foreach($params as $param) {
            $this->params[] = (float)$param;
        }
    }

    /**
     * Return the translation parameters as an array.
     */
    public function paramsAsArray()
    {
        return $this->params;
    }

    /**
     * Return the translation parameters as a CSV string.
     */
    public function paramsAsString()
    {
        return implode(',', $this->paramsAsArray());
    }

    /**
     * Get short name.
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Add a short name.
     */
    public function withCode($code)
    {
        $clone = clone $this;
        $clone->code = $code;
        return $clone;
    }

    /**
     * Get long name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add a long name.
     */
    public function withName($name)
    {
        $clone = clone $this;
        $clone->name = $name;
        return $clone;
    }

    /**
     * Returns true if supplied datum is the same as this datum.
     */
    public function equals(Datum $datum)
    {
        // TODO
    }

    /**
     * Return a new geocentric point transformed to the WGS84 datum.
     * TODO: if the point already is expressed in this datum, then
     * no conversion is required.
     * TODO: typehint so we only get a geocentric point.
     * TODO: take measurement units into account? Internally it should all be metres.
     * TODO: return a clone of the point.
     * TODO: all same rules for fromWgs84()
     */
    public function toWgs84(Geocentric $point)
    {
        if ($this->type == static::TYPE_3TERM) {
            $x = $point->x + $this->params[0];
            $y = $point->y + $this->params[1];
            $z = $point->z + $this->params[2];

            $point = $point->withOrdinates($x, $y, $z);
        }

        if ($this->type == static::TYPE_7TERM) {
            $Dx_BF = $this->params[0];
            $Dy_BF = $this->params[1];
            $Dz_BF = $this->params[2];

            // These need converting from seconds of arc to radians.
            $Rx_BF = $this->params[3] * static::SEC_TO_RAD;
            $Ry_BF = $this->params[4] * static::SEC_TO_RAD;
            $Rz_BF = $this->params[5] * static::SEC_TO_RAD;

            // Convert parts per million to a multiplier
            $M_BF = $this->params[6] * static::PPM_TO_MULT;

            $x = $M_BF * ($point->x - $Rz_BF * $point->y + $Ry_BF * $point->z) + $Dx_BF;
            $y = $M_BF * ($Rz_BF * $point->x + $point->y - $Rx_BF * $point->z) + $Dy_BF;
            $z = $M_BF * (-$Ry_BF * $point->x + $Rx_BF * $point->y + $point->z) + $Dz_BF;

            $point = $point->withOrdinates($x, $y, $z);
        }

        return $point;
    }

    /**
     * Convert a geocentric point from the WGS84 datum.
     */
    public function fromWgs84(Geocentric $point)
    {
        if ($this->type == static::TYPE_3TERM) {
            $x = $point->x - $this->params[0];
            $y = $point->y - $this->params[1];
            $z = $point->z - $this->params[2];

            $point = $point->withOrdinates($x, $y, $z);
        }

        if ($this->type == static::TYPE_7TERM) {
            $Dx_BF = $this->params[0];
            $Dy_BF = $this->params[1];
            $Dz_BF = $this->params[2];

            // These need converting from seconds of arc to radians.
            $Rx_BF = $this->params[3] * static::SEC_TO_RAD;
            $Ry_BF = $this->params[4] * static::SEC_TO_RAD;
            $Rz_BF = $this->params[5] * static::SEC_TO_RAD;

            // Convert parts per million to a multiplier
            $M_BF = $this->params[6] * static::PPM_TO_MULT;

            $x_tmp = ($point->x - $Dx_BF) / $M_BF;
            $y_tmp = ($point->y - $Dy_BF) / $M_BF;
            $z_tmp = ($point->z - $Dz_BF) / $M_BF;

            $x = $x_tmp + $Rz_BF * $y_tmp - $Ry_BF * $z_tmp;
            $y = -$Rz_BF * $x_tmp + $y_tmp + $Rx_BF * $z_tmp;
            $z = $Ry_BF * $x_tmp - $Rx_BF * $y_tmp + $z_tmp;

            $point = $point->withOrdinates($x, $y, $z);
        }

        return $point;
    }
}
