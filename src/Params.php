<?php namespace Academe\Proj;

/**
 * Provide a library for lists of named parameters.
 * Some will be hard-coded in here, some will be in files,
 * some can be registered by the application and some may
 * involve a remote API lookup.
 * This class does not create the geo objects, but provides
 * the data (arrays) that can be used to initialise them.
 */

use Exception;

class Params
{
    // All ellipsoid codes are lower-case.

    protected $ellipsoids = [
        'merit' => ['a' => 6378137.0, 'rf' => 298.257, 'name' => 'MERIT 1983'],
        'sgs85' => ['a' => 6378136.0, 'rf' => 298.257, 'name' => 'Soviet Geodetic System 85'],
        'grs80' => ['a' => 6378137.0, 'rf' => 298.257222101, 'name' => 'GRS 1980(IUGG, 1980)'],
        'iau76' => ['a' => 6378140.0, 'rf' => 298.257, 'name' => 'IAU 1976'],
        'airy' => ['a' => 6377563.396, 'b' => 6356256.910, 'name' => 'Airy 1830'],
        'apl4.' => ['a' => 6378137, 'rf' => 298.25, 'name' => 'Appl. Physics. 1965'],
        'nwl9d' => ['a' => 6378145.0, 'rf' => 298.25, 'name' => 'Naval Weapons Lab., 1965'],
        'mod_airy' => ['a' => 6377340.189, 'b' => 6356034.446, 'name' => 'Modified Airy'],
        'andrae' => ['a' => 6377104.43, 'rf' => 300.0, 'name' => 'Andrae 1876 (Den., Iclnd.)'],
        'aust_sa' => ['a' => 6378160.0, 'rf' => 298.25, 'name' => 'Australian Natl & S. Amer. 1969'],
        'grs67' => ['a' => 6378160.0, 'rf' => 298.2471674270, 'name' => 'GRS 67(IUGG 1967)'],
        'bessel' => ['a' => 6377397.155, 'rf' => 299.1528128, 'name' => 'Bessel 1841'],
        'bess_nam' => ['a' => 6377483.865, 'rf' => 299.1528128, 'name' => 'Bessel 1841 (Namibia)'],
        'clrk66' => ['a' => 6378206.4, 'b' => 6356583.8, 'name' => 'Clarke 1866'],
        'clrk80' => ['a' => 6378249.145, 'rf' => 293.4663, 'name' => 'Clarke 1880 mod.'],
        'cpm' => ['a' => 6375738.7, 'rf' => 334.29, 'name' => 'Comm. des Poids et Mesures 1799'],
        'delmbr' => ['a' => 6376428.0, 'rf' => 311.5, 'name' => 'Delambre 1810 (Belgium)'],
        'engelis' => ['a' => 6378136.05, 'rf' => 298.2566, 'name' => 'Engelis 1985'],
        'evrst30' => ['a' => 6377276.345, 'rf' => 300.8017, 'name' => 'Everest 1830'],
        'evrst48' => ['a' => 6377304.063, 'rf' => 300.8017, 'name' => 'Everest 1948'],
        'evrst56' => ['a' => 6377301.243, 'rf' => 300.8017, 'name' => 'Everest 1956'],
        'evrst69' => ['a' => 6377295.664, 'rf' => 300.8017, 'name' => 'Everest 1969'],
        'evrstSS' => ['a' => 6377298.556, 'rf' => 300.8017, 'name' => 'Everest (Sabah & Sarawak)'],
        'fschr60' => ['a' => 6378166.0, 'rf' => 298.3, 'name' => 'Fischer (Mercury Datum) 1960'],
        'fschr60m' => ['a' => 6378155.0, 'rf' => 298.3, 'name' => 'Fischer 1960'],
        'fschr68' => ['a' => 6378150.0, 'rf' => 298.3, 'name' => 'Fischer 1968'],
        'helmert' => ['a' => 6378200.0, 'rf' => 298.3, 'name' => 'Helmert 1906'],
        'hough' => ['a' => 6378270.0, 'rf' => 297.0, 'name' => 'Hough'],
        'intl' => ['a' => 6378388.0, 'rf' => 297.0, 'name' => 'International 1909 (Hayford)'],
        'kaula' => ['a' => 6378163.0, 'rf' => 298.24, 'name' => 'Kaula 1961'],
        'lerch' => ['a' => 6378139.0, 'rf' => 298.257, 'name' => 'Lerch 1979'],
        'mprts' => ['a' => 6397300.0, 'rf' => 191.0, 'name' => 'Maupertius 1738'],
        'new_intl' => ['a' => 6378157.5, 'b' => 6356772.2, 'name' => 'New International 1967'],
        'plessis' => ['a' => 6376523.0, 'rf' => 6355863.0, 'name' => 'Plessis 1817 (France)'],
        'krass' => ['a' => 6378245.0, 'rf' => 298.3, 'name' => 'Krassovsky, 1942'],
        'seasia' => ['a' => 6378155.0, 'b' => 6356773.3205, 'name' => 'Southeast Asia'],
        'walbeck' => ['a' => 6376896.0, 'b' => 6355834.8467, 'name' => 'Walbeck'],
        'wgs60' => ['a' => 6378165.0, 'rf' => 298.3, 'name' => 'WGS 60'],
        'wgs66' => ['a' => 6378145.0, 'rf' => 298.25, 'name' => 'WGS 66'],
        'wgs72' => ['a' => 6378135.0, 'rf' => 298.26, 'name' => 'WGS 72'],
        'wgs84' => ['a' => 6378137.0, 'rf' => 298.257223563, 'name' => 'WGS 84'],
        'sphere' => ['a' => 6370997.0, 'b' => 6370997.0, 'name' => 'Normal Sphere (r=6370997)'],
    ];

