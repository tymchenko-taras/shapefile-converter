<?php
/**
 * Created by PhpStorm.
 * User: Taras
 * Date: 20/11/18
 * Time: 3:27 PM
 */

namespace A;
require_once 'ShapeFile.php';
require_once 'ShapeFileException.php';
require_once 'GpointConverter.php';
require_once 'ShapeConverter.php';

error_reporting(E_ALL);

$converter = new \ShapeConverter();
$converter->convert('elect/ELECTORAL_DISTRICT.shp', 'ENGLISH_NA', ['Electoral Districts of Ontario']);