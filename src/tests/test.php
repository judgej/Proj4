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
    var_dump(new Academe\Proj\Proj4Config($definition));
    die();
}