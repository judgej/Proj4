# Proj (Geographic Coordinate Projection Conversion)

Personal experiments in a Proj4 reboot.

## Introduction

Helping to bring the [Proj4php](https://github.com/proj4php/proj4php) up to dat ewith composer
and namespaces, I realised that it carried a lot of its JavaScript roots with it as baggage.
This included the use of static variables as globals, public properties all over the place,
objects updating their parents and children whilly-nilly - it makes debugging and testing a
very difficult process.

So, I have made some progress on that project, but some of the changes I would like to make
next will break its BC a lot. This project is my test instance where I can try out what I want
to do next, and demonstrate the features that it would offer. It is too experimental to push
into Proj4, but the aim is that it will eventually move across - if the project is happy with
what it offers. In the meantime, I intent this project to remain a useable amd working package,
which should become stable over time.

A bit of history first. Without going into too many details details, PROJ.4 is an application,
written in C, that goes back to the early 1980s. It is still [well-maintained on github](https://github.com/OSGeo/proj.4)
by [The Open Source Geospatial Foundation](http://www.osgeo.org/).

So what is PROJ.4? It is a cartographic projections library. That is a set of tools that handles
conversions between geographic projections. It knows about coordinate systems, ellipsoids,
datums, grid maps, map projections - all these things.

Much of the library has been ported to JavaScript, which became the [Proj4JS](http://proj4js.org/)
project.

Proj4JS was then ported to PHP and now resides at https://github.com/proj4php/proj4php The porting
from the functional language of JavaScript to the OO language of PHP was pretty much a straight
line-for-line move. This has led to some archtectural issues, and that is why we are here - to
fix them :-)

## The Approach

A few basic ideas are going into this project. They include:

* composer compatibility
* immutable objects where possible
* testable (no surprises, globals, public properties being munged by classes)
* Not be afraid of discarding some of the older Proj4JS ideas - restructure (e.g. multiple `Point` types,
  moving point conversions out of `Datum` and into the points.
* But do make use of the fantastic code that has been developed for  Proj.4 ans its derivatives, by people
  an awful lot cleverer than me :-)

## What Do We Have So Far?

There is an `Ellipsoid` class. This holds the properties of a geodesic ellipsoid. When creating this
object, give it a semi-major axis (distance from pole to centre of Earth) and one of the semi-minor
axis (centre of Earth to equator), flattening, or the invers flattening. The `Ellipsoid` will
calculate all the other values when you need them.

The `Datum` class defines the parameters for the reference surface of the Earth. This includes an
`Ellipsoid`, the offset of the centre of the Earth compared to the WGS84 datum, and optional
rotation and scaling factors.

Then ther are points. Each point represents a point on the surface of the Earth above or below its
reference ellipsoid. The two ways to represent points in this project at the moment are
`geocentric` or `geodetic` points. A `geocentric` point is a cartiesuiam (x, y, z) location from the sentre of
the Earth. (x, y) sits on the equitorial plane, and z is parallel to the polar axis.

A `geodetic` coordiate is represented by a latitude and longitude angle, and a height (above its
reference ellipsoid).

## Some examples

Going straight to example code to show what we have at present, these are the conversions we
can do:

~~~php
use Academe\Proj\Datum;
use Academe\Proj\Ellipsoid;
use Academe\Proj\Points\Geocentric;
use Academe\Proj\Points\Geodetic;
~~~

~~~php
// This is the default WGS84 ellipsoid
$ellipsoid = new Ellipsoid();

// This is the "Plessis 1817" ellipsoid.
$ellipsoid_plessis = new Ellipsoid(['a' => 6376523.0, 'rf' => 6355863.0, 'name' => 'Plessis 1817 (France)']);

// We create a geodetic (lat/long) point.
// It gets a WGS84 ellipsoid by default.
// The geodetic height defaults to zero, so this point is rigth on the ellipsoid.
$point = new Geodetic(54.807601889865, -1.5888977);

// A point in France, using a the "Plessis 1817" ellipsoid.
$point_paris = new Geodetic(48.8588589, 2.3475569, 12, $ellipsoid_plessis);

// We can convert the point to a geocentric point:
$point_geocentric = $point_paris->toGeoCentric();

var_dump($point_geocentric->asArray());

// Gives us an array [x, y, z]
//
// array(3) {
//  [0]=>
//  float(4191704.6080426)
//  [1]=>
//  float(4798081.9953674)
//  [2]=>
//  float(261190.02573393)
//  ["ellps"]=>
//    array(3) {
//      ["a"]=>
//      float(6376523)
//      ["rf"]=>
//      float(6355863)
//      ["name"]=>
//      string(21) "Plessis 1817 (France)"
//    }
// }

// We can covert it back again.
$point_geodetic = Geodetic::fromGeocentric($point_geocentric);

// Every point type will have a `toGeocentric()` method and a static `fromGeocentric()`
// method. A Geocentric point is the go-between common point type that conversions will
// jump via.
~~~

Here is another example, datum-shifting a geodetic coordinate from OSGB36 to WGS84 and
back again:

~~~php
// We are pointing at Edinburgh castle:
// OSGB36 (55°56′55.15″N, 003°11′54.57″W) == WGS84 (55°56′54.94″N, 003°11′59.69″W)

// Set up the datum
$ellipsoid_airy = new Ellipsoid(['a' => 6377563.396, 'b' => 6356256.910, 'code' => 'airy', 'name' => 'Airy 1830']);
$datum_osgb36 = new Datum([446.448, -125.157, 542.060, 0.1502, 0.2470, 0.8421, -20.4894], $ellipsoid_airy);

// Create the point.
$point_castle = new Geodetic(55+(56/60)+(55.15/3600), -(3+(11/60)+(54.57/3600)), 0, $datum_osgb36);

// Original OSGB36 point.
echo "point_castle = " . print_r($point_castle->asArray(), true) . "\n";

// Shift to WGS84.
$point_castle_wgs84 = $point_castle->toWgs84();
echo "point_castle_wgs84 = " . print_r($point_castle_wgs84->asArray(), true) . "\n";

// Back to OSGB36
$point_castle_osgb36 = $point_castle_wgs84->toDatum($datum_osgb36);
echo "point_castle_osgb36 = " . print_r($point_castle_osgb36->asArray(), true) . "\n";
~~~

This is the result:

~~~php
point_castle = Array
(
    [lat] => 55.948652777778
    [lon] => -3.1984916666667
    [height] => 0
    [datum] => Array
        (
            [0] => 446.448
            [1] => -125.157
            [2] => 542.06
            [3] => 0.1502
            [4] => 0.247
            [5] => 0.8421
            [6] => -20.4894
            [ellps] => Array
                (
                    [a] => 6377563.396
                    [b] => 6356256.91
                    [code] => airy
                    [name] => Airy 1830
                )
        )
}

point_castle_wgs84 = Array
(
    [lat] => 55.94859199041
    [lon] => -3.1999147965915
    [height] => 169.58012914099
    [datum] => Array
        (
            [0] => 0
            [1] => 0
            [2] => 0
            [ellps] => Array
                (
                    [a] => 6378137
                    [rf] => 298.257223563
                    [code] => WGS84
                    [name] => WGS 84
                )
        )
)

point_castle_osgb36 = Array
(
    [lat] => 55.948652777402
    [lon] => -3.1984916671385
    [height] => 2.9094517230988E-5
    [datum] => Array
        (
            [0] => 446.448
            [1] => -125.157
            [2] => 542.06
            [3] => 0.1502
            [4] => 0.247
            [5] => 0.8421
            [6] => -20.4894
            [ellps] => Array
                (
                    [a] => 6377563.396
                    [b] => 6356256.91
                    [code] => airy
                    [name] => Airy 1830
                )
        )
)
~~~

Converting a transverse mercator coordinate to lat/lon.

~~~php
// Parameter library
$params = new Params();

// The ellipsoid we want to use.
$ellps = new Ellipsoid($params->ellps('wgs84'));

// A very simple transvers marcator projection, with the central meridian at 0.
$projection = new Academe\Proj\Projection\Tmerc([
    'lat0' => deg2rad(0), // the equator; would generally be shifted North
    'x0' => 0,
    'y0' => 0,
    'lon0' => deg2rad(0), // central meridian
    'k0' => 1.0,
    'ellps' => $ellps,
]);

// A point that uses this projection, 98km West and 5988km North of the (ellipsoid) equator.
$projected = new Projected(['x' => -98360, 'y' => 5986957], $projection);

// Lets turn this into a lat/lon coordinate.
// $projected->inverse() gives us the Geodetic initialisation values (in radian).
$inverse = new Geodetic($projected->inverse());

// So where are we?
echo "lat=" . $inverse->lat . " lon=" . $inverse->lon;
// lat=53.9999 lon=-1.49999 (North UK)
~~~

The parameter library can be used to create objects like this:

~~~php
use Academe\Proj\Params;

// Just the Mod Airy ellipsoid
$ellipsoid_mod_airy = new Ellipsoid($params->ellipsoid('mod_airy'));

// The ire65 datum, which includes the Mod Airy ellipsoid and Helmert transform parameters.
$datum_ire65 = new Datum($params->datum('ire65'));
~~~

These conversions are important, because many conversion operations can only be done on
certain coordinate types, or using certain datums. All the more complex conersions will be
built upon translating all these points to standard forms that can then be operated on.

For example, a point can be datum shifted to and from any `Datum` and the WGS84 `Datum`.
This can only be done to a `Geocentric` point - a cartesian 3D point. So if you wanted to
shift a lat/long point to another `Datum` then you convert it to `Geocentric`, then datum
shift it, then convert the shifted point back to `Geodetic`. This package aims to make this
process simple and natural; conversions will be done for you automatically if required, with
the aim of leaving you with the same type of point at the end. Or you can manually do each
step. The choice will be yours, but the package will look after you with appropriate type
hinting.

## Next Steps

* Introduce projections.
* A parser for PROJ.4 parameter strings.
* Tables of common ellipsoids, datums, geographic reference systems, with the facility to inject more.  See Issue #2
* Tests! I'm not good at these, so help will be appreciated.
* Feeding ideas back into `proj4php`.
