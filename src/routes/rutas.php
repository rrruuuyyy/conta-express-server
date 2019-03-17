<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
$app = new \Slim\App;

require 'clientes.php';
require 'usuarios.php';
require 'xml.php';
require 'consumo_cliente.php';
require 'pendientes_consumo.php';
require 'folios.php';