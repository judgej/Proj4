<?php namespace Proj4;

/**
 * Defines a geodetic ellipsoidal datum.
 * This is an ellipsoid-based datum (there are other types).
 * The parameters are: the 3D cartesian location of the origin, the
 * orientation of the axes, and the ellipsoid.
 * A datum is defined by how it relates to the WGS84 datum - the worldwide
 * standard.
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
    //const TYPE_UNKNOWN = 0;
    //const TYPE_GRIDSHIFT = 3;
    // WGS84 or equivalent
    //const TYPE_WGS84 = 4;
    // WGS84 or equivalent
    //const TYPE_NODATUM = 5;

    // Convert parts per million to a multiplier.
    const PPM_TO_MULT = 0.0000001;

    // Convert seconds of arch to radians.
    // Pi/180/3600
    // Deprecated: using deg2rad($seconds_of_arc / 3600)
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
     * Parameters can be left empty, in which case the datum defaults to
     * WGS84 (which is the reference datum, so no transformations are needed).
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
     * All intialisation parameters can be passed in as a single array.
     * - Elements 0-2 or 0-6 are the 3-parameter transformation or 7-parameter Helmert transform values,
     *   in order (dx, dy, dz, a, b, g, s) where a/b/g are the rotations (sec) and s is the scaling (ppm).
     * - ellps is the ellipsoid, either as an Ellispoid object or an array for initialising.
     * - code and name are the code and name values, not used in calculations but useful when tracing data.
     */
    public function __construct(array $params = null, Ellipsoid $ellipsoid = null)
    {
        // The translation parameters we will collect.
        $trans = [];

        if (is_array($params)) {
            foreach($params as $key => $value) {
                switch (is_numeric($key) ? $key : strtolower($key)) {
                    case 0:
                    case 'x':
                    case 'dx':
                        $trans[0] = (float)$value;
                        break;
                    case 1:
                    case 'y':
                    case 'dy':
                        $trans[1] = (float)$value;
                        break;
                    case 2:
                    case 'z':
                    case 'dz':
                        $trans[2] = (float)$value;
                        break;
                    case 3:
                    case 'a':
                    case 'rx':
                        $trans[3] = (float)$value;
                        break;
                    case 4:
                    case 'b':
                    case 'ry':
                        $trans[4] = (float)$value;
                        break;
                    case 5:
                    case 'g':
                    case 'rz':
                        $trans[5] = (float)$value;
                        break;
                    case 6:
                    case 's':
                    case 'm':
                        $trans[6] = (float)$value;
                        break;
                    case AbstractPoint::ELLIPSOID_PARAM_NAME:
                        if ($value instanceof Ellipsoid) {
                            $ellipsoid = $value;
                        } elseif (is_array($value)) {
                            $ellipsoid = new Ellipsoid($value);
                        } else {
                            throw new Exception(sprintf(
                                '"%s" element passed to Datum with unexpected data type',
                                AbstractPoint::ELLIPSOID_PARAM_NAME
                            ));
                        }
                    case 'code':
                        $this->code = $value;
                        break;
                    case 'name':
                        $this->name = $value;
                        break;
                }
            }
        }

        // An unspecified ellipsoid will default to WGS84.
        if (empty($ellipsoid)) {
            // The default ellipsoid is a WGS84.
            $ellipsoid = new Ellipsoid;
        }

        $this->ellipsoid = $ellipsoid;

        // Unspecified params will default to the WGS85 datum, with no transformations.
        if (empty($trans)) {
            $trans = [0, 0, 0];
            $this->code = 'WGS84';
            $this->name = 'WGS84';
        }

        // Maybe collect instead elements "0" to "2" or "0" to "6", Dx, Dy etc.
        // Then the parameters could hold other arbitrary elements that could be useful.
        // The Ordnance Survey lists the parameters in order (tx, ty, tz, S, rx, ry, rz)
        // which differs by the numeric order Proj.4 uses with S on the end, so anything
        // that could help avoid ambiguity would be good.

        if (count($trans) == 3) {
            // Geocentric translation only.
            $this->type = static::TYPE_3TERM;
        } elseif(count($trans) == 7) {
            // Helmert 7 parameter transform
            $this->type = static::TYPE_7TERM;
        } else {
            // Wrong number of parameter values.
            throw new Exception(sprintf(
                'Invalid parameter term count ("%d"); 3 or 7 terms supported', count($trans)
            ));
        }

        $this->params = $trans;
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
     * Return the object as an array.
     */
    public function AsArray()
    {
        $array = $this->params + [AbstractPoint::ELLIPSOID_PARAM_NAME => $this->ellipsoid->asArray()];

        if ($this->getCode()) {
            $array['code'] = $this->getCode();
        }

        if ($this->getName()) {
            $array['name'] = $this->getName();
        }

        return $array;
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
     * Set a new elllipsoid without doing any conversion.
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

    /**
     * Return a new geocentric point transformed to the WGS84 datum.
     * TODO: if the point already is expressed in this datum, then
     * no conversion is required.
     * TODO: take measurement units into account? Internally it should all be metres.
     * TODO: all same rules for fromWgs84()
     * TODO: if we are already WGS84, then there are no translations necessary.
     */
    public function toWgs84(Geocentric $point)
    {
        if ($this->type == static::TYPE_3TERM) {
            $x = $point->x + $this->params[0];
            $y = $point->y + $this->params[1];
            $z = $point->z + $this->params[2];
        } elseif ($this->type == static::TYPE_7TERM) {
            $Dx_BF = $this->params[0];
            $Dy_BF = $this->params[1];
            $Dz_BF = $this->params[2];

            // The rotation parameters need converting from seconds of arc to radians.
            // Seconds of arc is the standard format in which datum rotations are supplied.
            $Rx_BF = deg2rad($this->params[3] / 3600);
            $Ry_BF = deg2rad($this->params[4] / 3600);
            $Rz_BF = deg2rad($this->params[5] / 3600);

            // Convert parts-per-million scaling factor to a multiplier.
            $M_BF = 1 + ($this->params[6] * static::PPM_TO_MULT);

            $x = $M_BF * ($point->x - $Rz_BF * $point->y + $Ry_BF * $point->z) + $Dx_BF;
            $y = $M_BF * ($Rz_BF * $point->x + $point->y - $Rx_BF * $point->z) + $Dy_BF;
            $z = $M_BF * (-$Ry_BF * $point->x + $Rx_BF * $point->y + $point->z) + $Dz_BF;
        } else {
            throw new Exception('Unknown datum transformation parameters type');
        }

        // Return a new point, with the new coordinates, and with a default WGS84 datum.
        // Even if nothing has chanegd, we return a clone.
        $new_point = $point
            ->withOrdinates($x, $y, $z)
            ->withDatum(new Datum);

        return $new_point;
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
        } elseif ($this->type == static::TYPE_7TERM) {
            $Dx_BF = $this->params[0];
            $Dy_BF = $this->params[1];
            $Dz_BF = $this->params[2];

            // These need converting from seconds of arc to radians.
            $Rx_BF = $this->params[3] * static::SEC_TO_RAD;
            $Ry_BF = $this->params[4] * static::SEC_TO_RAD;
            $Rz_BF = $this->params[5] * static::SEC_TO_RAD;

            // Convert parts per million to a multiplier
            $M_BF = 1 + ($this->params[6] * static::PPM_TO_MULT);

            $x_tmp = ($point->x - $Dx_BF) / $M_BF;
            $y_tmp = ($point->y - $Dy_BF) / $M_BF;
            $z_tmp = ($point->z - $Dz_BF) / $M_BF;

            $x = $x_tmp + $Rz_BF * $y_tmp - $Ry_BF * $z_tmp;
            $y = -$Rz_BF * $x_tmp + $y_tmp + $Rx_BF * $z_tmp;
            $z = $Ry_BF * $x_tmp - $Rx_BF * $y_tmp + $z_tmp;
        } else {
            throw new Excreption('Unknown datum transformation parameter type');
        }

        // Give the point the new datum.
        $new_point = $point
            ->withOrdinates($x, $y, $z)
            ->withDatum($this);

        return $new_point;
    }
}
