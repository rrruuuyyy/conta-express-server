<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
//Obtener todos los clientes
    $app->post('/api/no_resueltos/egresos/get', function(Request $request, Response $response){
        $idusuario = $request->getParam('idusuario');
        $idcliente = $request->getParam('idcliente');
        $token = $request->getParam('token');
        $pendientes = [];
        //  Verificacion del token
        // $auth = new Auth();
        // $token = $auth->Check($token);
        // if($token['status'] === false){
        //     echo json_encode($token);
        //     return;
        // }
        // Fin Verificacion
        // Consulta para errores de pagos
        $consulta = "SELECT * FROM pagos_compra WHERE idcliente='$idcliente' AND resuelto='false' ORDER BY fecha ASC";
        try{
            // Instanciar la base de datos
            $db = new db();

            // Conexión
            $db = $db->connect();
            $ejecutar = $db->query($consulta);
            $errores = $ejecutar->fetchAll(PDO::FETCH_OBJ);
            $db = null;
            $pendientes = array_merge($pendientes,$errores);

        } catch(PDOException $e){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Errores no cargados pagos',
                'error' => $e->getMessage()
            );
            return json_encode($mensaje);
        }
        // Consulta para errores de credito o devoluciones
        $consulta = "SELECT * FROM credito_devolucion_compra WHERE idcliente='$idcliente' AND resuelto='false' ORDER BY fecha ASC";
        try{
            // Instanciar la base de datos
            $db = new db();

            // Conexión
            $db = $db->connect();
            $ejecutar = $db->query($consulta);
            $errores = $ejecutar->fetchAll(PDO::FETCH_OBJ);
            $db = null;
            $pendientes = array_merge($pendientes,$errores);

        } catch(PDOException $e){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Errores no cargados',
                'error' => $e->getMessage()
            );
            return json_encode($mensaje);
        }
        //Exportar y mostrar en formato JSON
        $mensaje = array(
            'status' => true,
            'mensaje' => 'Errores cargados',
            'rest' => $pendientes
        );
        echo json_encode($mensaje);
    });
    $app->post('/api/resolver/xmls_faltantes', function(Request $request, Response $response){
        $data = $_POST;
        $archivos = $_FILES['file'];
        $idusuario = $data['idusuario'];
        $idcliente = $data['idcliente'];
        $uuids_padre = json_decode($data['uuids_padre']);
        $token = $data['token'];
        // Variables que ocuparemos en los procesos
        $xmls = [];
        $xmls_verif = [];
        // var_dump($archivos);
        //  Verificacion del token
        // $auth = new Auth();
        // $token = $auth->Check($token);
        // if($token['status'] === false){
        //     echo json_encode($token);
        //     return;
        // }
        // Fin Verificacion 
        for ($i=0; $i < count( $archivos ) ; $i++) {
            $xml = $archivos['tmp_name'][$i];
            $info = new infoXML();
            $info = $info->obtener($xml);
            $info->UUID = strtoupper($info->UUID);
            $info->UUID = str_replace(" ","",$info->UUID);
            // Convertimos fecha: 2019/03/05 505548 en 2019/03/05
            $date = date_create($info->Fecha);
            $date =  date_format($date, 'Y-m-d');
            $info->Fecha = $date;
            array_push($xmls, $info);
        }
        // Proceso para verificar que los xmls subidos son los correctos
        for ($i=0; $i < count($xmls); $i++) { 
            $valido = false;
            for ($j=0; $j < count($uuids_padre); $j++) { 
                if( $xmls[$i]->UUID === $uuids_padre[$j] ){
                    $valido = true;
                }
            }
            if($valido){
                array_push($xmls_verif,$xmls[$i]);
            }
        }
        for ($i=0; $i < count($xmls_verif); $i++) { 
            $sql = "INSERT INTO `docto-xml` 
				(idcliente,idusuario,TipoDeComprobante,Serie,Folio,FormaPago,SubTotal,Total,Moneda,TipoCambio,MetodoPago,LugarExpedicion,Conceptos,Impuestos,Complementos, Fecha,UUID,deducible,estado,UUIDS_relacionados,Emisor,Receptor,conta,TotalGravado,TotalExento,Descuento,TotalImpuestosRetenidos,TotalImpuestosTrasladados,Otros) 
				values 
				(:idcliente,:idusuario,:TipoDeComprobante,:Serie,:Folio,:FormaPago,:SubTotal,:Total,:Moneda,:TipoCambio,:MetodoPago,:LugarExpedicion,:Conceptos,:Impuestos,:Complementos,:Fecha,:UUID,:deducible,:estado,:UUIDS_relacionados,:Emisor,:Receptor,:conta,:TotalGravado,:TotalExento,:Descuento,:TotalImpuestosRetenidos,:TotalImpuestosTrasladados,:Otros) " ;
            try{
                $conceptos = json_encode($xmls_verif[$i]->Conceptos);
                $impuestos = json_encode($xmls_verif[$i]->Impuestos);
                $Complementos = json_encode($xmls_verif[$i]->Complementos);
                $Emisor = json_encode($xmls_verif[$i]->Emisor);
                $Receptor = json_encode($xmls_verif[$i]->Receptor);
                $uuids_relacionados = json_encode($xmls_verif[$i]->UUIDS_relacionados);
                $Otros = json_encode($xmls_verif[$i]->Otros);
                // Get DB Object
                $db = new db();
                // Connect
                $db = $db->connect();
                $stmt = $db->prepare($sql);					
                $stmt->bindParam(':idcliente', $idcliente);
                $stmt->bindParam(':idusuario', $idusuario);
                $stmt->bindParam(':TipoDeComprobante', $xmls_verif[$i]->TipoDeComprobante);
                $stmt->bindParam(':Serie', $xmls_verif[$i]->Serie);
                $stmt->bindParam(':Folio', $xmls_verif[$i]->Folio);
                $stmt->bindParam(':FormaPago', $xmls_verif[$i]->FormaPago);
                $stmt->bindParam(':SubTotal', $xmls_verif[$i]->SubTotal);
                $stmt->bindParam(':Total', $xmls_verif[$i]->Total);
                $stmt->bindParam(':Moneda', $xmls_verif[$i]->Moneda);
                $stmt->bindParam(':TipoCambio', $xmls_verif[$i]->TipoCambio);
                $stmt->bindParam(':MetodoPago', $xmls_verif[$i]->MetodoPago);
                $stmt->bindParam(':LugarExpedicion', $xmls_verif[$i]->LugarExpedicion);
                $stmt->bindParam(':Conceptos', $conceptos);
                $stmt->bindParam(':Impuestos', $impuestos);
                $stmt->bindParam(':Complementos', $Complementos);
                $stmt->bindParam(':Fecha', $xmls_verif[$i]->Fecha);
                $stmt->bindParam(':UUID', $xmls_verif[$i]->UUID);
                $stmt->bindParam(':deducible', $xmls_verif[$i]->deducible);
                $stmt->bindParam(':estado', $xmls_verif[$i]->estado);
                $stmt->bindParam(':UUIDS_relacionados', $uuids_relacionados);
                $stmt->bindParam(':Emisor', $Emisor);
                $stmt->bindParam(':Receptor', $Receptor);
                $stmt->bindParam(':conta', $xmls_verif[$i]->conta);
                $stmt->bindParam(':TotalGravado', $xmls_verif[$i]->TotalGravado);
                $stmt->bindParam(':TotalExento', $xmls_verif[$i]->TotalExento);
                $stmt->bindParam(':Descuento', $xmls_verif[$i]->Descuento);
                $stmt->bindParam(':TotalImpuestosRetenidos', $xmls_verif[$i]->TotalImpuestosRetenidos);
                $stmt->bindParam(':TotalImpuestosTrasladados', $xmls_verif[$i]->TotalImpuestosTrasladados);
                $stmt->bindParam(':Otros', $Otros);
                $stmt->execute();
        
            } catch(PDOException $e){
                $mensaje = array(
                    'xml' => $xmls[$i],
                    'error' => $e->getMessage()
                );
                echo json_encode($mensaje);
                return;
            }
        }
        // Procesamos los xmls que acabamos de guardar para asi separalos por compras a credito o parcialidades
        $procesador = new ProcesadorXml;
        $procesador = $procesador->procesar('egreso',xmls_verif,$idcliente);
        $pendientes = [];
        
        // Consulta para errores de pagos
        $consulta = "SELECT * FROM pagos_compra WHERE idcliente='$idcliente' AND resuelto='false' ORDER BY fecha ASC";
        try{
            // Instanciar la base de datos
            $db = new db();

            // Conexión
            $db = $db->connect();
            $ejecutar = $db->query($consulta);
            $errores = $ejecutar->fetchAll(PDO::FETCH_OBJ);
            $db = null;
            $pendientes = array_merge($pendientes,$errores);

        } catch(PDOException $e){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Errores no cargados pagos',
                'error' => $e->getMessage()
            );
            return json_encode($mensaje);
        }
        // Consulta para errores de credito o devoluciones
        $consulta = "SELECT * FROM credito_devolucion_compra WHERE idcliente='$idcliente' AND resuelto='false' ORDER BY fecha ASC";
        try{
            // Instanciar la base de datos
            $db = new db();

            // Conexión
            $db = $db->connect();
            $ejecutar = $db->query($consulta);
            $errores = $ejecutar->fetchAll(PDO::FETCH_OBJ);
            $db = null;
            $pendientes = array_merge($pendientes,$errores);

        } catch(PDOException $e){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Errores no cargados',
                'error' => $e->getMessage()
            );
            return json_encode($mensaje);
        }
        for ($i=0; $i < count($pendientes); $i++) {
            $uuid = $pendientes[$i]->uuid_padre;
            $consulta = "SELECT iddocto_xml FROM `docto-xml` WHERE UUID='$uuid'";
            try{
                // Instanciar la base de datos
                $db = new db();

                // Conexión
                $db = $db->connect();
                $ejecutar = $db->query($consulta);
                $factura = $ejecutar->fetch(PDO::FETCH_OBJ);
                $db = null;
//<----------------------------------------------------------------- CORREGIR ESTO --------------------------------------------------------->
                if($factura != false){
                    $sql = '';
                    $resuelto = 'true';
                    if( $pendientes[$i]->idpagos_compra ){
                        $idpagos_compra = $pendientes[$i]->idpagos_compra;
                        $sql = "UPDATE pagos_compra SET
                        idcompra_cliente = :idcompra_cliente,
                        resuelto = :resuelto
                        WHERE idpagos_compra = $idpagos_compra ";
                    }else{
                        $idcredito_devolucion = $pendientes[$i]->idcredito_devolucion; 
                        $sql = "UPDATE credito_devolucion_compra SET
                        idcompra_cliente = :idcompra_cliente,
                        resuelto   = :resuelto
                        WHERE idcredito_devolucion = $idcredito_devolucion ";
                    }
                    
                    try{
                        // Get DB Object
                        $db = new db();
                        // Connect
                        $db = $db->connect();
                        $stmt = $db->prepare($sql);
                        $stmt->bindParam(':idcompra_cliente', $factura->iddocto_xml );
                        $stmt->bindParam(':resuelto', $resuelto);
                        $stmt->execute();
                        $mensaje = array(
                            'status' => true,
                            'mensaje' => 'Pendientes actualizados correctamente',
                            'rest' => ''
                        );
                        echo json_encode($mensaje);
                        return;

                    } catch(PDOException $e){
                        $mensaje = array(
                            'status' => false,
                            'mensaje' => 'Error al guardar la solucion de los pendientes',
                            'error' => $e->getMessage()
                        );
                        echo json_encode($mensaje);
                        return;
                    }
                }

            } catch(PDOException $e){
                $mensaje = array(
                    'status' => false,
                    'mensaje' => 'Error al obtener facturas de los xml procesados',
                    'error' => $e->getMessage()
                );
                return json_encode($mensaje);
            }
        }



        echo json_encode($xmls_verif);
    });