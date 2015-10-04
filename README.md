# Proj4

Experiments in a Proj4 reboot

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
* Not be afraid of discarding some of the older Proj4JS ideas - restricture (e.g. multiple `Point` types,
  moving point conversions out of `Datum` and into the points.

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
use Proj4\Datum;
use Proj4\Ellipsoid;
use Proj4\Points\Geocentric;
use Proj4\Points\Geodetic;
~~~

~~~php
// This is the default WGS84 ellipsoid
$ellipsoid = new Ellipsoid();

// This is the "Plessis 1817" ellipsoid.
$ellipsoid_plessis = new Ellipsoid(['a' => 6376523.0, 'rf' => 6355863.0, 'name' => 'Plessis 1817 (France)']);

// We create a geodetic (lat/long) point.
// It gets a WGS84 ellipsoid by default.
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

* Implement a datum shift function. For this, a point needs to identify what datum it uses at
  any time, so we know whether a real shift is needed converting to another datum, and the
  resulting datum will give contact to the new point (which will have different values).
* Introduce projections.
* A parser for PROJ.4 parameter strings.
* Tables of common ellipsoids, datums, geographic reference systems, with the facility to inject more.
* Tests! I'm not good at these, so help will be appreciated.
* Feeding ideas back into `Proj4php`.
