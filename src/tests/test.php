<?php
include '../../vendor/autoload.php';

$import = new \Academe\Proj\Proj4Importer();
$import->importProjections();
$import->importEllipsoids();
$import->importUnits();
$import->importDatums();

// This file is for testing purposes ony.
$definitions = json_decode(file_get_contents('./data/definitions.json'));
foreach($definitions as $definition) {
    $config = new Academe\Proj\Proj4Config($definition);
    // Test getting an ellipsoid.
    $config->getEllipsoid();
    // Test getting a datum.
    $config->getDatum();
}

// Check projections data to see for which we have a class.
$total = 0;
$implemented = 0;
foreach(json_decode(file_get_contents('../data/projections.json')) as $id => $details) {
    $class = 'Academe\\Proj\\Projection\\' . ucfirst($id);
    if (class_exists($class)) {
        $implemented++;
    }
    $total++;
}
echo "Currently $implemented out of $total projections have an implementing class.\n";

$def = '+proj=sterea +lat_0=52.15616055555555 +lon_0=5.38763888888889 +k=0.9999079 +x_0=155000 +y_0=463000 +ellps=bessel +units=m +no_defs';
$config = new \Academe\Proj\Proj4Config($def);

$ellipsoid = $config->getEllipsoid();

$datum = $config->getDatum();


$epsg28991 = new \Academe\Proj\Point\Geodetic(252890.0, 593697.0, 0, $datum);
$normal = $epsg28991->toWgs84();
var_dump($epsg28991);
var_dump($normal);
//$ellipsoid = new
// The geodetic height defaults to zero, so this point is rigth on the ellipsoid.
//$point = new \Academe\Proj\Point\Geodetic(54.807601889865, -1.5888977);