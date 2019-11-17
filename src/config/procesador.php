<?php
    class ProcesadorXml{
        function procesar($tipo,$xmls,$idcliente){
            if($tipo === 'ingreso'){
                $respuesta = '';
                $consulta = "SELECT * FROM cliente_cliente WHERE idcliente = '$idcliente' ";
                try{
                    // Instanciar la base de datos
                    $db = new db();        
                    // Conexión
                    $db = $db->connect();
                    $ejecutar = $db->query($consulta);
                    $clientes_cliente = $ejecutar->fetchAll(PDO::FETCH_OBJ);
                    $db = null;
                    //<---------------------------- COMIENZA EL ORDENAMIENTO DE LOS XMLS ---------------------------->
                    for ($i=0; $i < count($xmls) ; $i++) {
                        //<----------- SI EL XML ES NUEVO Y NO SE HA INGRESADO ANTERIORMENTE Y ES DEDUCIBLE SE GUARDA ------->
                        if($xmls[$i]->estado === "nuevo" and $xmls[$i]->deducible === "si"){
                            //>-------------------------- TIPO DE COMPROBANTE INGRESO ------------------------------->
                            //Vamos a realizar un for para obtener el iddocto_xml de la factura que estamos analizando
                            $uuid = $xmls[$i]->UUID;
                            $sql_busqueda = "SELECT iddocto_xml FROM `docto-xml` WHERE UUID = '$uuid' ";
                            try{
                                // Instanciar la base de datos
                                $db = new db();            
                                // Conexión
                                $db = $db->connect();
                                $ejecutar = $db->query($sql_busqueda);
                                $uuid = $ejecutar->fetch(PDO::FETCH_OBJ);
                                $db = null;
                                $xmls[$i]->iddocto_xml = $uuid->iddocto_xml;
                            } catch(PDOException $e){
                                $mensaje = array(
                                    'status' => false,
                                    'mensaje' => 'Error al obtener el iddocto_xml del xml que se clasifico para analizarlo',
                                    'error' => $e->getMessage()
                                );
                                return json_encode($mensaje);
                            } 
                            $cliente_cliente = '';
                            //Emepzamos a analizar xml por xml
                            //<-------------------------------------- ACCIONES SI EL XML ES DE TIPO INGRESO ----------------------------------->
                            if($xmls[$i]->TipoDeComprobante === 'I'){
                                $tipo_venta = '';
                                if( $xmls[$i]->MetodoPago === 'PUE' ){
                                    $tipo_venta = 'contado';
                                }else{
                                    $tipo_venta = 'credito';
                                }
                                for ($j=0; $j < count($clientes_cliente); $j++) {
                                    if( $xmls[$i]->Receptor->Rfc === $clientes_cliente[$j]->rfc ){
                                        $cliente_cliente = $clientes_cliente[$j];
                                    }
                                }
                                $uuid = $xmls[$i]->UUID;
                                $sql = "INSERT INTO venta_cliente 
                                    (idcliente_cliente,idcliente,iddocto_xml,fecha,forma_pago,total,total_descuento,tipo,uuid) 
                                    VALUES 
                                    (:idcliente_cliente,:idcliente,:iddocto_xml,:fecha,:forma_pago,:total,:total_descuento,:tipo,:uuid)";
                                try{
                                    // Get DB Object
                                    $db = new db();
                                    // Connect
                                    $db = $db->connect();
                                    $stmt = $db->prepare($sql);
                                    $stmt->bindParam(':idcliente_cliente', $cliente_cliente->idcliente_cliente);
                                    $stmt->bindParam(':idcliente', $idcliente);
                                    $stmt->bindParam(':iddocto_xml', $xmls[$i]->iddocto_xml);
                                    $stmt->bindParam(':fecha', $xmls[$i]->Fecha);
                                    $stmt->bindParam(':forma_pago', $xmls[$i]->FormaPago);
                                    $stmt->bindParam(':total', $xmls[$i]->Total);
                                    $stmt->bindParam(':total_descuento', $xmls[$i]->Total);
                                    $stmt->bindParam(':tipo', $tipo_venta);
                                    $stmt->bindParam(':uuid', strtoupper($xmls[$i]->UUID));
                                    $stmt->execute();
    
                                } catch(PDOException $e){
                                    $mensaje = array(
                                        'status' => false,
                                        'mensaje' => 'Error al guardar venta del cliente',
                                        'error' => $e->getMessage()
                                    );
                                    echo json_encode($mensaje);
                                    return;
                                }
                            }
                            //<-------------------------------------- ACCIONES SI EL XML ES DE TIPO EGRESO ----------------------------------->
                            if( $xmls[$i]->TipoDeComprobante === 'E' ){
                                $relaciones = $xmls[$i]->UUIDS_relacionados;
                                $cliente_cliente = '';
                                $compras_encontradas = [];
                                $compras_faltantes = [];
                                for ($j=0; $j < count($clientes_cliente); $j++) {
                                    if( $xmls[$i]->Receptor->Rfc === $clientes_cliente[$j]->rfc ){
                                        $cliente_cliente = $clientes_cliente[$j];
                                    }
                                }
                                //<---------------- OBTENER UUIDS DE LAS FACTURAS A LAS QUE SE LE APLICO UN DESCUENTO O DEVOLUCION --------->
                                if( count($relaciones) === 1 ){
                                    $uuid = $relaciones[0];
                                    $sql_docto = "SELECT * FROM venta_cliente WHERE uuid='$uuid' ";
                                     try{
                                        // Instanciar la base de datos
                                        $db = new db();
                                        // Conexión
                                        $db = $db->connect();
                                        $ejecutar = $db->query($sql_docto);
                                        $venta = $ejecutar->fetch(PDO::FETCH_OBJ);
                                        $devoluciones = [];                                        
                                        $idventa_cliente = '';                                
                                        $db = null;
                                        $sql_credito = "INSERT INTO `credito_devolucion_venta` (idventa_cliente,iddocto_xml,idcliente_cliente,idcliente,total,forma_pago,fecha,resuelto,uuid_padre) VALUES (:idventa_cliente,:iddocto_xml,:idcliente_cliente,:idcliente,:total,:forma_pago,:fecha,:resuelto,:uuid_padre)";
                                        if( $venta != false ){
                                            //Si existe y es solo una la que viene relacionada, guardamos todos sus datos en la venta padre
                                            $venta->credito_dev = json_decode( $venta->credito_dev );
                                            if( $venta->credito_dev === null  ){
                                                $devoluciones = [];
                                            }else{
                                                $devoluciones = $venta->credito_dev;
                                            }
                                            $resuelto = 'true';
                                            $idventa_cliente = $venta->idventa_cliente;
                                            $credito_dev = (object)[];
                                            $credito_dev->Impuestos = $xmls[$i]->Impuestos;
                                            $credito_dev->MetodoPago = $xmls[$i]->MetodoPago;
                                            $credito_dev->FormaPago = $xmls[$i]->FormaPago;
                                            $credito_dev->Total = $xmls[$i]->Total;
                                            $credito_dev->TotalExento = $xmls[$i]->TotalExento;
                                            $credito_dev->TotalGravado = $xmls[$i]->TotalGravado;
                                            $credito_dev->TotalIEPS = $xmls[$i]->TotalIEPS;
                                            $credito_dev->TotalImpuestosRetenidos = $xmls[$i]->TotalImpuestosRetenidos;
                                            $credito_dev->TotalImpuestosTrasladados = $xmls[$i]->TotalImpuestosTrasladados;
                                            $credito_dev->UUID_tipo_relacion = $xmls[$i]->UUID_tipo_relacion;
                                            $credito_dev->uuid = $xmls[$i]->UUID;
                                            $credito_dev->especificado = true;
                                            array_push( $devoluciones, $credito_dev );
                                            // Obtenemos el total_descuento de la factura padre
                                            $total_descuento = $compra->total;
                                            for ($o=0; $o < count($devoluciones); $o++) { 
                                                $total_descuento = $total_descuento - $devoluciones[$o]->Total;
                                            }
                                            $devoluciones = json_encode($devoluciones);
                                            //Modificamos la factura venta_cliente para guardar si el credito o devolucion viene especificada la devolucion
                                            $sql_venta = "UPDATE venta_cliente SET
                                                credito_dev = :credito_dev,
                                                total_descuento = :total_descuento
                                            WHERE idventa_cliente = '$idventa_cliente'";
                                            try{
                                                $db = new db();
                                                $db = $db->connect();
                                                $stmt = $db->prepare($sql_venta);
                                                $stmt->bindParam(':credito_dev', $devoluciones);
                                                $stmt->bindParam(':total_descuento', $total_descuento);
                                                $stmt->execute();                                        
                                            } catch(PDOException $e){
                                                $mensaje = array(
                                                    'status' => false,
                                                    'mensaje' => 'Error al convertir la devolucion en string y guardarla dentro de la venta padre',
                                                    'error' => $e->getMessage()
                                                );
                                                return $mensaje;
                                            }
                                        }else{
                                            //Si no exite una venta guardamos el credito o devolucion como no resuelto
                                            $idventa_cliente = '';
                                            $resuelto = 'false';                        
                                        }
                                        try{
                                            // Get DB Object
                                            $db = new db();
                                            // Connect
                                            $db = $db->connect();
                                            $stmt = $db->prepare($sql_credito);
                                            $stmt->bindParam(':idventa_cliente', $idventa_cliente);
                                            $stmt->bindParam(':iddocto_xml', $xmls[$i]->iddocto_xml);
                                            $stmt->bindParam(':idcliente_cliente', $cliente_cliente->idcliente_cliente);
                                            $stmt->bindParam(':idcliente', $cliente_cliente->idcliente);
                                            $stmt->bindParam(':total', $xmls[$i]->Total);
                                            $stmt->bindParam(':forma_pago', $xmls[$i]->FormaPago);
                                            $stmt->bindParam(':fecha', $xmls[$i]->Fecha);
                                            $stmt->bindParam(':resuelto', $resuelto);
                                            $stmt->bindParam(':uuid_padre',$relaciones[0]);                                                
                                            $stmt->execute();

                                        } catch(PDOException $e){
                                            $mensaje = array(
                                                'status' => false,
                                                'mensaje' => 'Error al guardar credito o devolucion donde el documento vinculado solo es uno',
                                                'error' => $e->getMessage()
                                            );
                                            return $mensaje;
                                        }

                                     }catch(PDOException $e){
                                        $mensaje = array(
                                            'status' => false,
                                            'mensaje' => 'Error al buscar facturas dentro del credito o devolucion en donde solo hay un documento relacionado',
                                            'error' => $e->getMessage()
                                        );
                                        return json_encode($mensaje);
                                    }
                                }else{
                                    $total_descuento = floatval($xmls[$i]->Total);
                                    $descuento = 0;
                                    $ventas_encontradas = [];
                                    $ventas_faltantes = 0;
                                    for ($j=0; $j < count($relaciones); $j++) {
                                        $uuid = $relaciones[$j];
                                        $sql_docto = "SELECT * FROM venta_cliente WHERE uuid = '$uuid' ";
                                        try{
                                            // Instanciar la base de datos
                                            $db = new db();
                                            // Conexión
                                            $db = $db->connect();
                                            $ejecutar = $db->query($sql_docto);
                                            $venta = $ejecutar->fetch(PDO::FETCH_OBJ);
                                            $db = null;
            
                                            if($venta){
                                                array_push($ventas_encontradas,$venta);
                                                
                                            }else{
                                                $ventas_faltantes = $ventas_faltantes + 1;
                                            }                                            
                                        } catch(PDOException $e){
                                            $mensaje = array(
                                                'status' => false,
                                                'mensaje' => 'Error al buscar facturas dentro del credito o devolucion',
                                                'error' => $e->getMessage()
                                            );
                                            return json_encode($mensaje);
                                        }
                                    }
                                    //Aqui empezamos a desglosar los encontrados y no encontrados
                                    // Inicio del guardado en la base de datos los creditos o devoluciones encontradas
                                    $resuelto = 'true';
                                    for ($j=0; $j < count($ventas_encontradas); $j++) { 
                                        # code...
                                        $sql_credito = "INSERT INTO `credito_devolucion_venta` (idventa_cliente,iddocto_xml,idcliente_cliente,idcliente,total,forma_pago,fecha,resuelto,uuid_padre) VALUES (:idventa_cliente,:iddocto_xml,:idcliente_cliente,:idcliente,:total,:forma_pago,:fecha,:resuelto,:uuid_padre)";
                                        try{
                                            // Get DB Object
                                            $db = new db();
                                            // Connect
                                            $db = $db->connect();
                                            $stmt = $db->prepare($sql_credito);
                                            $stmt->bindParam(':idventa_cliente', $ventas_encontradas[$j]->idventa_cliente);
                                            $stmt->bindParam(':iddocto_xml', $xmls[$i]->iddocto_xml);
                                            $stmt->bindParam(':idcliente_cliente', $cliente_cliente->idcliente_cliente);
                                            $stmt->bindParam(':idcliente', $cliente_cliente->idcliente);
                                            if($total_descuento <= 0.0){
                                                $descuento = 0.0;
                                            }else{
                                                if( $total_descuento >=  $ventas_encontradas[$j]->total ){
                                                    $descuento = $ventas_encontradas[$j]->total;
                                                    $total_descuento = $total_descuento - $descuento;
                                                }else{
                                                    $descuento = $ventas_encontradas[$j]->total;
                                                    $total_descuento = $total_descuento - $descuento;
                                                }
                                            }
                                            $stmt->bindParam(':total', $descuento);
                                            $stmt->bindParam(':forma_pago', $xmls[$i]->FormaPago);
                                            $stmt->bindParam(':fecha', $xmls[$i]->Fecha);
                                            $resuelto = 'true';
                                            $stmt->bindParam(':resuelto', $resuelto);
                                            $stmt->bindParam(':uuid_padre',$relaciones[$j]);                                            
                                            $stmt->execute();
                                            //Guardamos dentro de la factura padre el credito incompleto
                                            if( is_string( $ventas_encontradas[$j]->credito_dev ) ){
                                                $devoluciones = [];
                                            }else{
                                                $devoluciones = json_decode( $ventas_encontradas[$j]->credito_dev );
                                            }
                                            $idventa_cliente = $ventas_encontradas[$j]->idventa_cliente;
                                            $credito_dev = (object)[];
                                            $credito_dev->MetodoPago = $xmls[$i]->MetodoPago;
                                            $credito_dev->FormaPago = $xmls[$i]->FormaPago;
                                            $credito_dev->UUID_tipo_relacion = $xmls[$i]->UUID_tipo_relacion;
                                            $credito_dev->Total = $descuento;
                                            $credito_dev->uuid = $xmls[$i]->UUID;
                                            $credito_dev->especificado = false;
                                            array_push( $devoluciones, $credito_dev );
                                            $devoluciones = json_encode($devoluciones);
                                            //Modificamos la factura venta_cliente para guardar si el credito o devolucion viene especificada la devolucion
                                            $sql_venta = "UPDATE venta_cliente SET
                                                credito_dev = :credito_dev
                                            WHERE idventa_cliente = '$idventa_cliente'";
                                            try{
                                                $db = new db();
                                                $db = $db->connect();
                                                $stmt = $db->prepare($sql_venta);
                                                $stmt->bindParam(':credito_dev', $devoluciones);
                                                $stmt->execute();                                        
                                            } catch(PDOException $e){
                                                $mensaje = array(
                                                    'status' => false,
                                                    'mensaje' => 'Error al convertir la devolucion en string y guardarla dentro de la venta padre cuando hay muchas relaciones',
                                                    'error' => $e->getMessage()
                                                );
                                                return $mensaje;
                                            }

                                        } catch(PDOException $e){
                                            $mensaje = array(
                                                'status' => false,
                                                'mensaje' => 'Error al crear el credito o devolucion en multiples documentos relacionados',
                                                'error' => $e->getMessage()
                                            );
                                            return $mensaje;
                                        }
                                    }
                                    // Inicio del guardado en la base de datos los creditos o devoluciones no encontradas
                                    $resuleto = 'false';
                                    $descuento = $total_descuento / count($ventas_faltantes);
                                    for ($j=0; $j < count($ventas_faltantes); $j++) { 
                                        # code...
                                        $sql = "INSERT INTO credito_devolucion_venta (iddocto_xml,idcliente_cliente,idcliente,total,forma_pago,fecha,resuelto,uuid_padre) 
                                        VALUES 
                                        (:iddocto_xml,:idcliente_cliente,:idcliente,:total,:forma_pago,:fecha,:resuelto,:uuid_padre)";
                                        try{											
                                            // Get DB Object
                                            $db = new db();
                                            // Connect
                                            $db = $db->connect();
                                            $stmt = $db->prepare($sql);
                                            
                                            $stmt->bindParam(':iddocto_xml', $xmls[$i]->iddocto_xml);
                                            $stmt->bindParam(':idcliente_cliente', $cliente_cliente->idcliente_cliente);
                                            $stmt->bindParam(':idcliente', $cliente_cliente->idcliente);
                                            $stmt->bindParam(':total', $descuento);
                                            $stmt->bindParam(':forma_pago', $xmls[$i]->FormaPago);
                                            $stmt->bindParam(':fecha', $xmls[$i]->Fecha);
                                            $resuelto = 'true';
                                            $stmt->bindParam(':resuelto', $resuelto);
                                            $stmt->bindParam(':uuid_padre',$relaciones[$j]);
                                            
                                            $stmt->execute();
    
                                        } catch(PDOException $e){
                                            $mensaje = array(
                                                'status' => false,
                                                'mensaje' => 'Error al guardar credito devolucion de multiples relaciones y xml padre no encontrado INGRESOS ',
                                                'error' => $e->getMessage()
                                            );
                                            echo json_encode($mensaje);
                                            return;
                                        }
                                    } 
                                }
                                //<--------------------------------------FIN FACTURA EGRESO -------------------------------------->
                            }    
                            //<-------------------------------------- ACCIONES SI EL XML ES DE TIPO PAGO ----------------------------------->
                            if( $xmls[$i]->TipoDeComprobante === 'P' ){
                                $pagos = [];
                                $cliente_cliente = '';
                                $pagos_encontradas = [];
                                $pagos_faltantes = [];
                                for ($j=0; $j < count($clientes_cliente); $j++) {
                                    if( $xmls[$i]->Receptor->Rfc === $clientes_cliente[$j]->rfc ){
                                        $cliente_cliente = $clientes_cliente[$j];
                                    }
                                }
                                // FOR PARA ENCONTRAR OBTENER LOS PAGOS REALIZADOS DENTRO DEL XML DE PAGO
                                for ($o=0; $o < count( $xmls[$i]->Complementos->Pagos ); $o++) { 
                                    for ($l=0; $l < count( $xmls[$i]->Complementos->Pagos[$o]->documentos ); $l++) { 
                                        # code...
                                        $pago = $xmls[$i]->Complementos->Pagos[$o]->documentos[$l];
                                        $pago->forma_pago = $xmls[$i]->Complementos->Pagos[$o]->FormaDePagoP;
                                        $pago->fecha = $xmls[$i]->Complementos->Pagos[$o]->FechaPago;
                                        array_push($pagos,$pago);
                                    }
                                }
                                // For para obtener las ventas y guardarlas dentro de los pagos
                                for ($o=0; $o < count($pagos); $o++) {
                                    $uuid = strtoupper( $pagos[$o]->IdDocumento );
                                    $uuid = str_replace(" ","",$uuid);
                                    $consulta = "SELECT * FROM venta_cliente WHERE uuid = '$uuid'";
                                    try{
                                        // Instanciar la base de datos
                                        $db = new db();
        
                                        // Conexión
                                        $db = $db->connect();
                                        $ejecutar = $db->query($consulta);
                                        $venta = $ejecutar->fetch(PDO::FETCH_OBJ);
                                        $db = null;
        
                                        if($venta != false){
                                            $pagos[$o]->venta = $venta;
                                        }else{
                                            $pagos[$o]->venta = null;
                                        }
        
                                    } catch(PDOException $e){
                                        $mensaje = array(
                                            'status' => false,
                                            'mensaje' => 'venta no encontrada xml de pago',
                                            'error' => $e->getMessage()
                                        );
                                        return json_encode($mensaje);
                                    }
                                }
                                //for para guardar en la base de datos los pagos con o sin el uuid padre
                                for ($o=0; $o < count($pagos) ; $o++){
                                    $sql = "INSERT INTO pagos_venta 
                                    (idventa_cliente,iddocto_xml,idcliente_cliente,idcliente,total,forma_pago,fecha,resuelto,uuid_padre,porcentaje) 
                                    VALUES 
                                    (:idventa_cliente,:iddocto_xml,:idcliente_cliente,:idcliente,:total,:forma_pago,:fecha,:resuelto,:uuid_padre,:porcentaje)";
                                    try{
                                        // Get DB Object
                                        $db = new db();
                                        // Connect
                                        $db = $db->connect();
                                        $stmt = $db->prepare($sql);
                                        $stmt->bindParam(':iddocto_xml', $xmls[$i]->iddocto_xml);
                                        $stmt->bindParam(':idcliente_cliente', $cliente_cliente->idcliente_cliente);
                                        $stmt->bindParam(':idcliente', $cliente_cliente->idcliente);
                                        $stmt->bindParam(':total', $pagos[$o]->ImpPagado );
                                        $stmt->bindParam(':forma_pago', $pagos[$o]->forma_pago );
                                        $stmt->bindParam(':fecha', $pagos[$o]->fecha );
                                        $stmt->bindParam(':uuid_padre', $pagos[$o]->IdDocumento );
                                        $resuelto = '';
                                        $porcentaje = 0.0;
                                        $idventa_cliente = '';
                                        if($pagos[$o]->venta != null){
                                            $resuelto = 'true';
                                            $idventa_cliente = $pagos[$o]->venta->idventa_cliente;
                                            $porcentaje = floatval( $pagos[$o]->venta->porcentaje_pagado );
                                            $porcentaje = $porcentaje + ( ( floatval($pagos[$o]->ImpPagado) / floatval($pagos[$o]->venta->total_descuento) ) * 100 ); 
                                            //Modificamos la factura padre para guardar el porcentaje pagado
                                            $sql_venta_edit = "UPDATE venta_cliente SET
                                                porcentaje_pagado = :porcentaje_pagado
                                            WHERE idventa_cliente = '$idventa_cliente'";
                                            try{
                                                $db2 = new db();
                                                $db2 = $db2->connect();
                                                $stmt2 = $db2->prepare($sql_venta_edit);
                                                $stmt2->bindParam(':porcentaje_pagado', $porcentaje);
                                                $stmt2->execute();  
                                                $db2 = null;
                                                                                     
                                            } catch(PDOException $e){
                                                $mensaje = array(
                                                    'status' => false,
                                                    'mensaje' => 'Error al guardar el porcentaje pagado en la venta padre',
                                                    'error' => $e->getMessage()
                                                );
                                                return $mensaje;
                                            }
                                        }else{
                                            $idventa_cliente = null;
                                            $resuelto = 'false';
                                        }
                                        $stmt->bindParam(':idventa_cliente', $idventa_cliente );
                                        $stmt->bindParam(':resuelto', $resuelto );
                                        $porcentaje = ( ( floatval($pagos[$o]->ImpPagado) / floatval($pagos[$o]->venta->total_descuento) ) * 100 );								
                                        $stmt->bindParam(':porcentaje', $porcentaje );							
                                        $stmt->execute();
        
                                    } catch(PDOException $e){
                                        $mensaje = array(
                                            'status' => false,
                                            'mensaje' => 'Error al crear complemento pago venta',
                                            'error' => $e->getMessage()
                                        );
                                        return $mensaje;
                                    }
                                }
                            }                                     
                        }
                    }
                    $respuesta = true;
                    return $respuesta;
                } catch(PDOException $e){
                    $mensaje = array(
                        'status' => false,
                        'mensaje' => 'Error al cargar los cientes del cliente',
                        'error' => $e->getMessage()
                    );
                    return json_encode($mensaje);
                }
            }
            //<---------------------------------------------------------- XMLS CARGADOS DE TIPO EGRESO -------------------------------------------->count
            if( $tipo === 'egreso' ){
                $consulta = "SELECT * FROM proveedor_cliente WHERE idcliente = '$idcliente' ";
                try{
                    // Instanciar la base de datos
                    $db = new db();
                    // Conexión
                    $db = $db->connect();
                    $ejecutar = $db->query($consulta);
                    $proveedores_cliente = $ejecutar->fetchAll(PDO::FETCH_OBJ);
                    $db = null;
                    //<---------------------------- COMIENZA EL ORDENAMIENTO DE LOS XMLS ---------------------------->
                    for ($i=0; $i < count($xmls) ; $i++) {
                        //<----------- SI EL XML ES NUEVO Y NO SE HA INGRESADO ANTERIORMENTE Y ES DEDUCIBLE SE GUARDA ------->
                        if($xmls[$i]->estado === "nuevo" and $xmls[$i]->deducible === "si"){
                            //Vamos a realizar un for para obtener el iddocto_xml de la factura que estamos analizando
                            $uuid = $xmls[$i]->UUID;
                            $sql_busqueda = "SELECT iddocto_xml FROM `docto-xml` WHERE UUID = '$uuid' ";
                            try{
                                // Instanciar la base de datos
                                $db = new db();            
                                // Conexión
                                $db = $db->connect();
                                $ejecutar = $db->query($sql_busqueda);
                                $uuid = $ejecutar->fetch(PDO::FETCH_OBJ);
                                $db = null;
                                $xmls[$i]->iddocto_xml = $uuid->iddocto_xml;
                            } catch(PDOException $e){
                                $mensaje = array(
                                    'status' => false,
                                    'mensaje' => 'Error al obtener el iddocto_xml del xml que se clasifico para analizarlo',
                                    'error' => $e->getMessage()
                                );
                                return json_encode($mensaje);
                            }
                            $proveedor_cliente = '';
                            //<-------------------------------------- ACCIONES SI EL XML ES DE TIPO INGRESO ----------------------------------->
                            if($xmls[$i]->TipoDeComprobante === 'I'){
                                $tipo_compra = '';
                                if( $xmls[$i]->MetodoPago === 'PUE' ){
                                    $tipo_compra = 'contado';
                                }else{
                                    $tipo_compra = 'credito';
                                }
                                for ($j=0; $j < count($proveedores_cliente); $j++) {
                                    if( $xmls[$i]->Emisor->Rfc === $proveedores_cliente[$j]->rfc ){
                                        $proveedor_cliente = $proveedores_cliente[$j];
                                    }
                                }
                                $uuid = $xmls[$i]->UUID;
                                $sql = "INSERT INTO compra_cliente 
                                (idproveedor_cliente,idcliente,iddocto_xml,fecha,forma_pago,total,total_descuento,tipo,uuid) 
                                VALUES 
                                (:idproveedor_cliente,:idcliente,:iddocto_xml,:fecha,:forma_pago,:total,:total_descuento,:tipo,:uuid)";
                                try{
                                    // Get DB Object
                                    $db = new db();
                                    // Connect
                                    $db = $db->connect();
                                    $stmt = $db->prepare($sql);
                                    $stmt->bindParam(':idproveedor_cliente', $proveedor_cliente->idproveedor_cliente);
                                    $stmt->bindParam(':idcliente', $idcliente);
                                    $stmt->bindParam(':iddocto_xml', $xmls[$i]->iddocto_xml);
                                    $stmt->bindParam(':fecha', $xmls[$i]->Fecha);
                                    $stmt->bindParam(':forma_pago', $xmls[$i]->FormaPago);
                                    $stmt->bindParam(':total', $xmls[$i]->Total);
                                    $stmt->bindParam(':total_descuento', $xmls[$i]->Total);
                                    $stmt->bindParam(':tipo', $tipo_compra);
                                    $stmt->bindParam(':uuid', strtoupper($xmls[$i]->UUID));
                                    $stmt->execute();
    
                                } catch(PDOException $e){
                                    $mensaje = array(
                                        'status' => false,
                                        'mensaje' => 'Error al guardar compras',
                                        'error' => $e->getMessage()
                                    );
                                    echo json_encode($mensaje);
                                    return;
                                }
                            }
                            //<-------------------------------------- ACCIONES SI EL XML ES DE TIPO EGRESO ----------------------------------->
                            if( $xmls[$i]->TipoDeComprobante === 'E' ){
                                $relaciones = $xmls[$i]->UUIDS_relacionados;
                                $proveedor_cliente = '';
                                $compras_encontradas = [];
                                $compras_faltantes = [];
                                for ($j=0; $j < count($proveedores_cliente); $j++) {
                                    if( $xmls[$i]->Emisor->Rfc === $proveedores_cliente[$j]->rfc ){
                                        $proveedor_cliente = $proveedores_cliente[$j];
                                    }
                                }
                                //<---------------- OBTENER UUIDS DE LAS FACTURAS A LAS QUE SE LE APLICO UN DESCUENTO O DEVOLUCION --------->
                                if( count($relaciones) === 1 ){
                                    $uuid = $relaciones[0];
                                    $sql_docto = "SELECT * FROM compra_cliente WHERE uuid='$uuid' ";
                                     try{
                                        // Instanciar la base de datos
                                        $db = new db();
                                        // Conexión
                                        $db = $db->connect();
                                        $ejecutar = $db->query($sql_docto);
                                        $compra = $ejecutar->fetch(PDO::FETCH_OBJ);
                                        $devoluciones = [];                                        
                                        $idcompra_cliente = '';                                
                                        $db = null;
                                        $sql_credito = "INSERT INTO `credito_devolucion_compra` (idcompra_cliente,iddocto_xml,idproveedor_cliente,idcliente,total,forma_pago,fecha,resuelto,uuid_padre) VALUES (:idcompra_cliente,:iddocto_xml,:idproveedor_cliente,:idcliente,:total,:forma_pago,:fecha,:resuelto,:uuid_padre)";
                                        if( $compra != false ){
                                            //Si existe y es solo una la que viene relacionada, guardamos todos sus datos en la compra padre
                                            $compra->credito_dev = json_decode( $compra->credito_dev );                                        
                                            if( $compra->credito_dev === null  ){
                                                $devoluciones = [];
                                            }else{
                                                $devoluciones = $compra->credito_dev;
                                            }
                                            $resuelto = 'true';
                                            $idcompra_cliente = $compra->idcompra_cliente;
                                            $credito_dev = (object)[];
                                            $credito_dev->Impuestos = $xmls[$i]->Impuestos;
                                            $credito_dev->MetodoPago = $xmls[$i]->MetodoPago;
                                            $credito_dev->FormaPago = $xmls[$i]->FormaPago;
                                            $credito_dev->Total = $xmls[$i]->Total;
                                            //Restamos el total de la devolucion a la factura padre
                                            $total_descuento = $total_descuento - $xmls[$i]->Total;
                                            $credito_dev->TotalExento = $xmls[$i]->TotalExento;
                                            $credito_dev->TotalGravado = $xmls[$i]->TotalGravado;
                                            $credito_dev->TotalIEPS = $xmls[$i]->TotalIEPS;
                                            $credito_dev->TotalImpuestosRetenidos = $xmls[$i]->TotalImpuestosRetenidos;
                                            $credito_dev->TotalImpuestosTrasladados = $xmls[$i]->TotalImpuestosTrasladados;
                                            $credito_dev->UUID_tipo_relacion = $xmls[$i]->UUID_tipo_relacion;
                                            $credito_dev->uuid = $xmls[$i]->UUID;
                                            $credito_dev->especificado = true;
                                            array_push( $devoluciones, $credito_dev );
                                            // Obtenemos el total_descuento de la factura padre
                                            $total_descuento = $compra->total;
                                            for ($o=0; $o < count($devoluciones); $o++) { 
                                                $total_descuento = $total_descuento - $devoluciones[$o]->Total;
                                            }
                                            $devoluciones = json_encode($devoluciones);
                                            //Modificamos la factura compra_cliente para guardar si el credito o devolucion viene especificada la devolucion
                                            $sql_compra = "UPDATE compra_cliente SET
                                                credito_dev = :credito_dev,
                                                total_descuento = :total_descuento
                                            WHERE idcompra_cliente = '$idcompra_cliente'";
                                            try{
                                                $db = new db();
                                                $db = $db->connect();
                                                $stmt = $db->prepare($sql_compra);
                                                $stmt->bindParam(':credito_dev', $devoluciones);
                                                $stmt->bindParam(':total_descuento', $total_descuento);
                                                $stmt->execute();                                        
                                            } catch(PDOException $e){
                                                $mensaje = array(
                                                    'status' => false,
                                                    'mensaje' => 'Error al convertir la devolucion en string y guardarla dentro de la compra padre',
                                                    'error' => $e->getMessage()
                                                );
                                                return $mensaje;
                                            }
                                        }else{
                                            //Si no exite una compra guardamos el credito o devolucion como no resuelto
                                            $idcompra_cliente = '';
                                            $resuelto = 'false';                        
                                        }
                                        try{
                                            // Get DB Object
                                            $db = new db();
                                            // Connect
                                            $db = $db->connect();
                                            $stmt = $db->prepare($sql_credito);
                                            $stmt->bindParam(':idcompra_cliente', $idcompra_cliente);
                                            $stmt->bindParam(':iddocto_xml', $xmls[$i]->iddocto_xml);
                                            $stmt->bindParam(':idproveedor_cliente', $proveedor_cliente->idproveedor_cliente);
                                            $stmt->bindParam(':idcliente', $proveedor_cliente->idcliente);
                                            $stmt->bindParam(':total', $xmls[$i]->Total);
                                            $stmt->bindParam(':forma_pago', $xmls[$i]->FormaPago);
                                            $stmt->bindParam(':fecha', $xmls[$i]->Fecha);
                                            $stmt->bindParam(':resuelto', $resuelto);
                                            $stmt->bindParam(':uuid_padre',$relaciones[0]);                                                
                                            $stmt->execute();

                                        } catch(PDOException $e){
                                            $mensaje = array(
                                                'status' => false,
                                                'mensaje' => 'Error al guardar credito o devolucion donde el documento vinculado solo es uno',
                                                'error' => $e->getMessage()
                                            );
                                            return $mensaje;
                                        }

                                     }catch(PDOException $e){
                                        $mensaje = array(
                                            'status' => false,
                                            'mensaje' => 'Error al buscar facturas dentro del credito o devolucion en donde solo hay un documento relacionado',
                                            'error' => $e->getMessage()
                                        );
                                        return json_encode($mensaje);
                                    }
                                }else{
                                    $total_descuento = floatval($xmls[$i]->Total);
                                    $descuento = 0;
                                    $compras_encontradas = [];
                                    $compras_faltantes = 0;
                                    for ($j=0; $j < count($relaciones); $j++) {
                                        $uuid = $relaciones[$j];
                                        $sql_docto = "SELECT * FROM compra_cliente WHERE uuid = '$uuid' ";
                                        try{
                                            // Instanciar la base de datos
                                            $db = new db();
                                            // Conexión
                                            $db = $db->connect();
                                            $ejecutar = $db->query($sql_docto);
                                            $compra = $ejecutar->fetch(PDO::FETCH_OBJ);
                                            $db = null;
            
                                            if($compra){
                                                array_push($compras_encontradas,$compra);
                                                
                                            }else{
                                                $compras_faltantes = $compras_faltantes + 1;
                                            }                                            
                                        } catch(PDOException $e){
                                            $mensaje = array(
                                                'status' => false,
                                                'mensaje' => 'Error al buscar facturas dentro del credito o devolucion',
                                                'error' => $e->getMessage()
                                            );
                                            return json_encode($mensaje);
                                        }
                                    }
                                    //Aqui empezamos a desglosar los encontrados y no encontrados
                                    // Inicio del guardado en la base de datos los creditos o devoluciones encontradas
                                    $resuelto = 'true';
                                    for ($j=0; $j < count($compras_encontradas); $j++) { 
                                        # code...
                                        $sql_credito = "INSERT INTO `credito_devolucion_compra` (idcompra_cliente,iddocto_xml,idproveedor_cliente,idcliente,total,forma_pago,fecha,resuelto,uuid_padre) VALUES (:idcompra_cliente,:iddocto_xml,:idproveedor_cliente,:idcliente,:total,:forma_pago,:fecha,:resuelto,:uuid_padre)";
                                        try{
                                            // Get DB Object
                                            $db = new db();
                                            // Connect
                                            $db = $db->connect();
                                            $stmt = $db->prepare($sql_credito);
                                            $stmt->bindParam(':idcompra_cliente', $compras_encontradas[$j]->idcompra_cliente);
                                            $stmt->bindParam(':iddocto_xml', $xmls[$i]->iddocto_xml);
                                            $stmt->bindParam(':idproveedor_cliente', $proveedor_cliente->idproveedor_cliente);
                                            $stmt->bindParam(':idcliente', $proveedor_cliente->idcliente);
                                            if($total_descuento <= 0.0){
                                                $descuento = 0.0;
                                            }else{
                                                if( $total_descuento >=  $compras_encontradas[$j]->total ){
                                                    $descuento = $compras_encontradas[$j]->total;
                                                    $total_descuento = $total_descuento - $descuento;
                                                }else{
                                                    $descuento = $compras_encontradas[$j]->total;
                                                    $total_descuento = $total_descuento - $descuento;
                                                }
                                            }
                                            $stmt->bindParam(':total', $descuento);
                                            $stmt->bindParam(':forma_pago', $xmls[$i]->FormaPago);
                                            $stmt->bindParam(':fecha', $xmls[$i]->Fecha);
                                            $resuelto = 'true';
                                            $stmt->bindParam(':resuelto', $resuelto);
                                            $stmt->bindParam(':uuid_padre',$relaciones[$j]);                                            
                                            $stmt->execute();
                                            //Guardamos dentro de la factura padre el credito incompleto
                                            if( $compra->credito_dev === null  ){
                                                $devoluciones = [];
                                            }else{
                                                $devoluciones = json_decode( $compras_encontradas[$j]->credito_dev );
                                            }
                                            $idcompra_cliente = $compras_encontradas[$j]->idcompra_cliente;
                                            $credito_dev = (object)[];
                                            $credito_dev->MetodoPago = $xmls[$i]->MetodoPago;
                                            $credito_dev->FormaPago = $xmls[$i]->FormaPago;
                                            $credito_dev->UUID_tipo_relacion = $xmls[$i]->UUID_tipo_relacion;
                                            $credito_dev->Total = $descuento;
                                            $credito_dev->uuid = $xmls[$i]->UUID;
                                            $credito_dev->especificado = false;
                                            array_push( $devoluciones, $credito_dev );
                                            $devoluciones = json_encode($devoluciones);
                                            // Creamos la variable donde guardaremos el total del descuento de la factura padre y le restaremos el descuento
                                            $descuento_padre = $compras_encontradas[$i]->total_decuento;
                                            $descuento_padre = $descuento_padre - $xmls[$i]->Total;
                                            //Modificamos la factura compra_cliente para guardar si el credito o devolucion viene especificada la devolucion
                                            $sql_compra = "UPDATE compra_cliente SET
                                                credito_dev = :credito_dev,
                                                total_descuento = :total_descuento
                                            WHERE idcompra_cliente = '$idcompra_cliente'";
                                            try{
                                                $db = new db();
                                                $db = $db->connect();
                                                $stmt = $db->prepare($sql_compra);
                                                $stmt->bindParam(':credito_dev', $devoluciones);
                                                $stmt->bindParam(':total_descuento', $descuento_padre);
                                                $stmt->execute();                                        
                                            } catch(PDOException $e){
                                                $mensaje = array(
                                                    'status' => false,
                                                    'mensaje' => 'Error al convertir la devolucion en string y guardarla dentro de la compra padre cuando hay muchas relaciones',
                                                    'error' => $e->getMessage()
                                                );
                                                return $mensaje;
                                            }

                                        } catch(PDOException $e){
                                            $mensaje = array(
                                                'status' => false,
                                                'mensaje' => 'Error al crear el credito o devolucion en multiples documentos relacionados',
                                                'error' => $e->getMessage()
                                            );
                                            return $mensaje;
                                        }
                                    }
                                    // Inicio del guardado en la base de datos los creditos o devoluciones no encontradas
                                    $resuleto = 'false';
                                    $descuento = $total_descuento / count($compras_faltantes);
                                    for ($j=0; $j < count($compras_faltantes); $j++) { 
                                        # code...
                                        $sql = "INSERT INTO credito_devolucion_compra (iddocto_xml,idproveedor_cliente,idcliente,total,forma_pago,fecha,resuelto,uuid_padre) 
                                        VALUES 
                                        (:iddocto_xml,:idproveedor_cliente,:idcliente,:total,:forma_pago,:fecha,:resuelto,:uuid_padre)";
                                        try{											
                                            // Get DB Object
                                            $db = new db();
                                            // Connect
                                            $db = $db->connect();
                                            $stmt = $db->prepare($sql);
                                            
                                            $stmt->bindParam(':iddocto_xml', $xmls[$i]->iddocto_xml);
                                            $stmt->bindParam(':idproveedor_cliente', $proveedor_cliente->idproveedor_cliente);
                                            $stmt->bindParam(':idcliente', $proveedor_cliente->idcliente);
                                            $stmt->bindParam(':total', $descuento);
                                            $stmt->bindParam(':forma_pago', $xmls[$i]->FormaPago);
                                            $stmt->bindParam(':fecha', $xmls[$i]->Fecha);
                                            $resuelto = 'true';
                                            $stmt->bindParam(':resuelto', $resuelto);
                                            $stmt->bindParam(':uuid_padre',$relaciones[$j]);
                                            
                                            $stmt->execute();
    
                                        } catch(PDOException $e){
                                            $mensaje = array(
                                                'status' => false,
                                                'mensaje' => 'Error al guardar credito devolucion de multiples relaciones y xml padre no encontrado EGRESOS ',
                                                'error' => $e->getMessage()
                                            );
                                            echo json_encode($mensaje);
                                            return;
                                        }
                                    } 
                                }
                                //<--------------------------------------FIN FACTURA EGRESO -------------------------------------->
                            }
                            //<-------------------------------------- ACCIONES SI EL XML ES DE TIPO PAGO ----------------------------------->
                            if( $xmls[$i]->TipoDeComprobante === 'P' ){
                                $pagos = [];
                                $proveedor_cliente = '';
                                $pagos_encontradas = [];
                                $pagos_faltantes = [];
                                for ($j=0; $j < count($proveedores_cliente); $j++) {
                                    if( $xmls[$i]->Emisor->Rfc === $proveedores_cliente[$j]->rfc ){
                                        $proveedor_cliente = $proveedores_cliente[$j];
                                    }
                                }
                                // FOR PARA ENCONTRAR OBTENER LOS PAGOS REALIZADOS DENTRO DEL XML DE PAGO
                                for ($o=0; $o < count( $xmls[$i]->Complementos->Pagos ); $o++) { 
                                    for ($l=0; $l < count( $xmls[$i]->Complementos->Pagos[$o]->documentos ); $l++) { 
                                        # code...
                                        $pago = $xmls[$i]->Complementos->Pagos[$o]->documentos[$l];
                                        $pago->forma_pago = $xmls[$i]->Complementos->Pagos[$o]->FormaDePagoP;
                                        // $pago->fecha = $xmls[$i]->Complementos->Pagos[$o]->FechaPago;
                                        $pago->fecha = $xmls[$i]->Fecha;
                                        array_push($pagos,$pago);
                                    }
                                }
                                // For para obtener las ventas y guardarlas dentro de los pagos
                                for ($o=0; $o < count($pagos); $o++) {
                                    $uuid = strtoupper( $pagos[$o]->IdDocumento );
                                    $uuid = str_replace(" ","",$uuid);
                                    $consulta = "SELECT * FROM compra_cliente WHERE uuid = '$uuid'";
                                    try{
                                        // Instanciar la base de datos
                                        $db = new db();
        
                                        // Conexión
                                        $db = $db->connect();
                                        $ejecutar = $db->query($consulta);
                                        $compra = $ejecutar->fetch(PDO::FETCH_OBJ);
                                        $db = null;
        
                                        if($compra != false){
                                            $pagos[$o]->compra = $compra;
                                        }else{
                                            $pagos[$o]->compra = null;
                                        }
        
                                    } catch(PDOException $e){
                                        $mensaje = array(
                                            'status' => false,
                                            'mensaje' => 'Compra no encontrada xml de pago',
                                            'error' => $e->getMessage()
                                        );
                                        return json_encode($mensaje);
                                    }
                                }
                                //for para guardar en la base de datos los pagos con o sin el uuid padre
                                for ($o=0; $o < count($pagos) ; $o++){
                                    $sql = "INSERT INTO pagos_compra 
                                    (idcompra_cliente,iddocto_xml,idproveedor_cliente,idcliente,total,forma_pago,fecha,resuelto,uuid_padre,porcentaje) 
                                    VALUES 
                                    (:idcompra_cliente,:iddocto_xml,:idproveedor_cliente,:idcliente,:total,:forma_pago,:fecha,:resuelto,:uuid_padre,:porcentaje)";
                                    try{
                                        // Get DB Object
                                        $db = new db();
                                        // Connect
                                        $db = $db->connect();
                                        $stmt = $db->prepare($sql);
                                        $stmt->bindParam(':iddocto_xml', $xmls[$i]->iddocto_xml);
                                        $stmt->bindParam(':idproveedor_cliente', $proveedor_cliente->idproveedor_cliente);
                                        $stmt->bindParam(':idcliente', $proveedor_cliente->idcliente);
                                        $stmt->bindParam(':total', $pagos[$o]->ImpPagado );
                                        $stmt->bindParam(':forma_pago', $pagos[$o]->forma_pago );
                                        $stmt->bindParam(':fecha', $pagos[$o]->fecha );
                                        $stmt->bindParam(':uuid_padre', $pagos[$o]->IdDocumento );
                                        $resuelto = '';
                                        $porcentaje = 0.0;
                                        $idcompra_cliente = '';
                                        if($pagos[$o]->compra != null){
                                            $resuelto = 'true';
                                            $idcompra_cliente = $pagos[$o]->compra->idcompra_cliente;
                                            $porcentaje = floatval( $pagos[$o]->compra->porcentaje_pagado );
                                            $porcentaje = $porcentaje + ( ( floatval($pagos[$o]->ImpPagado) / floatval($pagos[$o]->compra->total_descuento) ) * 100 ); 
                                            //Modificamos la factura padre para guardar el porcentaje pagado
                                            $sql_compra_edit = "UPDATE compra_cliente SET
                                                porcentaje_pagado = :porcentaje_pagado
                                            WHERE idcompra_cliente = '$idcompra_cliente'";
                                            try{
                                                $db2 = new db();
                                                $db2 = $db2->connect();
                                                $stmt2 = $db2->prepare($sql_compra_edit);
                                                $stmt2->bindParam(':porcentaje_pagado', $porcentaje);
                                                $stmt2->execute();  
                                                $db2 = null;
                                                                                     
                                            } catch(PDOException $e){
                                                $mensaje = array(
                                                    'status' => false,
                                                    'mensaje' => 'Error al guardar el porcentaje pagado en la compra padre',
                                                    'error' => $e->getMessage()
                                                );
                                                return $mensaje;
                                            }
                                        }else{
                                            $idcompra_cliente = null;
                                            $resuelto = 'false';
                                        }
                                        $stmt->bindParam(':idcompra_cliente', $idcompra_cliente );
                                        $stmt->bindParam(':resuelto', $resuelto );
                                        $porcentaje = ( ( floatval($pagos[$o]->ImpPagado) / floatval($pagos[$o]->compra->total_descuento) ) * 100 );								
                                        $stmt->bindParam(':porcentaje', $porcentaje );							
                                        $stmt->execute();
        
                                    } catch(PDOException $e){
                                        $mensaje = array(
                                            'status' => false,
                                            'mensaje' => 'Error al crear complemento pago COMPRA',
                                            'error' => $e->getMessage()
                                        );
                                        return $mensaje;
                                    }
                                }
                            }
                        }
                    }
                    return true;
                } catch(PDOException $e){
                    $mensaje = array(
                        'status' => false,
                        'mensaje' => 'Error al cargar proveedores',
                        'error' => $e->getMessage()
                    );
                    return json_encode($mensaje);
                }
            }
        }
    }