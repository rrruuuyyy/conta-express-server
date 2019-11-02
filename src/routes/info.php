<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
    //Obtener todos los clientes
    $app->post('/api/info/dashboard', function(Request $request, Response $response){
        $idcliente = $request->getParam('idcliente');
        $mes = $request->getParam('mes');
        $year = $request->getParam('year');
        $token = $request->getParam('token');
        $compras = [];
        $ventas = [];
        $complemento_pagos = [];
        $ingresos = [];
        $egresos = [];
        $proveedores = [];
        //  Verificacion del token
        // $auth = new Auth();
        // $token = $auth->Check($token);
        // if($token['status'] === false){
        //     echo json_encode($token);
        //     return;
        // }
        // Fin Verificacion
        //<------------------------------------------------------ INGRESOS ------------------------------------------------------------->
        //Consulta para obtener todas las ventas
        $consulta_ventas = "SELECT * FROM venta_cliente WHERE MONTH(fecha) = '$mes' AND YEAR(fecha) = '$year' AND idcliente = '$idcliente'";
        try{
            // Instanciar la base de datos
            $db = new db();

            // Conexión
            $db = $db->connect();
            $ejecutar = $db->query($consulta_ventas);
            $ventas = $ejecutar->fetchAll(PDO::FETCH_OBJ);
            $db = null;

        } catch(PDOException $e){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Clientes no cargados',
                'error' => $e->getMessage()
            );
            return json_encode($mensaje);
        }
        //<------------------------------------------------------ EGRESOS ------------------------------------------------------------->
        //Consulta para obtener todas las compras
        $consulta_compras = "SELECT * FROM compra_cliente WHERE MONTH(fecha) = '$mes' AND YEAR(fecha) = '$year' AND idcliente = '$idcliente'";
        try{
            // Instanciar la base de datos
            $db = new db();

            // Conexión
            $db = $db->connect();
            $ejecutar = $db->query($consulta_compras);
            $compras = $ejecutar->fetchAll(PDO::FETCH_OBJ);
            $db = null;

        } catch(PDOException $e){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Clientes no cargados',
                'error' => $e->getMessage()
            );
            return json_encode($mensaje);
        }
        //Consulta para obtener todos los complementos de pago
        $consulta_complementos = "SELECT * FROM pagos_compra WHERE MONTH(fecha) = '$mes' AND YEAR(fecha) = '$year' AND idcliente = '$idcliente' ";
        try{
            // Instanciar la base de datos
            $db = new db();

            // Conexión
            $db = $db->connect();
            $ejecutar = $db->query($consulta_complementos);
            $complemento_pagos = $ejecutar->fetchAll(PDO::FETCH_OBJ);
            $db = null;

        } catch(PDOException $e){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Clientes no cargados',
                'error' => $e->getMessage()
            );
            return json_encode($mensaje);
        }
        //Consulta para obtener todos los proveedores y guardarles sus compras que se le realizaron
        $consulta_proveedores = "SELECT * FROM proveedor_cliente WHERE idcliente = '$idcliente' ";
        try{
            // Instanciar la base de datos
            $db = new db();

            // Conexión
            $db = $db->connect();
            $ejecutar = $db->query($consulta_proveedores);
            $proveedores = $ejecutar->fetchAll(PDO::FETCH_OBJ);
            $db = null;
            for ($i=0; $i < count($compras); $i++) {
                for ($j=0; $j < count($proveedores); $j++) { 
                    if( $compras[$i]->idproveedor_cliente === $proveedores[$j]->idproveedor_cliente ){
                        $proveedores[$j]->compras = [];
                        array_push($proveedores[$j]->compras,$compras[$i]);
                    }
                }
                for ($j=0; $j < count($complemento_pagos); $j++) { 
                    if( $complemento_pagos[$i]->idproveedor_cliente === $proveedores[$j]->idproveedor_cliente ){
                        $proveedores[$j]->pagos = [];
                        array_push($proveedores[$j]->pagos,$complemento_pagos[$i]);
                    }
                }
            }

        } catch(PDOException $e){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Clientes no cargados',
                'error' => $e->getMessage()
            );
            return json_encode($mensaje);
        }
        $respuesta = (object)[];
        $respuesta->complemento_pagos = $complemento_pagos;
        $respuesta->compras = $compras;
        $respuesta->proveedores = $proveedores;
        $respuesta->ventas = $ventas;
        echo json_encode($respuesta);
    });
    