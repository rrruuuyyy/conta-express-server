<?php
header('Content-Type: application/json; charset=UTF-8');
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


require '../vendor/autoload.php';
require '../src/config/db.php';
require '../src/config/auth.php';
require '../src/config/xml_info.php';
require '../src/config/fechas.php';
require '../src/config/idCreator.php';
require '../src/config/pdfCreator.php';
require '../src/config/conversorNumero.php';
require '../src/config/procesador.php';
require '../src/config/GuzzleWebClient.php';


$app = new \Slim\App;
$app->add(function ($req, $res, $next) {
    $response = $next($req, $res);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});
$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function($req, $res) {
    $handler = $this->notFoundHandler; // handle using the default Slim page not found handler
    return $handler($req, $res);
});
// instead of mapping:
$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

///crear las rutas de clientes
require "../src/routes/rutas.php";


$app->run();


