<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \CfdiUtils\ConsultaCfdiSat\WebService;
use \CfdiUtils\ConsultaCfdiSat\RequestParameters;
    //Obtener todos los clientes
    $app->post('/api/info/dashboard', function(Request $request, Response $response){
        $idcliente = $request->getParam('idcliente');
        $mes = $request->getParam('mes');
        $year = $request->getParam('year');
        $token = $request->getParam('token');
        // Variables para ventas
        $ventas = [];
        $ventas_anuales = [];
        $ventas_contado = [];
        $ventas_credito = [];
        $compras = [];
        $compras_anuales = [];
        $compras_contado = [];
        $compras_credito = [];
        $complemento_pagos_compra = [];
        $complemento_pagos_venta = [];
        $ingresos = [];
        $egresos = [];
        $proveedores = [];
        $clientes = [];
        $dctos_por_pagar = '';
        $dctos_por_cobrar = '';
        //  Verificacion del token
        // $auth = new Auth();
        // $token = $auth->Check($token);
        // if($token['status'] === false){
        //     echo json_encode($token);
        //     return;
        // }
        // Fin Verificacion
        error_reporting(-1);
        //<------------------------------------------------------ INGRESOS ------------------------------------------------------------->
        // Obtenemos todas las ventas por meses del año en curso;
        for ($i=0; $i < 12; $i++) {
            $mes_int = $i + 1;
            $sql = "SELECT * FROM venta_cliente WHERE YEAR(fecha) = '$year' AND MONTH(fecha) = '$mes_int' AND idcliente = '$idcliente' AND tipo = 'contado'";
            try{
                // Instanciar la base de datos
                $db = new db();
                // Conexión                
                $db = $db->connect();
                $ejecutar = $db->query($sql);
                $ventas_int = $ejecutar->fetchAll(PDO::FETCH_OBJ);
                $db = null;
                array_push($ventas_anuales,$ventas_int);

            } catch(PDOException $e){
                $mensaje = array(
                    'status' => false,
                    'mensaje' => 'Error al cargar ventas anuales',
                    'error' => $e->getMessage()
                );
                return json_encode($mensaje);
            }
        }
        for ($i=0; $i < 12; $i++) { 
            $mes_int = $i + 1;
            $sql = "SELECT * FROM pagos_venta WHERE YEAR(fecha) = '$year' AND MONTH(fecha) = '$mes_int' AND idcliente = '$idcliente'";
            try{
                // Instanciar la base de datos
                $db = new db();
                // Conexión                
                $db = $db->connect();
                $ejecutar = $db->query($sql);
                $ventas_int = $ejecutar->fetchAll(PDO::FETCH_OBJ);
                $db = null;
                $ventas_int = array_merge($ventas_anuales[$i],$ventas_int);
                $ventas_anuales[$i] = $ventas_int;

            } catch(PDOException $e){
                $mensaje = array(
                    'status' => false,
                    'mensaje' => 'Error al cargar ventas anuales complemento pagos',
                    'error' => $e->getMessage()
                );
                return json_encode($mensaje);
            }
        }
        // ################################ APARTADO PARA VENTAS ###############################
            $consulta_ventas = "SELECT * FROM venta_cliente WHERE MONTH(fecha) = '$mes' AND YEAR(fecha) = '$year' AND idcliente = '$idcliente'";
            try{
                // Instanciar la base de datos
                $db = new db();
                // Conexión                
                $db = $db->connect();
                $ejecutar = $db->query($consulta_ventas);
                $ventas = $ejecutar->fetchAll(PDO::FETCH_OBJ);
                $db = null;
                // Obtenemos las ventas al contado
                for ($i=0; $i < count( $ventas ); $i++) { 
                    $ventas[$i]->credito_dev = json_decode( $ventas[$i]->credito_dev );
                    if( $ventas[$i]->tipo === 'contado' ){
                        array_push($ventas_contado, $ventas);
                    }else{
                        array_push($ventas_credito, $ventas[$i]);
                    }
                }

            } catch(PDOException $e){
                $mensaje = array(
                    'status' => false,
                    'mensaje' => 'Error al cargar ventas_cliente',
                    'error' => $e->getMessage()
                );
                return json_encode($mensaje);
            }
            //Consulta para obtener todos los documentos por cobrar a anualmente
            $consulta_dctos = "SELECT * FROM venta_cliente WHERE idcliente = '$idcliente' AND porcentaje_pagado < 99";
            try{
                // Instanciar la base de datos
                $db = new db();
                // Conexión
                $db = $db->connect();
                $ejecutar = $db->query($consulta_dctos);
                $dctos_por_cobrar = $ejecutar->fetchAll(PDO::FETCH_OBJ);
                $db = null;
                for ($i=0; $i < count($dctos_por_cobrar); $i++) { 
                    $dctos_por_cobrar[$i]->deuda = $dctos_por_cobrar[$i]->total_descuento - ( $dctos_por_cobrar[$i]->total_descuento * $dctos_por_cobrar[$i]->porcentaje_pagado / 100 );
                }
            } catch(PDOException $e){
                $mensaje = array(
                    'status' => false,
                    'mensaje' => 'Error al cargar documentos por cobrar',
                    'error' => $e->getMessage()
                );
                return json_encode($mensaje);
            }
            //Consulta para obtener todos los complementos de pago
            $consulta_complementos = "SELECT * FROM pagos_venta WHERE MONTH(fecha) = '$mes' AND YEAR(fecha) = '$year' AND idcliente = '$idcliente' ";
            try{
                // Instanciar la base de datos
                $db = new db();

                // Conexión
                $db = $db->connect();
                $ejecutar = $db->query($consulta_complementos);
                $complemento_pagos_venta = $ejecutar->fetchAll(PDO::FETCH_OBJ);
                $db = null;

            } catch(PDOException $e){
                $mensaje = array(
                    'status' => false,
                    'mensaje' => 'Error al cargar pagos_compra',
                    'error' => $e->getMessage()
                );
                return json_encode($mensaje);
            }
            //Consulta para obtener todos los clientes y guardarles sus ventas correspondientes
            $consulta_clientes = "SELECT * FROM cliente_cliente WHERE idcliente = '$idcliente' ";
            try{
                // Instanciar la base de datos
                $db = new db();

                // Conexión
                $db = $db->connect();
                $ejecutar = $db->query($consulta_clientes);
                $clientes = $ejecutar->fetchAll(PDO::FETCH_OBJ);
                $db = null;
                for ($j=0; $j < count($clientes); $j++) {
                    $clientes[$j]->ventas = [];
                }
                for ($i=0; $i < count($ventas); $i++) {
                    for ($j=0; $j < count($clientes); $j++) { 
                        if( $ventas[$i]->idcliente_cliente === $clientes[$j]->idcliente_cliente ){
                            $clientes[$j]->ventas = [];
                            array_push($clientes[$j]->ventas,$ventas[$i]);
                        }
                    }
                    for ($j=0; $j < count($complemento_pagos_venta); $j++) { 
                        if( $complemento_pagos_venta[$i]->cliente_cliente === $clientes[$j]->cliente_cliente ){
                            $clientes[$j]->pagos = [];
                            array_push($clientes[$j]->pagos,$complemento_pagos_venta[$i]);
                        }
                    }
                }

            } catch(PDOException $e){
                $mensaje = array(
                    'status' => false,
                    'mensaje' => 'Error al cargar proveedores',
                    'error' => $e->getMessage()
                );
                return json_encode($mensaje);
            }      
        //<------------------------------------------------------ EGRESOS ------------------------------------------------------------->
        // ################################ APARTADO PARA COMPRAS ###############################
        // Obtenemos las compras anuales
        for ($i=0; $i < 12; $i++) {
            $mes_int = $i + 1;
            $sql = "SELECT * FROM compra_cliente WHERE YEAR(fecha) = '$year' AND MONTH(fecha) = '$mes_int' AND idcliente = '$idcliente' AND tipo = 'contado'";
            try{
                // Instanciar la base de datos
                $db = new db();
                // Conexión                
                $db = $db->connect();
                $ejecutar = $db->query($sql);
                $compras_int = $ejecutar->fetchAll(PDO::FETCH_OBJ);
                $db = null;
                $compras_anuales[$i] = $compras_int;
                $compras_int = '';

            } catch(PDOException $e){
                $mensaje = array(
                    'status' => false,
                    'mensaje' => 'Error al cargar compras anuales',
                    'error' => $e->getMessage()
                );
                return json_encode($mensaje);
            }
        }
        for ($i=0; $i < 12; $i++) { 
            $mes_int = $i + 1;
            $sql = "SELECT * FROM pagos_compra WHERE YEAR(fecha) = '$year' AND MONTH(fecha) = '$mes_int' AND idcliente = '$idcliente'";
            try{
                // Instanciar la base de datos
                $db = new db();
                // Conexión                
                $db = $db->connect();
                $ejecutar = $db->query($sql);
                $compras_int = $ejecutar->fetchAll(PDO::FETCH_OBJ);
                $db = null;
                $compras_int = array_merge($compras_anuales[$i],$compras_int);
                $compras_anuales[$i] = $compras_int;

            } catch(PDOException $e){
                $mensaje = array(
                    'status' => false,
                    'mensaje' => 'Error al cargar ventas anuales complemento compras',
                    'error' => $e->getMessage()
                );
                return json_encode($mensaje);
            }
        }
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
                    'mensaje' => 'Error al cargar compras',
                    'error' => $e->getMessage()
                );
                return json_encode($mensaje);
            }
            // Analizamos las compras para obtener las que se realizaron al contado
            for ($i=0; $i < count($compras); $i++) {
                $compras[$i]->credito_dev = json_decode($compras[$i]->credito_dev);
                if( $compras[$i]->tipo === 'contado' ){
                    array_push( $compras_contado, $compras[$i] );
                }else{
                    array_push( $compras_credito, $compras[$i] );
                }
            }
            //Consulta para obtener todos los documentos por pagar a largo plazo y corto plazo
            $consulta_dctos = "SELECT * FROM compra_cliente WHERE idcliente = '$idcliente' AND porcentaje_pagado < 99";
            try{
                // Instanciar la base de datos
                $db = new db();
                // Conexión
                $db = $db->connect();
                $ejecutar = $db->query($consulta_dctos);
                $dctos_por_pagar = $ejecutar->fetchAll(PDO::FETCH_OBJ);
                $db = null;
                for ($i=0; $i < count($dctos_por_pagar); $i++) { 
                    $dctos_por_pagar[$i]->deuda = $dctos_por_pagar[$i]->total_descuento - ( $dctos_por_pagar[$i]->total_descuento * ($dctos_por_pagar[$i]->porcentaje_pagado / 100) );
                }

            } catch(PDOException $e){
                $mensaje = array(
                    'status' => false,
                    'mensaje' => 'Error al cargar documentos por pagar a largo plazo',
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
                $complemento_pagos_compra = $ejecutar->fetchAll(PDO::FETCH_OBJ);
                $db = null;

            } catch(PDOException $e){
                $mensaje = array(
                    'status' => false,
                    'mensaje' => 'Error al cargar pagos_compra',
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
                for ($j=0; $j < count($proveedores); $j++) {
                    $proveedores[$j]->compras = [];
                }
                for ($i=0; $i < count($compras); $i++) {
                    for ($j=0; $j < count($proveedores); $j++) { 
                        if( $compras[$i]->idproveedor_cliente === $proveedores[$j]->idproveedor_cliente && $compras[$i]->tipo === 'contado' ){
                            array_push($proveedores[$j]->compras,$compras[$i]);
                        }
                    }
                }
                for ($i=0; $i < count($complemento_pagos_compra); $i++) { 
                    for ($j=0; $j < count($proveedores); $j++) {
                        if( $complemento_pagos_compra[$i]->idproveedor_cliente === $proveedores[$j]->idproveedor_cliente ){
                            array_push($proveedores[$j]->compras,$complemento_pagos_compra[$i]);
                        }
                    }
                }

            } catch(PDOException $e){
                $mensaje = array(
                    'status' => false,
                    'mensaje' => 'Error al cargar proveedores',
                    'error' => $e->getMessage()
                );
                return json_encode($mensaje);
            }
        $respuesta = (object)[];
        // Ventas
        $respuesta->clientes = $clientes;
        $respuesta->ventas = $ventas;
        $respuesta->ventas_anuales = $ventas_anuales;
        $respuesta->ventas_contado = $ventas_contado;
        $respuesta->ventas_credito = $ventas_credito;
        $respuesta->dctos_por_cobrar = $dctos_por_cobrar;
        $respuesta->complemento_pagos_venta = $complemento_pagos_venta;
        //Compras
        $respuesta->proveedores = $proveedores;
        $respuesta->compras = $compras;
        $respuesta->compras_anuales = $compras_anuales;
        $respuesta->compras_contado = $compras_contado;
        $respuesta->compras_credito = $compras_credito;
        $respuesta->dctos_por_pagar = $dctos_por_pagar;
        $respuesta->complemento_pagos_compra = $complemento_pagos_compra;
        $respuesta->ventas = $ventas;
        echo json_encode($respuesta);
    });
    $app->post('/api/info/doctos_x_pagar', function(Request $request, Response $response){
        $idcliente = $request->getParam('idcliente');
        $doctos_x_pagar = [];
        $sql = "SELECT * FROM compra_cliente WHERE idcliente = '$idcliente' AND porcentaje_pagado < 99";
        try{
            // Instanciar la base de datos
            $db = new db();
    
            // Conexión
            $db = $db->connect();
            $ejecutar = $db->query($sql);
            $doctos_x_pagar = $ejecutar->fetchAll(PDO::FETCH_OBJ);
            $db = null;
    
        } catch(PDOException $e){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Error al cargar documentos por pagar',
                'error' => $e->getMessage()
            );
            echo json_encode($mensaje);
            return;
        }
        for ($i=0; $i < count($doctos_x_pagar); $i++) {
            $doctos_x_pagar[$i]->deuda = $doctos_x_pagar[$i]->total_descuento * ( $doctos_x_pagar[$i]->total_descuento * ( $doctos_x_pagar[$i]->porcentaje_pagado / 100 ) );
            $doctos_x_pagar[$i]->pagos = [];
            $doctos_x_pagar[$i]->devoluciones_creditos = [];
            $doctos_x_pagar[$i]->credito_dev = json_decode($doctos_x_pagar[$i]->credito_dev);
            //Guardamos dentro de los documentos por cobrar la cantidad que todavia se debe
            $porcentaje = $doctos_x_pagar[$i]->porcentaje_pagado / 100 ;
            $deuda = $doctos_x_pagar[$i]->total_descuento - ( $doctos_x_pagar[$i]->total_descuento * $porcentaje );
            $doctos_x_pagar[$i]->deuda = $deuda;
            // Obtenemos el xml de la compra a credito
            $iddocto_xml = $doctos_x_pagar[$i]->iddocto_xml;
            $sql_xml = "SELECT * FROM `docto-xml` WHERE iddocto_xml = '$iddocto_xml'";
            try{
                // Instanciar la base de datos
                $db = new db();
                // Conexión
                $db = $db->connect();
                $ejecutar = $db->query($sql_xml);
                $xml = $ejecutar->fetch(PDO::FETCH_OBJ);
                $db = null;
                $xml->Conceptos = json_decode($xml->Conceptos);
                $xml->Emisor = json_decode($xml->Emisor);
                $xml->Receptor = json_decode($xml->Receptor);
                $xml->Impuestos = json_decode($xml->Impuestos);
                $xml->Complementos = json_decode($xml->Complementos);
                $doctos_x_pagar[$i]->xml = $xml;
            } catch(PDOException $e){
                $mensaje = array(
                    'status' => false,
                    'mensaje' => 'Error al buscar el xml de la compra a credito',
                    'error' => $e->getMessage()
                );
                echo json_encode($mensaje);
                return;
            }
            // Obtenemos todos los complementos de pagos
                $idcompra_cliente = $doctos_x_pagar[$i]->idcompra_cliente;
                $sql_doctos = "SELECT * FROM pagos_compra WHERE idcompra_cliente = '$idcompra_cliente'";
                try{
                    // Instanciar la base de datos
                    $db = new db();
                    // Conexión
                    $db = $db->connect();
                    $ejecutar = $db->query($sql_doctos);
                    $complementos = $ejecutar->fetchAll(PDO::FETCH_OBJ);
                    $db = null;                
                    // Obtenemos todos los xml de los pagos realizados
                    for ($j=0; $j < count($complementos); $j++) {
                        $iddocto_xml2 = $complementos[$j]->iddocto_xml;
                        $sql_xml2 = "SELECT * FROM `docto-xml` WHERE iddocto_xml = '$iddocto_xml2'";
                        try{
                            // Instanciar la base de datos
                            $db2 = new db();
                            // Conexión
                            $db2 = $db2->connect();
                            $ejecutar2 = $db2->query($sql_xml2);
                            $xml = $ejecutar2->fetch(PDO::FETCH_OBJ);
                            $db2 = null;
                            $xml->Conceptos = json_decode($xml->Conceptos);
                            $xml->Emisor = json_decode($xml->Emisor);
                            $xml->Receptor = json_decode($xml->Receptor);
                            $xml->Impuestos = json_decode($xml->Impuestos);
                            $xml->Complementos = json_decode($xml->Complementos);
                            $complementos[$j]->xml = $xml;
                        } catch(PDOException $e){
                            $mensaje = array(
                                'status' => false,
                                'mensaje' => 'Error al buscar el xml dentro de los pagos',
                                'error' => $e->getMessage()
                            );
                            echo json_encode($mensaje);
                            return;
                        }
                    }
                    $doctos_x_pagar[$i]->pagos = $complementos;
                } catch(PDOException $e){
                    $mensaje = array(
                        'status' => false,
                        'mensaje' => 'Error al buscar complementos de pago de las compras a credito',
                        'error' => $e->getMessage()
                    );
                    echo json_encode($mensaje);
                    return;
                }
            //Obtenemos todas las devoluciones realizadas
                $idcompra_cliente = $doctos_x_pagar[$i]->idcompra_cliente;
                $sql_dev = "SELECT * FROM credito_devolucion_compra WHERE idcompra_cliente = '$idcompra_cliente'";
                try{
                    // Instanciar la base de datos
                    $db = new db();
                    // Conexión
                    $db = $db->connect();
                    $ejecutar = $db->query($sql_dev);
                    $devoluciones_creditos = $ejecutar->fetchAll(PDO::FETCH_OBJ);
                    $db = null;
                    for ($j=0; $j < count($devoluciones_creditos); $j++) { 
                        $iddocto_xml2 = $devoluciones_creditos[$j]->iddocto_xml;
                        $sql_xml2 = "SELECT * FROM `docto-xml` WHERE iddocto_xml = '$iddocto_xml2'";
                        try{
                            // Instanciar la base de datos
                            $db2 = new db();
                            // Conexión
                            $db2 = $db2->connect();
                            $ejecutar2 = $db2->query($sql_xml2);
                            $xml = $ejecutar2->fetch(PDO::FETCH_OBJ);
                            $db2 = null;
                            $xml->Conceptos = json_decode($xml->Conceptos);
                            $xml->Emisor = json_decode($xml->Emisor);
                            $xml->Receptor = json_decode($xml->Receptor);
                            $xml->Impuestos = json_decode($xml->Impuestos);
                            $devoluciones_creditos[$j]->xml = $xml;
                        } catch(PDOException $e){
                            $mensaje = array(
                                'status' => false,
                                'mensaje' => 'Error al buscar el xml dentro de las devoluciones',
                                'error' => $e->getMessage()
                            );
                            echo json_encode($mensaje);
                            return;
                        }
                    }
                    $doctos_x_pagar[$i]->devoluciones_creditos = $devoluciones_creditos;

                } catch(PDOException $e){
                    $mensaje = array(
                        'status' => false,
                        'mensaje' => 'Error al buscar el xml de la compra a credito',
                        'error' => $e->getMessage()
                    );
                    echo json_encode($mensaje);
                    return;
                }
        }
        // Obtenemos los proveedores para guardar los documentos por pagar en cada uno.
        $proveedores = [];
        $sql_proveedores = "SELECT * FROM proveedor_cliente WHERE idcliente = '$idcliente'";
        try{
            // Instanciar la base de datos
            $db = new db();
    
            // Conexión
            $db = $db->connect();
            $ejecutar = $db->query($sql_proveedores);
            $proveedores = $ejecutar->fetchAll(PDO::FETCH_OBJ);
            $db = null;

        } catch(PDOException $e){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Error al cargar proveedores',
                'error' => $e->getMessage()
            );
            return json_encode($mensaje);
        }
        $prov = [];
        for ($i=0; $i < count($proveedores); $i++) {
            $proveedores[$i]->doctos_x_pagar = [];
            $deuda = 0;
            for ($j=0; $j < count($doctos_x_pagar); $j++) { 
                if( $proveedores[$i]->idproveedor_cliente === $doctos_x_pagar[$j]->idproveedor_cliente ){
                    $deuda = $deuda + ( $doctos_x_pagar[$j]->total_descuento - ( $doctos_x_pagar[$j]->total_descuento * $doctos_x_pagar[$j]->porcentaje_pagado / 100 ) );
                    array_push($proveedores[$i]->doctos_x_pagar,$doctos_x_pagar[$j]);
                }
            }
            if( count($proveedores[$i]->doctos_x_pagar) != 0 ){
                $proveedores[$i]->deuda = $deuda;
                array_push($prov,$proveedores[$i]);
            }
        }
        $mensaje = array(
            'status' => true,
            'mensaje' => 'Documentos por cobrar cargados correctamente',
            'proveedores' => $prov,
            'rest' => $doctos_x_pagar
        );
        echo json_encode($mensaje);
    });
    $app->post('/api/info/doctos_x_cobrar', function(Request $request, Response $response){
        $idcliente = $request->getParam('idcliente');
        $token = $request->getParam('token');
        $dctos_x_cobrar = [];
        $sql = "SELECT * FROM venta_cliente WHERE idcliente = '$idcliente' AND porcentaje_pagado < 99";
        try{
            // Instanciar la base de datos
            $db = new db();
    
            // Conexión
            $db = $db->connect();
            $ejecutar = $db->query($sql);
            $dctos_x_cobrar = $ejecutar->fetchAll(PDO::FETCH_OBJ);
            $db = null;
    
        } catch(PDOException $e){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Error al cargar documentos por cobrar',
                'error' => $e->getMessage()
            );
            echo json_encode($mensaje);
            return;
        }
        for ($i=0; $i < count($dctos_x_cobrar); $i++) {
            $dctos_x_cobrar[$i]->deuda = $dctos_x_cobrar[$i]->total_descuento * ( $dctos_x_cobrar[$i]->total_descuento * ( $dctos_x_cobrar[$i]->porcentaje_pagado / 100 ) );
            $dctos_x_cobrar[$i]->pagos = [];
            $dctos_x_cobrar[$i]->devoluciones_creditos = [];
            $dctos_x_cobrar[$i]->credito_dev = json_decode($dctos_x_cobrar[$i]->credito_dev);
            //Guardamos dentro de los documentos por cobrar la cantidad que todavia se debe
            $porcentaje = $dctos_x_cobrar[$i]->porcentaje_pagado / 100 ;
            $deuda = $dctos_x_cobrar[$i]->total_descuento - ( $dctos_x_cobrar[$i]->total_descuento * $porcentaje );
            $dctos_x_cobrar[$i]->deuda = $deuda;
            // Obtenemos el xml de la venta a credito
            $iddocto_xml = $dctos_x_cobrar[$i]->iddocto_xml;
            $sql_xml = "SELECT * FROM `docto-xml` WHERE iddocto_xml = '$iddocto_xml'";
            try{
                // Instanciar la base de datos
                $db = new db();
                // Conexión
                $db = $db->connect();
                $ejecutar = $db->query($sql_xml);
                $xml = $ejecutar->fetch(PDO::FETCH_OBJ);
                $db = null;
                $xml->Conceptos = json_decode($xml->Conceptos);
                $xml->Emisor = json_decode($xml->Emisor);
                $xml->Receptor = json_decode($xml->Receptor);
                $xml->Impuestos = json_decode($xml->Impuestos);
                $xml->Complementos = json_decode($xml->Complementos);
                $dctos_x_cobrar[$i]->xml = $xml;
            } catch(PDOException $e){
                $mensaje = array(
                    'status' => false,
                    'mensaje' => 'Error al buscar el xml de la venta a credito',
                    'error' => $e->getMessage()
                );
                echo json_encode($mensaje);
                return;
            }
            // Obtenemos todos los complementos de pagos
                $idventa_cliente = $dctos_x_cobrar[$i]->idventa_cliente;
                $sql_doctos = "SELECT * FROM pagos_venta WHERE idventa_cliente = '$idventa_cliente'";
                try{
                    // Instanciar la base de datos
                    $db = new db();
                    // Conexión
                    $db = $db->connect();
                    $ejecutar = $db->query($sql_doctos);
                    $complementos = $ejecutar->fetchAll(PDO::FETCH_OBJ);
                    $db = null;                
                    // Obtenemos todos los xml de los pagos realizados
                    for ($j=0; $j < count($complementos); $j++) {
                        $iddocto_xml2 = $complementos[$j]->iddocto_xml;
                        $sql_xml2 = "SELECT * FROM `docto-xml` WHERE iddocto_xml = '$iddocto_xml2'";
                        try{
                            // Instanciar la base de datos
                            $db2 = new db();
                            // Conexión
                            $db2 = $db2->connect();
                            $ejecutar2 = $db2->query($sql_xml2);
                            $xml = $ejecutar2->fetch(PDO::FETCH_OBJ);
                            $db2 = null;
                            $xml->Conceptos = json_decode($xml->Conceptos);
                            $xml->Emisor = json_decode($xml->Emisor);
                            $xml->Receptor = json_decode($xml->Receptor);
                            $xml->Impuestos = json_decode($xml->Impuestos);
                            $xml->Complementos = json_decode($xml->Complementos);
                            $complementos[$j]->xml = $xml;
                        } catch(PDOException $e){
                            $mensaje = array(
                                'status' => false,
                                'mensaje' => 'Error al buscar el xml dentro de los pagos',
                                'error' => $e->getMessage()
                            );
                            echo json_encode($mensaje);
                            return;
                        }
                    }
                    $dctos_x_cobrar[$i]->pagos = $complementos;
                } catch(PDOException $e){
                    $mensaje = array(
                        'status' => false,
                        'mensaje' => 'Error al buscar complementos de pago de las ventas a credito',
                        'error' => $e->getMessage()
                    );
                    echo json_encode($mensaje);
                    return;
                }
            //Obtenemos todas las devoluciones realizadas
                $idventa_cliente = $dctos_x_cobrar[$i]->idventa_cliente;
                $sql_dev = "SELECT * FROM credito_devolucion_venta WHERE idventa_cliente = '$idventa_cliente'";
                try{
                    // Instanciar la base de datos
                    $db = new db();
                    // Conexión
                    $db = $db->connect();
                    $ejecutar = $db->query($sql_dev);
                    $devoluciones_creditos = $ejecutar->fetchAll(PDO::FETCH_OBJ);
                    $db = null;
                    for ($j=0; $j < count($devoluciones_creditos); $j++) { 
                        $iddocto_xml2 = $devoluciones_creditos[$j]->iddocto_xml;
                        $sql_xml2 = "SELECT * FROM `docto-xml` WHERE iddocto_xml = '$iddocto_xml2'";
                        try{
                            // Instanciar la base de datos
                            $db2 = new db();
                            // Conexión
                            $db2 = $db2->connect();
                            $ejecutar2 = $db2->query($sql_xml2);
                            $xml = $ejecutar2->fetch(PDO::FETCH_OBJ);
                            $db2 = null;
                            $xml->Conceptos = json_decode($xml->Conceptos);
                            $xml->Emisor = json_decode($xml->Emisor);
                            $xml->Receptor = json_decode($xml->Receptor);
                            $xml->Impuestos = json_decode($xml->Impuestos);
                            $devoluciones_creditos[$j]->xml = $xml;
                        } catch(PDOException $e){
                            $mensaje = array(
                                'status' => false,
                                'mensaje' => 'Error al buscar el xml dentro de las devoluciones',
                                'error' => $e->getMessage()
                            );
                            echo json_encode($mensaje);
                            return;
                        }
                    }
                    $dctos_x_cobrar[$i]->devoluciones_creditos = $devoluciones_creditos;

                } catch(PDOException $e){
                    $mensaje = array(
                        'status' => false,
                        'mensaje' => 'Error al buscar el xml de la venta a credito',
                        'error' => $e->getMessage()
                    );
                    echo json_encode($mensaje);
                    return;
                }
        }
        // Obtenemos los proveedores para guardar los documentos por pagar en cada uno.
        $clientes = [];
        $sql_clientes = "SELECT * FROM cliente_cliente WHERE idcliente = '$idcliente'";
        try{
            // Instanciar la base de datos
            $db = new db();
    
            // Conexión
            $db = $db->connect();
            $ejecutar = $db->query($sql_clientes);
            $clientes = $ejecutar->fetchAll(PDO::FETCH_OBJ);
            $db = null;

        } catch(PDOException $e){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Error al cargar clientes',
                'error' => $e->getMessage()
            );
            return json_encode($mensaje);
        }
        $clien = [];
        for ($i=0; $i < count($clientes); $i++) {
            $clientes[$i]->dctos_x_cobrar = [];
            $deuda = 0;
            for ($j=0; $j < count($dctos_x_cobrar); $j++) { 
                if( $clientes[$i]->idcliente_cliente === $dctos_x_cobrar[$j]->idcliente_cliente ){
                    $deuda = $deuda + ( $dctos_x_cobrar[$j]->total_descuento - ( $dctos_x_cobrar[$j]->total_descuento * $dctos_x_cobrar[$j]->porcentaje_pagado / 100 ) );
                    array_push($clientes[$i]->dctos_x_cobrar,$dctos_x_cobrar[$j]);
                }
            }
            if( count($clientes[$i]->dctos_x_cobrar) != 0 ){
                $clientes[$i]->deuda = $deuda;
                array_push($clien,$clientes[$i]);
            }
        }
        $mensaje = array(
            'status' => true,
            'mensaje' => 'Documentos por cobrar cargados correctamente',
            'clientes' => $clien,
            'rest' => $dctos_x_cobrar
        );
        echo json_encode($mensaje);
    });
    $app->post('/api/info/facturas_egresos', function(Request $request, Response $response){
        $idcliente = $request->getParam('idcliente');
        $mes = $request->getParam('mes');
        $year = $request->getParam('year');
        $facturas_egresos = [];
        $compras = [];
        $proveedores = [];
        $sql = "SELECT * FROM compra_cliente WHERE idcliente = '$idcliente' AND MONTH(fecha) = '$mes' AND YEAR(fecha) = '$year'";
        try{
            // Instanciar la base de datos
            $db = new db();
    
            // Conexión
            $db = $db->connect();
            $ejecutar = $db->query($sql);
            $compras = $ejecutar->fetchAll(PDO::FETCH_OBJ);
            $db = null;
            for ($i=0; $i < count($compras); $i++) { 
                $iddocto_xml = $compras[$i]->iddocto_xml;
                $sql_xml2 = "SELECT * FROM `docto-xml` WHERE iddocto_xml = '$iddocto_xml'";
                try{
                    // Instanciar la base de datos
                    $db2 = new db();
                    // Conexión
                    $db2 = $db2->connect();
                    $ejecutar2 = $db2->query($sql_xml2);
                    $xml = $ejecutar2->fetch(PDO::FETCH_OBJ);
                    $db2 = null;
                    $xml->Conceptos = json_decode($xml->Conceptos);
                    $xml->Emisor = json_decode($xml->Emisor);
                    $xml->Receptor = json_decode($xml->Receptor);
                    $xml->Impuestos = json_decode($xml->Impuestos);
                    $xml->Complementos = json_decode($xml->Complementos);
                    $compras[$i]->xml = $xml;
                } catch(PDOException $e){
                    $mensaje = array(
                        'status' => false,
                        'mensaje' => 'Error al buscar el xml dentro de las compras',
                        'error' => $e->getMessage()
                    );
                    echo json_encode($mensaje);
                    return;
                }
            }
    
        } catch(PDOException $e){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Error al cargar compras por intervalo de fecha',
                'error' => $e->getMessage()
            );
            echo json_encode($mensaje);
            return;
        }
        $sql_pagos = "SELECT * FROM pagos_compra WHERE idcliente = '$idcliente' AND MONTH(fecha) = '$mes' AND YEAR(fecha) = '$year'";
        try{
            // Instanciar la base de datos
            $db = new db();
    
            // Conexión
            $db = $db->connect();
            $ejecutar = $db->query($sql_pagos);
            $pagos = $ejecutar->fetchAll(PDO::FETCH_OBJ);
            for ($i=0; $i < count($pagos); $i++) { 
                $iddocto_xml = $pagos[$i]->iddocto_xml;
                $sql_xml2 = "SELECT * FROM `docto-xml` WHERE iddocto_xml = '$iddocto_xml'";
                try{
                    // Instanciar la base de datos
                    $db2 = new db();
                    // Conexión
                    $db2 = $db2->connect();
                    $ejecutar2 = $db2->query($sql_xml2);
                    $xml = $ejecutar2->fetch(PDO::FETCH_OBJ);
                    $db2 = null;
                    $xml->Conceptos = json_decode($xml->Conceptos);
                    $xml->Emisor = json_decode($xml->Emisor);
                    $xml->Receptor = json_decode($xml->Receptor);
                    $xml->Impuestos = json_decode($xml->Impuestos);
                    $xml->Complementos = json_decode($xml->Complementos);
                    $pagos[$i]->xml = $xml;
                } catch(PDOException $e){
                    $mensaje = array(
                        'status' => false,
                        'mensaje' => 'Error al buscar el xml dentro de las pagos',
                        'error' => $e->getMessage()
                    );
                    echo json_encode($mensaje);
                    return;
                }
            }
            $db = null;
            $compras = array_merge($compras,$pagos);
    
        } catch(PDOException $e){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Error al cargar compras por intervalo de fecha',
                'error' => $e->getMessage()
            );
            echo json_encode($mensaje);
            return;
        }
        $sql_creidto = "SELECT * FROM credito_devolucion_compra WHERE idcliente= '$idcliente' AND MONTH(fecha) = '$mes' AND YEAR(fecha) = '$year' ";
        try{
            // Instanciar la base de datos
            $db = new db();
    
            // Conexión
            $db = $db->connect();
            $ejecutar = $db->query($sql_creidto);
            $creditos = $ejecutar->fetchAll(PDO::FETCH_OBJ);
            for ($i=0; $i < count($creditos); $i++) { 
                $iddocto_xml = $creditos[$i]->iddocto_xml;
                $sql_xml2 = "SELECT * FROM `docto-xml` WHERE iddocto_xml = '$iddocto_xml'";
                try{
                    // Instanciar la base de datos
                    $db2 = new db();
                    // Conexión
                    $db2 = $db2->connect();
                    $ejecutar2 = $db2->query($sql_xml2);
                    $xml = $ejecutar2->fetch(PDO::FETCH_OBJ);
                    $db2 = null;
                    $xml->Conceptos = json_decode($xml->Conceptos);
                    $xml->Emisor = json_decode($xml->Emisor);
                    $xml->Receptor = json_decode($xml->Receptor);
                    $xml->Impuestos = json_decode($xml->Impuestos);
                    $xml->Complementos = json_decode($xml->Complementos);
                    $creditos[$i]->xml = $xml;
                } catch(PDOException $e){
                    $mensaje = array(
                        'status' => false,
                        'mensaje' => 'Error al buscar el xml dentro de las creditos',
                        'error' => $e->getMessage()
                    );
                    echo json_encode($mensaje);
                    return;
                }
            }
            $db = null;
            $compras = array_merge($compras,$creditos);
    
        } catch(PDOException $e){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Error al cargar creditos y devoluciones por intervalo de fecha',
                'error' => $e->getMessage()
            );
            echo json_encode($mensaje);
            return;
        }
        $sql_proveedores = "SELECT * FROM proveedor_cliente WHERE idcliente = '$idcliente'";
        try{
            // Instanciar la base de datos
            $db = new db();
            // Conexión
            $db = $db->connect();
            $ejecutar = $db->query($sql_proveedores);
            $proveedores_int = $ejecutar->fetchAll(PDO::FETCH_OBJ);
            for ($i=0; $i < count($proveedores_int); $i++) {
                $proveedores_int[$i]->compras = [];
                for ($j=0; $j < count($compras); $j++) {                     
                    if( $proveedores_int[$i]->idproveedor_cliente === $compras[$j]->idproveedor_cliente ){
                        array_push($proveedores_int[$i]->compras,$compras[$j]);
                    }
                }
                if( count($proveedores_int[$i]->compras) != 0 ){
                    array_push($proveedores,$proveedores_int[$i]);
                }
            }
            $db = null;
            for ($i=0; $i < count($proveedores); $i++) {
                $total = 0.0;
                for ($j=0; $j < count($proveedores[$i]->compras); $j++) { 
                   $total = floatval($total) + floatval( $proveedores[$i]->compras[$j]->total );
                }
                $proveedores[$i]->total_movimientos = $total;
            }

        } catch(PDOException $e){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Error al cargar proveedores',
                'error' => $e->getMessage()
            );
            return json_encode($mensaje);
        }
        $respuesta = (object)[];
        $respuesta->proveedores = $proveedores;
        $respuesta->movimientos = $compras;
        $mensaje = array(
            'status' => true,
            'mensaje' => 'Informacion de facturas cargadas correctamente',
            'rest' => $respuesta
        );
        return json_encode($mensaje);
    });
    $app->post('/api/info/facturas_ingresos', function(Request $request, Response $response){
        $idcliente = $request->getParam('idcliente');
        $mes = $request->getParam('mes');
        $year = $request->getParam('year');
        $ventas = [];
        $clientes_cliente = [];
        $sql = "SELECT * FROM venta_cliente WHERE idcliente = '$idcliente' AND MONTH(fecha) = '$mes' AND YEAR(fecha) = '$year'";
        try{
            // Instanciar la base de datos
            $db = new db();
    
            // Conexión
            $db = $db->connect();
            $ejecutar = $db->query($sql);
            $ventas = $ejecutar->fetchAll(PDO::FETCH_OBJ);
            $db = null;
            for ($i=0; $i < count($ventas); $i++) { 
                $iddocto_xml = $ventas[$i]->iddocto_xml;
                $sql_xml2 = "SELECT * FROM `docto-xml` WHERE iddocto_xml = '$iddocto_xml'";
                try{
                    // Instanciar la base de datos
                    $db2 = new db();
                    // Conexión
                    $db2 = $db2->connect();
                    $ejecutar2 = $db2->query($sql_xml2);
                    $xml = $ejecutar2->fetch(PDO::FETCH_OBJ);
                    $db2 = null;
                    if($xml){
                        $xml->Conceptos = json_decode($xml->Conceptos);
                        $xml->Emisor = json_decode($xml->Emisor);
                        $xml->Receptor = json_decode($xml->Receptor);
                        $xml->Impuestos = json_decode($xml->Impuestos);
                        $xml->Complementos = json_decode($xml->Complementos);
                    }
                    $ventas[$i]->xml = $xml;
                } catch(PDOException $e){
                    $mensaje = array(
                        'status' => false,
                        'mensaje' => 'Error al buscar el xml dentro de las ventas',
                        'error' => $e->getMessage()
                    );
                    echo json_encode($mensaje);
                    return;
                }
            }
    
        } catch(PDOException $e){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Error al cargar ventas por intervalo de fecha',
                'error' => $e->getMessage()
            );
            echo json_encode($mensaje);
            return;
        }
        $sql_pagos = "SELECT * FROM pagos_venta WHERE idcliente = '$idcliente' AND MONTH(fecha) = '$mes' AND YEAR(fecha) = '$year'";
        try{
            // Instanciar la base de datos
            $db = new db();
    
            // Conexión
            $db = $db->connect();
            $ejecutar = $db->query($sql_pagos);
            $pagos = $ejecutar->fetchAll(PDO::FETCH_OBJ);
            for ($i=0; $i < count($pagos); $i++) { 
                $iddocto_xml = $pagos[$i]->iddocto_xml;
                $sql_xml2 = "SELECT * FROM `docto-xml` WHERE iddocto_xml = '$iddocto_xml'";
                try{
                    // Instanciar la base de datos
                    $db2 = new db();
                    // Conexión
                    $db2 = $db2->connect();
                    $ejecutar2 = $db2->query($sql_xml2);
                    $xml = $ejecutar2->fetch(PDO::FETCH_OBJ);
                    $db2 = null;
                    $xml->Conceptos = json_decode($xml->Conceptos);
                    $xml->Emisor = json_decode($xml->Emisor);
                    $xml->Receptor = json_decode($xml->Receptor);
                    $xml->Impuestos = json_decode($xml->Impuestos);
                    $xml->Complementos = json_decode($xml->Complementos);
                    $pagos[$i]->xml = $xml;
                } catch(PDOException $e){
                    $mensaje = array(
                        'status' => false,
                        'mensaje' => 'Error al buscar el xml dentro de las pagos',
                        'error' => $e->getMessage()
                    );
                    echo json_encode($mensaje);
                    return;
                }
            }
            $db = null;
            $ventas = array_merge($ventas,$pagos);
    
        } catch(PDOException $e){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Error al cargar ventas por intervalo de fecha',
                'error' => $e->getMessage()
            );
            echo json_encode($mensaje);
            return;
        }
        $sql_creidto = "SELECT * FROM credito_devolucion_venta WHERE idcliente= '$idcliente' AND MONTH(fecha) = '$mes' AND YEAR(fecha) = '$year' ";
        try{
            // Instanciar la base de datos
            $db = new db();
    
            // Conexión
            $db = $db->connect();
            $ejecutar = $db->query($sql_creidto);
            $creditos = $ejecutar->fetchAll(PDO::FETCH_OBJ);
            for ($i=0; $i < count($creditos); $i++) { 
                $iddocto_xml = $creditos[$i]->iddocto_xml;
                $sql_xml2 = "SELECT * FROM `docto-xml` WHERE iddocto_xml = '$iddocto_xml'";
                try{
                    // Instanciar la base de datos
                    $db2 = new db();
                    // Conexión
                    $db2 = $db2->connect();
                    $ejecutar2 = $db2->query($sql_xml2);
                    $xml = $ejecutar2->fetch(PDO::FETCH_OBJ);
                    $db2 = null;
                    $xml->Conceptos = json_decode($xml->Conceptos);
                    $xml->Emisor = json_decode($xml->Emisor);
                    $xml->Receptor = json_decode($xml->Receptor);
                    $xml->Impuestos = json_decode($xml->Impuestos);
                    $xml->Complementos = json_decode($xml->Complementos);
                    $creditos[$i]->xml = $xml;
                } catch(PDOException $e){
                    $mensaje = array(
                        'status' => false,
                        'mensaje' => 'Error al buscar el xml dentro de las creditos',
                        'error' => $e->getMessage()
                    );
                    echo json_encode($mensaje);
                    return;
                }
            }
            $db = null;
            $ventas = array_merge($ventas,$creditos);
    
        } catch(PDOException $e){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Error al cargar creditos y devoluciones por intervalo de fecha',
                'error' => $e->getMessage()
            );
            echo json_encode($mensaje);
            return;
        }
        $sql_clientes_cliente = "SELECT * FROM cliente_cliente WHERE idcliente = '$idcliente'";
        try{
            // Instanciar la base de datos
            $db = new db();
            // Conexión
            $db = $db->connect();
            $ejecutar = $db->query($sql_clientes_cliente);
            $clientes_cliente_int = $ejecutar->fetchAll(PDO::FETCH_OBJ);
            for ($i=0; $i < count($clientes_cliente_int); $i++) {
                $clientes_cliente_int[$i]->ventas = [];
                for ($j=0; $j < count($ventas); $j++) {                     
                    if( $clientes_cliente_int[$i]->idcliente_cliente === $ventas[$j]->idcliente_cliente ){
                        array_push($clientes_cliente_int[$i]->ventas,$ventas[$j]);
                    }
                }
                if( count($clientes_cliente_int[$i]->ventas) != 0 ){
                    array_push($clientes_cliente,$clientes_cliente_int[$i]);
                }
            }
            $db = null;
            for ($i=0; $i < count($clientes_cliente); $i++) {
                $total = 0.0;
                for ($j=0; $j < count($clientes_cliente[$i]->ventas); $j++) { 
                   $total = floatval($total) + floatval( $clientes_cliente[$i]->ventas[$j]->total );
                }
                $clientes_cliente[$i]->total_movimientos = $total;
            }

        } catch(PDOException $e){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Error al cargar clientes_cliente',
                'error' => $e->getMessage()
            );
            return json_encode($mensaje);
        }
        $respuesta = (object)[];
        $respuesta->clientes_cliente = $clientes_cliente;
        $respuesta->movimientos = $ventas;
        $mensaje = array(
            'status' => true,
            'mensaje' => 'Informacion de facturas ingresos cargadas correctamente',
            'rest' => $respuesta
        );
        return json_encode($mensaje);
    });
    $app->post('/api/info/movimiento_egreso', function(Request $request, Response $response){
        $idcliente = $request->getParam('idcliente');
        $movimiento = json_decode($request->getParam('movimiento'));
        $token = $request->getParam('token');
        //Vamos a detectar si el movimiento es de tipo venta, VENTA, DEVOLUCION O PAGO
        // echo json_encode($movimiento->xml->TipoDeComprobante);
        $xml_padre = null;
        if($movimiento->xml->TipoDeComprobante === 'I'){
            $xml_padre = $movimiento->xml;
            $idcompra_cliente = $movimiento->idcompra_cliente;
            // Obtenemos las devoluciones o creditos que se le realizaron a la factura
                $sql = "SELECT * FROM credito_devolucion_compra WHERE idcompra_cliente = '$idcompra_cliente'";
                try{
                    // Instanciar la base de datos
                    $db = new db();
                    // Conexión
                    $db = $db->connect();
                    $ejecutar = $db->query($sql);
                    $creditos = $ejecutar->fetchAll(PDO::FETCH_OBJ);
                    $db = null;
                    for ($i=0; $i < count($creditos); $i++) { 
                        $iddocto_xml = $creditos[$i]->iddocto_xml;
                        $sql2 = "SELECT * FROM `docto-xml` WHERE iddocto_xml = '$iddocto_xml'";
                        try{
                            // Instanciar la base de datos
                            $db2 = new db();                    
                            // Conexión
                            $db2 = $db2->connect();
                            $ejecutar2 = $db2->query($sql2);
                            $xml = $ejecutar2->fetch(PDO::FETCH_OBJ);
                            $db2 = null;
                            $xml->Conceptos = json_decode($xml->Conceptos);
                            $xml->Emisor = json_decode($xml->Emisor);
                            $xml->Receptor = json_decode($xml->Receptor);
                            $xml->Impuestos = json_decode($xml->Impuestos);
                            $xml->Complementos = json_decode($xml->Complementos);
                            $creditos[$i]->xml = $xml;

                        } catch(PDOException $e){
                            $mensaje = array(
                                'status' => false,
                                'mensaje' => 'Error al buscar docto-xml donde comprobante es I',
                                'error' => $e->getMessage()
                            );
                            return json_encode($mensaje);
                        }
                    }
                    $xml_padre->creditos_devoluciones = $creditos;
                } catch(PDOException $e){
                    $mensaje = array(
                        'status' => false,
                        'mensaje' => 'Error al buscar creditos y devoluciones, comprabante Ingreso',
                        'error' => $e->getMessage()
                    );
                    return json_encode($mensaje);
                }
            // Obtenemos los complementos de pagos realizados a la factura
                $sql = "SELECT * FROM pagos_compra WHERE idcompra_cliente = '$idcompra_cliente'";
                try{
                    // Instanciar la base de datos
                    $db = new db();
                    // Conexión
                    $db = $db->connect();
                    $ejecutar = $db->query($sql);
                    $pagos = $ejecutar->fetchAll(PDO::FETCH_OBJ);
                    $db = null;
                    for ($i=0; $i < count($pagos); $i++) { 
                        $iddocto_xml = $pagos[$i]->iddocto_xml;
                        $sql2 = "SELECT * FROM `docto-xml` WHERE iddocto_xml = '$iddocto_xml'";
                        try{
                            // Instanciar la base de datos
                            $db2 = new db();                    
                            // Conexión
                            $db2 = $db2->connect();
                            $ejecutar2 = $db2->query($sql2);
                            $xml = $ejecutar2->fetch(PDO::FETCH_OBJ);
                            $db2 = null;
                            $xml->Conceptos = json_decode($xml->Conceptos);
                            $xml->Emisor = json_decode($xml->Emisor);
                            $xml->Receptor = json_decode($xml->Receptor);
                            $xml->Impuestos = json_decode($xml->Impuestos);
                            $xml->Complementos = json_decode($xml->Complementos);
                            $pagos[$i]->xml = $xml;

                        } catch(PDOException $e){
                            $mensaje = array(
                                'status' => false,
                                'mensaje' => 'Error al buscar docto-xml donde comprobante es I',
                                'error' => $e->getMessage()
                            );
                            return json_encode($mensaje);
                        }
                    }
                    $xml_padre->pagos = $pagos;
                } catch(PDOException $e){
                    $mensaje = array(
                        'status' => false,
                        'mensaje' => 'Error al buscar creditos y devoluciones, comprabante Ingreso',
                        'error' => $e->getMessage()
                    );
                    return json_encode($mensaje);
                }
        }
        if( $movimiento->xml->TipoDeComprobante === 'E' || $movimiento->xml->TipoDeComprobante === 'P' ){
            // Obtenemos la factura padre con la que vamos a trabajar
            $uuid = $movimiento->uuid_padre;
            $sql_padre = "SELECT * FROM `docto-xml` WHERE UUID = '$uuid'";
            try{
                // Instanciar la base de datos
                $db2 = new db();                    
                // Conexión
                $db2 = $db2->connect();
                $ejecutar2 = $db2->query($sql_padre);
                $xml = $ejecutar2->fetch(PDO::FETCH_OBJ);
                $db2 = null;
                $xml->Conceptos = json_decode($xml->Conceptos);
                $xml->Emisor = json_decode($xml->Emisor);
                $xml->Receptor = json_decode($xml->Receptor);
                $xml->Impuestos = json_decode($xml->Impuestos);
                $xml->Complementos = json_decode($xml->Complementos);
                $xml_padre = $xml;

            } catch(PDOException $e){
                $mensaje = array(
                    'status' => false,
                    'mensaje' => 'Error al buscar docto-xml donde comprobante es E',
                    'error' => $e->getMessage()
                );
                return json_encode($mensaje);
            }
            $idcompra_cliente = $movimiento->idcompra_cliente;
            $sql = "SELECT * FROM credito_devolucion_compra WHERE idcompra_cliente = '$idcompra_cliente'";
            try{
                // Instanciar la base de datos
                $db = new db();
                // Conexión
                $db = $db->connect();
                $ejecutar = $db->query($sql);
                $creditos = $ejecutar->fetchAll(PDO::FETCH_OBJ);
                $db = null;
                for ($i=0; $i < count($creditos); $i++) { 
                    $iddocto_xml = $creditos[$i]->iddocto_xml;
                    $sql2 = "SELECT * FROM `docto-xml` WHERE iddocto_xml = '$iddocto_xml'";
                    try{
                        // Instanciar la base de datos
                        $db2 = new db();                    
                        // Conexión
                        $db2 = $db2->connect();
                        $ejecutar2 = $db2->query($sql2);
                        $xml = $ejecutar2->fetch(PDO::FETCH_OBJ);
                        $db2 = null;
                        $xml->Conceptos = json_decode($xml->Conceptos);
                        $xml->Emisor = json_decode($xml->Emisor);
                        $xml->Receptor = json_decode($xml->Receptor);
                        $xml->Impuestos = json_decode($xml->Impuestos);
                        $xml->Complementos = json_decode($xml->Complementos);
                        $creditos[$i]->xml = $xml;

                    } catch(PDOException $e){
                        $mensaje = array(
                            'status' => false,
                            'mensaje' => 'Error al buscar docto-xml donde comprobante es I',
                            'error' => $e->getMessage()
                        );
                        return json_encode($mensaje);
                    }
                }
                $xml_padre->creditos_devoluciones = $creditos;
            
            } catch(PDOException $e){
                $mensaje = array(
                    'status' => false,
                    'mensaje' => 'Error al buscar creditos y devoluciones, comprabante Egreso',
                    'error' => $e->getMessage()
                );
                return json_encode($mensaje);
            }
        // Obtenemos los complementos de pagos realizados a la factura
            $sql = "SELECT * FROM pagos_compra WHERE idcompra_cliente = '$idcompra_cliente'";
            try{
                // Instanciar la base de datos
                $db = new db();
                // Conexión
                $db = $db->connect();
                $ejecutar = $db->query($sql);
                $pagos = $ejecutar->fetchAll(PDO::FETCH_OBJ);
                $db = null;
                for ($i=0; $i < count($pagos); $i++) { 
                    $iddocto_xml = $pagos[$i]->iddocto_xml;
                    $sql2 = "SELECT * FROM `docto-xml` WHERE iddocto_xml = '$iddocto_xml'";
                    try{
                        // Instanciar la base de datos
                        $db2 = new db();                    
                        // Conexión
                        $db2 = $db2->connect();
                        $ejecutar2 = $db2->query($sql2);
                        $xml = $ejecutar2->fetch(PDO::FETCH_OBJ);
                        $db2 = null;
                        $xml->Conceptos = json_decode($xml->Conceptos);
                        $xml->Emisor = json_decode($xml->Emisor);
                        $xml->Receptor = json_decode($xml->Receptor);
                        $xml->Impuestos = json_decode($xml->Impuestos);
                        $xml->Complementos = json_decode($xml->Complementos);
                        $pagos[$i]->xml = $xml;

                    } catch(PDOException $e){
                        $mensaje = array(
                            'status' => false,
                            'mensaje' => 'Error al buscar docto-xml donde comprobante es I',
                            'error' => $e->getMessage()
                        );
                        return json_encode($mensaje);
                    }
                }
                $xml_padre->pagos = $pagos;
            } catch(PDOException $e){
                $mensaje = array(
                    'status' => false,
                    'mensaje' => 'Error al buscar creditos y devoluciones, comprabante Egreso',
                    'error' => $e->getMessage()
                );
                return json_encode($mensaje);
            }
        }
        $mensaje = array(
            'status' => true,
            'mensaje' => 'Factura padre cargada exitosamente',
            'rest' => $xml_padre
        );
        echo json_encode($mensaje);
    });
    $app->post('/api/info/movimiento_ingreso', function(Request $request, Response $response){
        $idcliente = $request->getParam('idcliente');
        $movimiento = json_decode($request->getParam('movimiento'));
        $token = $request->getParam('token');
        //Vamos a detectar si el movimiento es de tipo COMPRA, VENTA, DEVOLUCION O PAGO
        // echo json_encode($movimiento->xml->TipoDeComprobante);
        $xml_padre = null;
        if($movimiento->xml->TipoDeComprobante === 'I'){
            $xml_padre = $movimiento->xml;
            $idventa_cliente = $movimiento->idventa_cliente;
            // Obtenemos las devoluciones o creditos que se le realizaron a la factura
                $sql = "SELECT * FROM credito_devolucion_venta WHERE idventa_cliente = '$idventa_cliente'";
                try{
                    // Instanciar la base de datos
                    $db = new db();
                    // Conexión
                    $db = $db->connect();
                    $ejecutar = $db->query($sql);
                    $creditos = $ejecutar->fetchAll(PDO::FETCH_OBJ);
                    $db = null;
                    for ($i=0; $i < count($creditos); $i++) { 
                        $iddocto_xml = $creditos[$i]->iddocto_xml;
                        $sql2 = "SELECT * FROM `docto-xml` WHERE iddocto_xml = '$iddocto_xml'";
                        try{
                            // Instanciar la base de datos
                            $db2 = new db();                    
                            // Conexión
                            $db2 = $db2->connect();
                            $ejecutar2 = $db2->query($sql2);
                            $xml = $ejecutar2->fetch(PDO::FETCH_OBJ);
                            $db2 = null;
                            $xml->Conceptos = json_decode($xml->Conceptos);
                            $xml->Emisor = json_decode($xml->Emisor);
                            $xml->Receptor = json_decode($xml->Receptor);
                            $xml->Impuestos = json_decode($xml->Impuestos);
                            $xml->Complementos = json_decode($xml->Complementos);
                            $creditos[$i]->xml = $xml;

                        } catch(PDOException $e){
                            $mensaje = array(
                                'status' => false,
                                'mensaje' => 'Error al buscar docto-xml donde comprobante es I',
                                'error' => $e->getMessage()
                            );
                            return json_encode($mensaje);
                        }
                    }
                    $xml_padre->creditos_devoluciones = $creditos;
                } catch(PDOException $e){
                    $mensaje = array(
                        'status' => false,
                        'mensaje' => 'Error al buscar creditos y devoluciones, ventas Ingreso',
                        'error' => $e->getMessage()
                    );
                    return json_encode($mensaje);
                }
            // Obtenemos los complementos de pagos realizados a la factura
                $sql = "SELECT * FROM pagos_venta WHERE idventa_cliente = '$idventa_cliente'";
                try{
                    // Instanciar la base de datos
                    $db = new db();
                    // Conexión
                    $db = $db->connect();
                    $ejecutar = $db->query($sql);
                    $pagos = $ejecutar->fetchAll(PDO::FETCH_OBJ);
                    $db = null;
                    for ($i=0; $i < count($pagos); $i++) { 
                        $iddocto_xml = $pagos[$i]->iddocto_xml;
                        $sql2 = "SELECT * FROM `docto-xml` WHERE iddocto_xml = '$iddocto_xml'";
                        try{
                            // Instanciar la base de datos
                            $db2 = new db();                    
                            // Conexión
                            $db2 = $db2->connect();
                            $ejecutar2 = $db2->query($sql2);
                            $xml = $ejecutar2->fetch(PDO::FETCH_OBJ);
                            $db2 = null;
                            $xml->Conceptos = json_decode($xml->Conceptos);
                            $xml->Emisor = json_decode($xml->Emisor);
                            $xml->Receptor = json_decode($xml->Receptor);
                            $xml->Impuestos = json_decode($xml->Impuestos);
                            $xml->Complementos = json_decode($xml->Complementos);
                            $pagos[$i]->xml = $xml;

                        } catch(PDOException $e){
                            $mensaje = array(
                                'status' => false,
                                'mensaje' => 'Error al buscar docto-xml donde comprobante es I',
                                'error' => $e->getMessage()
                            );
                            return json_encode($mensaje);
                        }
                    }
                    $xml_padre->pagos = $pagos;
                } catch(PDOException $e){
                    $mensaje = array(
                        'status' => false,
                        'mensaje' => 'Error al buscar creditos y devoluciones, venta Ingreso',
                        'error' => $e->getMessage()
                    );
                    return json_encode($mensaje);
                }
        }
        if( $movimiento->xml->TipoDeComprobante === 'E' || $movimiento->xml->TipoDeComprobante === 'P' ){
            // Obtenemos la factura padre con la que vamos a trabajar
            $uuid = $movimiento->uuid_padre;
            $sql_padre = "SELECT * FROM `docto-xml` WHERE UUID = '$uuid'";
            try{
                // Instanciar la base de datos
                $db2 = new db();                    
                // Conexión
                $db2 = $db2->connect();
                $ejecutar2 = $db2->query($sql_padre);
                $xml = $ejecutar2->fetch(PDO::FETCH_OBJ);
                $db2 = null;
                $xml->Conceptos = json_decode($xml->Conceptos);
                $xml->Emisor = json_decode($xml->Emisor);
                $xml->Receptor = json_decode($xml->Receptor);
                $xml->Impuestos = json_decode($xml->Impuestos);
                $xml->Complementos = json_decode($xml->Complementos);
                $xml_padre = $xml;

            } catch(PDOException $e){
                $mensaje = array(
                    'status' => false,
                    'mensaje' => 'Error al buscar docto-xml donde comprobante es E',
                    'error' => $e->getMessage()
                );
                return json_encode($mensaje);
            }
            $idventa_cliente = $movimiento->idventa_cliente;
            $sql = "SELECT * FROM credito_devolucion_venta WHERE idventa_cliente = '$idventa_cliente'";
            try{
                // Instanciar la base de datos
                $db = new db();
                // Conexión
                $db = $db->connect();
                $ejecutar = $db->query($sql);
                $creditos = $ejecutar->fetchAll(PDO::FETCH_OBJ);
                $db = null;
                for ($i=0; $i < count($creditos); $i++) { 
                    $iddocto_xml = $creditos[$i]->iddocto_xml;
                    $sql2 = "SELECT * FROM `docto-xml` WHERE iddocto_xml = '$iddocto_xml'";
                    try{
                        // Instanciar la base de datos
                        $db2 = new db();                    
                        // Conexión
                        $db2 = $db2->connect();
                        $ejecutar2 = $db2->query($sql2);
                        $xml = $ejecutar2->fetch(PDO::FETCH_OBJ);
                        $db2 = null;
                        $xml->Conceptos = json_decode($xml->Conceptos);
                        $xml->Emisor = json_decode($xml->Emisor);
                        $xml->Receptor = json_decode($xml->Receptor);
                        $xml->Impuestos = json_decode($xml->Impuestos);
                        $xml->Complementos = json_decode($xml->Complementos);
                        $creditos[$i]->xml = $xml;

                    } catch(PDOException $e){
                        $mensaje = array(
                            'status' => false,
                            'mensaje' => 'Error al buscar docto-xml donde comprobante es I',
                            'error' => $e->getMessage()
                        );
                        return json_encode($mensaje);
                    }
                }
                $xml_padre->creditos_devoluciones = $creditos;
            
            } catch(PDOException $e){
                $mensaje = array(
                    'status' => false,
                    'mensaje' => 'Error al buscar creditos y devoluciones, ventabante Egreso',
                    'error' => $e->getMessage()
                );
                return json_encode($mensaje);
            }
        // Obtenemos los complementos de pagos realizados a la factura
            $sql = "SELECT * FROM pagos_venta WHERE idventa_cliente = '$idventa_cliente'";
            try{
                // Instanciar la base de datos
                $db = new db();
                // Conexión
                $db = $db->connect();
                $ejecutar = $db->query($sql);
                $pagos = $ejecutar->fetchAll(PDO::FETCH_OBJ);
                $db = null;
                for ($i=0; $i < count($pagos); $i++) { 
                    $iddocto_xml = $pagos[$i]->iddocto_xml;
                    $sql2 = "SELECT * FROM `docto-xml` WHERE iddocto_xml = '$iddocto_xml'";
                    try{
                        // Instanciar la base de datos
                        $db2 = new db();                    
                        // Conexión
                        $db2 = $db2->connect();
                        $ejecutar2 = $db2->query($sql2);
                        $xml = $ejecutar2->fetch(PDO::FETCH_OBJ);
                        $db2 = null;
                        $xml->Conceptos = json_decode($xml->Conceptos);
                        $xml->Emisor = json_decode($xml->Emisor);
                        $xml->Receptor = json_decode($xml->Receptor);
                        $xml->Impuestos = json_decode($xml->Impuestos);
                        $xml->Complementos = json_decode($xml->Complementos);
                        $pagos[$i]->xml = $xml;

                    } catch(PDOException $e){
                        $mensaje = array(
                            'status' => false,
                            'mensaje' => 'Error al buscar docto-xml donde comprobante es I',
                            'error' => $e->getMessage()
                        );
                        return json_encode($mensaje);
                    }
                }
                $xml_padre->pagos = $pagos;
            } catch(PDOException $e){
                $mensaje = array(
                    'status' => false,
                    'mensaje' => 'Error al buscar creditos y devoluciones, venta Ingreso',
                    'error' => $e->getMessage()
                );
                return json_encode($mensaje);
            }
        }
        $mensaje = array(
            'status' => true,
            'mensaje' => 'Factura padre cargada exitosamente',
            'rest' => $xml_padre
        );
        echo json_encode($mensaje);
    });
    $app->post('/api/info/estado_comprobante', function(Request $request, Response $response){
        $rfc_emisor = $request->getParam('rfc_emisor');
        $rfc_receptor = $request->getParam('rfc_receptor');
        $total = floatval($request->getParam('total'));
        $uuid = $request->getParam('uuid');
        $sello = $request->getParam('sello');
        $status = $request->getParam('status');

        if($status === 'cancelado'){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Comprobante ya cancelado',
                'rest' => $status
            );
            echo json_encode($mensaje);
            return;
        }

        // los datos del cfdi que se van a consultar
        $request = new RequestParameters(
            '3.3', // version del cfdi
            $rfc_emisor, // rfc emisor
            $rfc_receptor, // rfc receptor
            $total, // total (puede contener comas de millares)
            $uuid, // UUID
            $sello // sello
        );

        $respuesta = (object)[]; // Cast empty array to object
        $service = new WebService();
        $response = $service->request($request);
        $respuesta->status = strtolower($response->getCfdi()); // Vigente

        if($respuesta->status  === 'no encontrado'){
            $mensaje = array(
                'status' => true,
                'mensaje' => 'Comprobante no encontrado',
                'rest' => $respuesta
            );
            echo json_encode($mensaje);
            return;
        }
        if($respuesta->status != $status){
            $mensaje = array(
                'status' => true,
                'mensaje' => 'Se cancelo un comprobante',
                'rest' => $respuesta
            );
            echo json_encode($mensaje);
            return;
        }

        $mensaje = array(
            'status' => true,
            'mensaje' => 'Comprobante encontrado',
            'rest' => $respuesta
        );
        echo json_encode($mensaje);
    });