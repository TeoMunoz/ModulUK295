<?php
error_reporting(E_ERROR | E_PARSE);
require("../vendor/autoload.php");

use OpenApi\Generator;

$openapi = Generator::scan([
    realpath(__DIR__ . '/../src/routes'),
    realpath(__DIR__ . '/../src/config')
]);

header('Content-Type: text/plain');
echo $openapi->toYaml();