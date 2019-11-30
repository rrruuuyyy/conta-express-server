<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
$app = new \Slim\App;
require 'usuarios.php';
require 'clientes.php';
require 'xml.php';
require 'consumo_cliente.php';
require 'pendientes_consumo.php';
require 'folios.php';
require 'cobros.php';
require 'recibos.php';
require 'subusers.php';
require 'tools.php';
require 'xml_search.php';
require 'proveedores_cliente.php';
require 'info.php';
require 'pendientes.php';
require 'certificado.php';
require 'notificaciones.php';
require 'descarga_masiva.php';
$app->get('/api/', function(Request $request, Response $response){
    $mensaje = array(
        'status' => true,
        'mensaje' => 'Conexion creada'
    );
    echo json_encode($mensaje);
});