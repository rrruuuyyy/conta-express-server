<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->get('/api/pendientes_consumo/get', function(Request $request, Response $response){
    $idcliente = $request->getParam('idcliente');
    $idusuario = $request->getParam('idusuario');
    $token = $request->getParam('token');

    $hoy = new DateTime("",new DateTimeZone('America/Mexico_City'));
    $hoy = date_format($hoy, 'Y/m/d');
    $hoy = strtotime($hoy);
    $sql_clientes = "SELECT * FROM cliente WHERE idusuario = '$idusuario'";
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
    $sql = "SELECT * FROM `consumo-cliente` WHERE idusuario = '$idusuario' ";
        try{
            // Instanciar la base de datos
            $db = new db();

            // Conexión
            $db = $db->connect();
            $ejecutar = $db->query($sql);
            $consumo = $ejecutar->fetchAll(PDO::FETCH_OBJ);
            if($consumo === []){
                $mensaje = array(
                    'status' => false,
                    'mensaje' => 'No tiene consumos creados',
                    'error' => ""
                );
                echo json_encode($mensaje);
                return;
            }
            $db = null;
            for ($i=0; $i < count($consumo) ; $i++) {
            	if( strtotime($consumo[$i]->fecha_pago) <= $hoy ){
                    $fecha_actual= date("Y/m/d");
                    $totalConsumos = obtenerPeriodo($consumo[$i]->fecha_pago,$fecha_actual,$consumo[$i]->periodicidad_pago);
                    $inicio_servicio = restar_periodo($consumo[$i]->fecha_pago,$consumo[$i]->periodicidad_pago);
                    $ciclos = round($totalConsumos,0,PHP_ROUND_HALF_DOWN);
                    $fecha_pendiente = $consumo[$i]->fecha_pago;
                    for ($o=0; $o < $ciclos  ; $o++) {
                        $sql_pendiente = "INSERT INTO `pendientes_cobro` 
                		(idusuario,idcliente,fecha_pendiente,importe,descripcion,periodicidad_pago,inicio_servicio) 
                		VALUES 
                		(:idusuario,:idcliente,:fecha_pendiente,:importe,:descripcion,:periodicidad_pago,:inicio_servicio) ";
                		  try{
                            // Get DB Object
                            $db = new db();
                            // Connect
                            $db = $db->connect();
                            $stmt = $db->prepare($sql_pendiente);
                            $stmt->bindParam(':idusuario', $idusuario);
                            $stmt->bindParam(':idcliente', $idcliente);
                            $stmt->bindParam(':fecha_pendiente', $fecha_pendiente);
                            $stmt->bindParam(':importe', $consumo[$i]->importe_consumo);
                            $stmt->bindParam(':descripcion', $consumo[$i]->descripcion);
                            $stmt->bindParam(':periodicidad_pago', $consumo[$i]->periodicidad_pago);
                            $stmt->bindParam(':inicio_servicio', $inicio_servicio);
                            $stmt->execute();
                            $db = null;
                              
                            } catch(PDOException $e){
                                $mensaje = array(
                                    'status' => false,
                                    'mensaje' => 'Pendientes de cobro no creados',
                                    'error' => $e->getMessage()
                                );
                                echo json_encode($mensaje);
                                return;
                            }
                            $fecha_pendiente = sumar_periodo($fecha_pendiente,$consumo[$i]->periodicidad_pago);
                            $inicio_servicio = restar_periodo($fecha_pendiente,$consumo[$i]->periodicidad_pago);
                            $idconsumo = $consumo[$i]->idconsumo_cliente;
                            $sql = "UPDATE `consumo-cliente` SET
                                fecha_pago = '$fecha_pendiente'
                            WHERE idconsumo_cliente = '$idconsumo'  ";
                            try{
                                // Get DB Object
                                $db = new db();
                                // Connect
                                $db = $db->connect();
                                $stmt = $db->prepare($sql);
                                $stmt->execute();
                            } catch(PDOException $e){
                                $mensaje = array(
                                'status' => false,
                                'mensaje' => 'Consumo origen no actualizado',
                                'error' => $e->getMessage()
                            );
                            echo json_encode($mensaje);
                            return;
                        }
                    }
                    // Terminamos de obtener los consumos pendietes
                    $hoy2 = new DateTime("",new DateTimeZone('America/Mexico_City'));
                    $hoy2 = date_format($hoy2, 'Y/m/d');
                    $fecha_actual = strtotime($hoy2);
                    $fecha_entrada = strtotime($fecha_pendiente);
                    if( $fecha_entrada <= $fecha_actual){
                        $sql_pendiente = "INSERT INTO `pendientes_cobro` 
                		(idusuario,idcliente,fecha_pendiente,importe,descripcion,periodicidad_pago,inicio_servicio) 
                		VALUES 
                		(:idusuario,:idcliente,:fecha_pendiente,:importe,:descripcion,:periodicidad_pago,:inicio_servicio) ";
                		try{
                            // Get DB Object
                            $db = new db();
                            // Connect
                            $db = $db->connect();
                            $stmt = $db->prepare($sql_pendiente);
                            $stmt->bindParam(':idusuario', $idusuario);
                            $stmt->bindParam(':idcliente', $idcliente);
                            $stmt->bindParam(':fecha_pendiente', $fecha_pendiente);
                            $stmt->bindParam(':importe', $consumo[$i]->importe_consumo);
                            $stmt->bindParam(':descripcion', $consumo[$i]->descripcion);
                            $stmt->bindParam(':periodicidad_pago', $consumo[$i]->periodicidad_pago);
                            $stmt->bindParam(':inicio_servicio', $inicio_servicio);
                            $stmt->execute();
                            $db = null;
                              
                        } catch(PDOException $e){
                                $mensaje = array(
                                    'status' => false,
                                    'mensaje' => 'Pendientes de cobro no creados',
                                    'error' => $e->getMessage()
                                );
                                echo json_encode($mensaje);
                                return;
                        }
                        $fecha_pendiente = sumar_periodo($fecha_pendiente,$consumo[$i]->periodicidad_pago);
                        $idconsumo = $consumo[$i]->idconsumo_cliente;
                        $sql = "UPDATE `consumo-cliente` SET
                            fecha_pago = '$fecha_pendiente'
                        WHERE idconsumo_cliente = '$idconsumo'  ";
                        try{
                            // Get DB Object
                            $db = new db();
                            // Connect
                            $db = $db->connect();
                            $stmt = $db->prepare($sql);
                            $stmt->execute();
                        } catch(PDOException $e){
                            $mensaje = array(
                            'status' => false,
                            'mensaje' => 'Consumo origen no actualizado',
                            'error' => $e->getMessage()
                        );
                        echo json_encode($mensaje);
                        return;
                        }
                    }

    			}
            }

        } catch(PDOException $e){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Error al cargar los consumos de los clientes',
                'error' => $e->getMessage()
            );
            return json_encode($mensaje);
        }
        $sql_final = "SELECT * FROM pendientes_cobro WHERE ( idusuario = '$idusuario')";
        try{
            // Instanciar la base de datos
            $db = new db();

            // Conexión
            $db = $db->connect();
            $ejecutar = $db->query($sql_final);
            $pendientes = $ejecutar->fetchAll(PDO::FETCH_OBJ);
            $db = null;
            if( $pendientes === [] ){
                $mensaje = array(
                    'status' => true,
                    'mensaje' => 'Sin pendientes de cobro',
                    'rest' => $pendientes
                );
                return json_encode($mensaje);
                return;
            }
            for ($i=0; $i < count($pendientes) ; $i++) { 
                # code...
                $pendientes[$i]->selec = false;
                for ($j=0; $j < count($clientes) ; $j++) { 
                    # code...
                    if($pendientes[$i]->idcliente === $clientes[$j]->idcliente ){
                        $pendientes[$i]->cliente = $clientes[$j];
                    }
                }
            }

            //Exportar y mostrar en formato JSON
            $mensaje = array(
                'status' => true,
                'mensaje' => 'Pendientes cargados',
                'rest' => $pendientes
            );
            return json_encode($mensaje);
        } catch(PDOException $e){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Pendientes no cargados',
                'error' => $e->getMessage()
            );
            return json_encode($mensaje);
        }
});
$app->post('/api/pendientes_consumo/eliminar', function(Request $request, Response $response){
    $consumos = json_decode($request->getParam('consumos'));
    for ($i=0; $i < count($consumos) ; $i++) {
        $sql = "DELETE FROM pendientes_cobro WHERE idpendientes_cobro = '{$consumos[$i]->idpendientes_cobro}'";
        try{
            // Get DB Object
            $db = new db();
            // Connect
            $db = $db->connect();

            $stmt = $db->prepare($sql);
            $stmt->execute();
            $db = null;
        } catch(PDOException $e){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Error al eliminar pendientes',
                'error' => $e->getMessage()
            );
            echo json_encode($mensaje);
        }
    }
    $mensaje = array(
        'status' => true,
        'mensaje' => 'Pendientes de cobro eliminados satisfactoriamente',
        'rest' => ''
    );
    echo json_encode($mensaje);
});
$app->post('/api/pendientes_consumo/cobrar', function(Request $request, Response $response){
    $pendientes = json_decode($request->getParam('pendientes'));
    //  Verificacion del token
    // $auth = new Auth();
    // $token = $auth->Check($token);
    // if($token['status'] === false){
    //     echo json_encode($token);
    //     return;
    // }
    // Fin Verificacion
    $hoy = new DateTime("",new DateTimeZone('America/Mexico_City'));
    $hoy = date_format($hoy, 'd/m/Y');
    // DETECTAMOS SI TODOS LOS COBROS SON MENSUALES
    // $pago_mensual = false;
    // for ($i=0; $i < count($pendientes->pendientes) ; $i++) { 
    //     if( $pendientes->pendientes[$i]->periodicidad_pago === "mensual" ){
    //         $pago_mensual = true;
    //         break;
    //     }else{
    //         $pago_mensual = false;
    //         break;
    //     }
    // }
    $folios = "";
    $sql_folios = "SELECT * FROM folios WHERE idusuario = '{$pendientes->idusuario}'";
    try{
        // Get DB Object
        $db = new db();
        // Connect
        $db = $db->connect();
        $ejecutar = $db->query($sql_folios);
        $folios = $ejecutar->fetchAll(PDO::FETCH_OBJ);
        $folios = $folios[0];
        $db = null;

    } catch(PDOException $e){
        $mensaje = array(
            'status' => false,
            'mensaje' => 'Folios no cargados',
            'error' => $e->getMessage()
        );
        echo json_encode($mensaje);
        return;
    }
    $nombres = [];
    $idcliente = "";
    $pdf = new pdfCreator();
    $pdf = $pdf->PDF_cobro($pendientes->pendientes[$i],$folios,$hoy);
    return;
    for ($i=0; $i < count( $pendientes->pendientes ) ; $i++) {
        $pdf = new pdfCreator();
        $cobro = $pendientes->pendientes[$i];
        $pdf = $pdf->PDF_cobro($pendientes->pendientes[$i],$folios,$hoy);
        array_push($nombres,$pdf);
        $pdf = null;
        $folios->folio = $folios->folio + 1;
        // $idcliente = $pendientes->pendientes[$i]->cliente->idcliente;
        // $pendientes->pendientes[$i]->cliente = null;
        // $borrar_pendiente = "DELETE FROM pendientes_cobro WHERE idpendientes_cobro = '{$cobro->idpendientes_cobro}' ";
        // try{
        //     // Get DB Object
        //     $db = new db();
        //     // Connect
        //     $db = $db->connect();
    
        //     $stmt = $db->prepare($borrar_pendiente);
        //     $stmt->execute();
        //     $db = null;
        // } catch(PDOException $e){
        //     $mensaje = array(
        //         'status' => false,
        //         'mensaje' => 'Error al eliminar pendientes',
        //         'error' => $e->getMessage()
        //     );
        //     echo json_encode($mensaje);
        // }
    }
    echo json_encode($nombres);
    return;
    $sql_folios = "UPDATE folios SET serie=:serie, folio=:folio WHERE idfolios = '{$folios->idfolios}'";
    try{
        // Get DB Object
        $db = new db();
        // Connect
        $db = $db->connect();
        $stmt = $db->prepare($sql_folios);
        $stmt->bindParam(':serie', $folios->serie);
        $stmt->bindParam(':folio', $folios->folio);
        $stmt->execute();
    } catch(PDOException $e){
        $mensaje = array(
            'status' => false,
            'mensaje' => 'Folios no actualizados',
            'error' => $e->getMessage()
        );
        echo json_encode($mensaje);
        return;
    }
    $hoy = new DateTime("",new DateTimeZone('America/Mexico_City'));
    $hoy = date_format($hoy, 'Y/m/d');
    $nombres = json_encode($nombres);
    $pend = json_encode($pendientes->pendientes );
    //Guardamos los cobros realizados        
    $sql_cobro = "INSERT INTO cobros (idusuario,idcliente,nombres_archivos,importe,forma_pago,cuenta,fecha_pago,fecha_creacion,cobros) 
    VALUES
    (:idusuario,:idcliente,:nombres_archivos,:importe,:forma_pago,:cuenta,:fecha_pago,:fecha_creacion,:cobros) ";
    try{
        // Get DB Object
        $db = new db();
        // Connect
        $db = $db->connect();
        $stmt = $db->prepare($sql_cobro);
        $stmt->bindParam(':idusuario', $pendientes->idusuario);
        $stmt->bindParam(':idcliente', $idcliente);
        $stmt->bindParam(':nombres_archivos', $nombres );
        $stmt->bindParam(':importe', $pendientes->importe );
        $stmt->bindParam(':forma_pago', $pendientes->forma_pago );
        $stmt->bindParam(':cuenta', $pendientes->cuenta );
        $stmt->bindParam(':fecha_pago', $pendientes->fecha_pago );
        $stmt->bindParam(':fecha_creacion', $hoy );
        $stmt->bindParam(':cobros', $pend);
        $stmt->execute();

    } catch(PDOException $e){
        $mensaje = array(
            'status' => false,
            'mensaje' => 'Error al guardar cobro',
            'error' => $e->getMessage()
        );
        echo json_encode($mensaje);
        return;
    }
    $nombres = json_decode($nombres);
    $mensaje = array(
        'status' => true,
        'mensaje' => 'Pendientes cobrados satisfactoriamente',
        'rest' => $nombres
    );
    echo json_encode($mensaje);
});