<?php


namespace Academe\Proj;

/**
 * This class parses and holds a Proj4 configuration.
 * @package Academe\Proj
 */
class Proj4Config
{
    /**
     * @var string The source string that this object was constructed from.
     */
    private $source;

    /**
     * @var Semimajor radius of the ellipsoid axis
     */
    protected $a;

    /**
     * @var ? Used with Oblique Mercator and possibly a few others
     */
    protected $alpha;

    /**
     * @var Axis orientation (new in 4.8.0)
     */
    protected $axis;

    /**
     * @var Semiminor radius of the ellipsoid axis
     */
    protected $b;

    /**
     * @var stringDatum name (see `proj -ld`)
     */
    protected $datum;

    /**
     * @var string Ellipsoid name (see `proj -le`)
     */
    protected $ellps;

    /**
     * @var float Scaling factor (old name)
     */
    protected $k;

    /**
     * @var float Scaling factor (new name)
     */
    protected $k_0;

    /**
     * @var float Latitude of origin
     */
    protected$lat_0;

    /**
     * @var float Latitude of first standard parallel
     */
    protected $lat_1;

    /**
     * @var float Latitude of second standard parallel
     */
    protected $lat_2;

    /**
     * @var float Latitude of true scale
     */
    protected $lat_ts;

    /**
     * @var float Central meridian
     */
    protected $lon_0;

    /**
     * @var ? Longitude used with Oblique Mercator and possibly a few others
     */
    protected $lonc;

    /**
     * @var Center longitude to use for wrapping (see below)
     */
    protected $lon_wrap;

    /**
     * @var  Filename of NTv2 grid file to use for datum transforms (see below)
     */
    protected $nadgrids;

    /**
     * @var  Don't use the /usr/share/proj/proj_def.dat defaults file
     */
    protected $no_defs;

    /**
     * @var  Allow longitude output outside -180 to 180 range, disables wrapping (see below)
     */
    protected $over;

    /**
     * @var string Alternate prime meridian (typically a city name, see below)
     */
    protected $pm;

    /**
     * @var string Projection name (see `proj -l`)
     */
    protected $proj;
    /**
     * @var Denotes southern hemisphere UTM zone
     */
    protected $south;

    /**
     * @var float Multiplier to convert map units to 1.0m
     */
    protected $to_meter;

    /**
     * @var 3 or 7 term datum transform parameters (see below)
     */
    protected $towgs84;

    /**
     * @var meters, US survey feet, etc.
     */
    protected $units;

    /**
     * @var vertical conversion to meters.
     */

    protected $vto_meter;
    /**
     * @var vertical units.
     */

    protected $vunits;
    /**
     * @var int False easting
     */

    protected $x_0;
    /**
     * @var int False northing
     */

    protected $y_0;
    /**
     * @var  UTM zone
     */

    protected $zone;

    /**
     * @var string Title of this definition.
     */
    protected $title;

    /**
     * @var float
     */
    protected $from_greenwich = 0.0;
    
    public function __construct($configurationString)
    {
        $this->source = $configurationString;
        $params = $this->parseIntoArray($configurationString);
        foreach($params as $key => $value) {
            $attribute = lcfirst($key);
            $setter = 'set' . ucfirst($attribute);
            if (method_exists($this, $setter)) {
                $this->$setter($value);
            } elseif (property_exists($this, $attribute)) {
                $this->$attribute = $value;
            } else {
                throw new \Exception("Unknown parameter: '$key' for definition: $configurationString");
            }
        }

    }

    /**
     * Static because it does not need $this, public because someone might want to use it outside the context
     * of creating an instance of this class.
     * @param $string
     */
    public static function parseIntoArray($string)
    {
        preg_match_all('/\+(?<key>\w+)=(?<val>.*?)(?:\+|$)/', $string, $matches);
        $result = [];
        foreach($matches['key'] as $i => $key) {
            $result[$key] = self::convert($matches['val'][$i], $key);
            
        }
        return $result;
    }

    /**
     * This converts PROJ4 values to native PHP values.
     * The goal is to be flexible and speed is not a primary concern (at this point).
     * @param string $value
     * @param $key Optional key to help with deciding the format.
     * @return float|null|int|string
     */
    public static function convert($value, $key = null)
    {
        if (strpos($value, '/') !== false
            && count($parts = explode('/', $value)) === 2
            && is_numeric($parts[0])
            && is_numeric($parts[1])
        ) {
            return floatval($parts[0]) / floatval($parts[1]);
        } elseif (ctype_digit($value)) {
            return intval($value);
        } elseif (is_numeric($value)) {
            return floatval($value);
        } elseif (empty($value)) {
            return null;
        } elseif (preg_match('/^(-?[0-9]+(\.[0-9]*)?)(,(-?[0-9]+(\.[0-9]*)?))*$/', $value, $matches)) {
            return array_map('self::convert', explode(',', $value));
        } elseif (preg_match('/^(?<k>[a-zA-Z\d]*?)=(?<v>.*?)$/', $value, $matches)) {
            return [
                $matches['k'] => self::convert($matches['v'], $matches['k'])
            ];
        } else {
//            echo "Can't convert: $value\n";

        }
        return $value;
    }


}