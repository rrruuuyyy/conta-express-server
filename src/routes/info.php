<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
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
            //Consulta para obtener todos los documentos por cobrar a largo plazo y corto plazo
            $consulta_dctos = "SELECT * FROM venta_cliente WHERE idcliente = '$idcliente' AND porcentaje_pagado < 99";
            try{
                // Instanciar la base de datos
                $db = new db();
                // Conexión
                $db = $db->connect();
                $ejecutar = $db->query($consulta_dctos);
                $dctos_por_cobrar = $ejecutar->fetchAll(PDO::FETCH_OBJ);
                $db = null;
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
                    'mensaje' => 'Clientes no cargados',
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
        $doctos_x_cobrar = [];
        $sql = "SELECT * FROM compra_cliente WHERE idcliente = '$idcliente' AND porcentaje_pagado < 98";
        try{
            // Instanciar la base de datos
            $db = new db();
    
            // Conexión
            $db = $db->connect();
            $ejecutar = $db->query($sql);
            $doctos_x_cobrar = $ejecutar->fetchAll(PDO::FETCH_OBJ);
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
        for ($i=0; $i < count($doctos_x_cobrar); $i++) {
            $doctos_x_cobrar[$i]->pagos = [];
            $doctos_x_cobrar[$i]->devoluciones_creditos = [];
            $doctos_x_cobrar[$i]->credito_dev = json_decode($doctos_x_cobrar[$i]->credito_dev);
            //Guardamos dentro de los documentos por cobrar la cantidad que todavia se debe
            $porcentaje = $doctos_x_cobrar[$i]->porcentaje_pagado / 100 ;
            $deuda = $doctos_x_cobrar[$i]->total_descuento - ( $doctos_x_cobrar[$i]->total_descuento * $porcentaje );
            $doctos_x_cobrar[$i]->deuda = $deuda;
            // Obtenemos el xml de la compra a credito
            $iddocto_xml = $doctos_x_cobrar[$i]->iddocto_xml;
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
                $doctos_x_cobrar[$i]->xml = $xml;
            } catch(PDOException $e){
                $mensaje = array(
                    'status' => false,
                    'mensaje' => 'Error al buscar el xml de la compra a credito',
                    'error' => $e->getMessage()
                );
                echo json_encode($mensaje);
                return;
            }
            // Obtenemos todos los pagos que se realizaron
                $idcompra_cliente = $doctos_x_cobrar[$i]->idcompra_cliente;
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
                    $doctos_x_cobrar[$i]->pagos = $complementos;
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
                $idcompra_cliente = $doctos_x_cobrar[$i]->idcompra_cliente;
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
                    $doctos_x_cobrar[$i]->devoluciones_creditos = $devoluciones_creditos;

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
        $mensaje = array(
            'status' => true,
            'mensaje' => 'Documentos por cobrar cargados correctamente',
            'rest' => $doctos_x_cobrar
        );
        echo json_encode($mensaje);
    });