    // Mixed or upper-case ellipsoid codes go into the aliases list.

    protected $ellipsoid_alias = [
        'MERIT' => 'merit',
        'SGS85' => 'sgs85',
        'GRS80' => 'grs80',
        'IAU76' => 'iau76',
        'APL4.' => 'apl4.',
        'NWL9D' => 'nwl9d',
        'aust_SA' => 'aust_sa',
        'GRS67' => 'grs67',
        'CPM' => 'cpm',
        'SEasia' => 'seasia',
        'WGS60' => 'wgs60',
        'WGS66' => 'wgs66',
        'WGS72' => 'wgs72',
        'WGS84' => 'wgs84',
    ];

    // The datums.

    protected $datums = [
        'wgs84' => [
            'towgs84' => [0.0, 0.0, 0.0],
            'ellipsoid' => 'WGS84',
            'name' => 'WGS84'
        ],
        'ggrs87' => [
            'towgs84' => [-199.87, 74.79, 246.62],
            'ellipsoid' => 'GRS80',
            'name' => 'Greek_Geodetic_Reference_System_1987'
        ],
        'nad83' => [
            'towgs84' => [0.0, 0.0, 0.0],
            'ellipsoid' => 'GRS80',
            'name' => 'North_American_Datum_1983'
        ],
        'nad27' => [
            'nadgrids' => '@conus,@alaska,@ntv2_0.gsb,@ntv1_can.dat',
            'ellipsoid' => 'clrk66',
            'name' => 'North_American_Datum_1927'
        ],
        'potsdam' => [
            'towgs84' => [606.0, 23.0, 413.0],
            'ellipsoid' => 'bessel',
            'name' => 'Potsdam Rauenberg 1950 DHDN'
        ],
        'carthage' => [
            'towgs84' => [-263.0, 6.0, 431.0],
            'ellipsoid' => 'clark80',
            'name' => 'Carthage 1934 Tunisia'
        ],
        'hermannskogel' => [
            'towgs84' => [653.0, -212.0, 449.0],
            'ellipsoid' => 'bessel',
            'name' => 'Hermannskogel'
        ],
        'ire65' => [
            'towgs84' => [482.530, -130.596, 564.557, -1.042, -0.214, -0.631, 8.15],
            'ellipsoid' => 'mod_airy',
            'name' => 'Ireland 1965'
        ],
        'nzgd49' => [
            'towgs84' => [59.47, -5.04, 187.44, 0.47, -0.1, 1.024, -4.5993],
            'ellipsoid' => 'intl',
            'name' => 'New Zealand Geodetic Datum 1949'
        ],
        'osgb36' => [
            'towgs84' => [446.448, -125.157, 542.060, 0.1502, 0.2470, 0.8421, -20.4894],
            'ellipsoid' => 'airy',
            'name' => 'Airy 1830'
        ],
    ];

    protected $datum_alias = [
        'OSGB36' => 'osgb36',
        'WGS84' => 'wgs84',
        'GGRS87' => 'ggrs87',
        'NAD83' => 'nad83',
        'NAD27' => 'nad27',
    ];

    /**
     * Construct the database.
     */
    public function __construct()
    {
    }

    /**
     * Return ellipsoid parameters.
     */
    public function ellipsoid($key)
    {
        if (isset($this->ellipsoids[strtolower($key)])) {
            return $this->ellipsoids[$key] + ['code' => $key];
        } elseif (isset($this->$ellipsoid_alias[$key])) {
            // Be careful no alias point to itself, otherwise you get an endless loop here.
            return $this->ellipsoid($this->$ellipsoid_alias[$name]);
        }

        return [];
    }

    /**
     * Return datum parameters.
     */
    public function datum($key)
    {
        if (isset($this->datums[strtolower($key)])) {
            $datum = $this->datums[strtolower($key)];

            if ( ! isset($datum['code'])) {
                $datum['code'] = $key;
            }

            // Expand the ellipsoid key.
            if (is_string($datum['ellipsoid'])) {
                $datum['ellipsoid'] = $this->ellipsoid($datum['ellipsoid']);
            }

            return $datum;
        } elseif (isset($this->$datum_alias[$key])) {
            // Be careful no alias point to itself, otherwise you get an endless loop here.
            return $this->datum($this->$datum_alias[$key]);
        }

        return [];
    }
}
