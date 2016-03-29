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
    new Academe\Proj\Proj4Config($definition);
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

