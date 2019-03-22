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