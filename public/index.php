<?php
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

$app->setBasePath("/Modul295");

(require __DIR__ . '/../src/routes/authRoutes.php')($app);
(require __DIR__ . '/../src/routes/productsRoutes.php')($app);

$app->run();
