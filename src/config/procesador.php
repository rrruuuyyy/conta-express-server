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
                    $clientes = $ejecutar->fetchAll(PDO::FETCH_OBJ);
                    $db = null;
                    //<---------------------------- COMIENZA EL ORDENAMIENTO DE LOS XMLS ---------------------------->
                    for ($i=0; $i < count($xmls) ; $i++) {
                        //<----------- SI EL XML ES NUEVO Y NO SE HA INGRESADO ANTERIORMENTE Y ES DEDUCIBLE SE GUARDA ------->
                        if($xmls[$i]->estado === "nuevo" and $xmls[$i]->deducible === "si"){
                            //>-------------------------- TIPO DE COMPROBANTE INGRESO ------------------------------->
                            if($xmls[$i]->TipoDeComprobante === 'I'){
                                $tipo_factura = '';
                                $cliente = '';
                                if( $xmls[$i]->MetodoPago === 'PUE' ){
                                    $tipo_factura = 'contado';
                                }else{
                                    $tipo_factura = 'credito';
                                }
                                for ($j=0; $j < count($clientes); $j++) {
                                    if( $xmls[$i]->Receptor->Rfc === $clientes[$j]->rfc ){
                                        $cliente = $clientes[$j];
                                    }
                                }
                                $uuid = $xmls[$i]->UUID;
                                $sql_docto = "SELECT * FROM `docto-xml` WHERE UUID = '$uuid' ";
                                try{
                                    // Instanciar la base de datos
                                    $db = new db();
                                    // Conexión
                                    $db = $db->connect();
                                    $ejecutar = $db->query($sql_docto);
                                    $factura = $ejecutar->fetch(PDO::FETCH_OBJ);
                                    $db = null;
        
                                    $sql = "INSERT INTO venta_cliente (idcliente_cliente,idcliente,iddocto_xml,fecha,forma_pago,total,tipo,uuid) VALUES (:idcliente_cliente,:idcliente,:iddocto_xml,:fecha,:forma_pago,:total,:tipo,:uuid)";
                                    try{
                                        // Get DB Object
                                        $db = new db();
                                        // Connect
                                        $db = $db->connect();
                                        $stmt = $db->prepare($sql);
                                        $stmt->bindParam(':idcliente_cliente', $cliente->idcliente_cliente);
                                        $stmt->bindParam(':idcliente', $idcliente);
                                        $stmt->bindParam(':iddocto_xml', $factura->iddocto_xml);
                                        $stmt->bindParam(':fecha', $factura->Fecha);
                                        $stmt->bindParam(':forma_pago', $factura->FormaPago);
                                        $stmt->bindParam(':total', $factura->Total);
                                        $stmt->bindParam(':tipo', $tipo_factura);
                                        $stmt->bindParam(':uuid', strtoupper($factura->UUID));
                                        $stmt->execute();
        
                                    } catch(PDOException $e){
                                        $respuesta = array(
                                            'status' => false,
                                            'mensaje' => 'Error al guardar compras',
                                            'error' => $e->getMessage()
                                        );
                                        return $respuesta;
                                    }
        
                                } catch(PDOException $e){
                                    $respuesta = array(
                                        'status' => false,
                                        'mensaje' => 'Error al buscar factura',
                                        'error' => $e->getMessage()
                                    );
                                    return $respuesta;
                                }
                            }
                            //<-------------------------------------- ACCIONES SI EL XML ES DE TIPO EGRESO ----------------------------------->
                            if( $xmls[$i]->TipoDeComprobante === 'E' ){
                                $cliente_cliente = '';
                                $ventas_encontradas = [];
                                $ventas_faltantes = [];
                                for ($j=0; $j < count($clientes); $j++) {
                                    if( $xmls[$i]->Receptor->Rfc === $clientes[$j]->rfc ){
                                        $cliente_cliente = $clientes[$j];
                                    }
                                }
                                //<---------------- OBTENER UUIDS DE LAS FACTURAS A LAS QUE SE LE APLICO UN DESCUENTO O DEVOLUCION --------->
                                for ($j=0; $j < count($xmls[$i]->UUIDS_relacionados); $j++) {
                                    $uuid = $xmls[$i]->UUIDS_relacionados[$j];
                                    $sql_docto = "SELECT * FROM `venta_cliente` WHERE uuid = '$uuid' ";
                                    try{
                                        // Instanciar la base de datos
                                        $db = new db();
                                        // Conexión
                                        $db = $db->connect();
                                        $ejecutar = $db->query($sql_docto);
                                        $venta = $ejecutar->fetch(PDO::FETCH_OBJ);
                                        $db = null;
        
                                        if($venta){
                                            array_push($ventas_encontradas, $venta);
                                        }else{
                                            array_push($ventas_faltantes, $venta);
                                        }
                                        //Buscamos el documento de la factura en nuestra base de datos
                                        $uuid = $xmls[$i]->UUID;
                                        $sql_docto = "SELECT * FROM `docto-xml` WHERE UUID = '$uuid' ";
                                        try{
                                            // Instanciar la base de datos
                                            $db = new db();
        
                                            // Conexión
                                            $db = $db->connect();
                                            $ejecutar = $db->query($sql_docto);
                                            $factura = $ejecutar->fetch(PDO::FETCH_OBJ);
                                            $db = null;
                                            
                                            //Creamos la tabla de credito o devolucion con la factura encontrada
                                            $total_descuento = floatval($xmls[$i]->Total);
                                            for ($k=0; $k < count($ventas_encontradas); $k++) {
                                                $sql = "INSERT INTO credito_devolucion_venta (idventa_cliente,iddocto_xml,idcliente_cliente,idcliente,total,forma_pago,fecha,resuelto,uuid_padre) 
                                                VALUES 
                                                (:idventa_cliente,:iddocto_xml,:idcliente_cliente,:idcliente,:total,:forma_pago,:fecha,:resuelto,:uuid_padre)";
                                                try{
                                                    
                                                    // Get DB Object
                                                    $db = new db();
                                                    // Connect
                                                    $db = $db->connect();
                                                    $stmt = $db->prepare($sql);
                                                    $stmt->bindParam(':idventa_cliente', $ventas_encontradas[$k]->idventa_cliente );
                                                    $stmt->bindParam(':iddocto_xml', $factura->iddocto_xml );
                                                    $stmt->bindParam(':idcliente_cliente', $cliente_cliente->idcliente_cliente);
                                                    $stmt->bindParam(':idcliente', $cliente_cliente->idcliente);
                                                    if( $ventas_encontradas[$k]->total <= $total_descuento ){
                                                        $stmt->bindParam(':total', $ventas_encontradas[$k]->total);
                                                        $total_descuento = $total_descuento  - floatval($ventas_encontradas[$k]->total);
                                                    }else{
                                                        $stmt->bindParam(':total', $total_descuento);
                                                    }
                                                    $stmt->bindParam(':total', $factura->FormaPago);
                                                    $stmt->bindParam(':forma_pago', $factura->FormaPago);
                                                    $stmt->bindParam(':fecha', $factura->Fecha);
                                                    $stmt->bindParam(':resuelto', true);
                                                    $stmt->bindParam(':uuid_padre', strtoupper( $ventas_encontradas[$k]->uuid ));
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
                                            } //<------------------------------------ FINAL DE DOCUMENTOS PADRE ENCONTRADOS ------------------------------------------->
                                            //<--------------------iNICIO DOCUMENTOS PADRE NO ENCONTRADOS ----------------------->
                                            for ($k=0; $k < count($ventas_faltantes); $k++) {
                                                $sql = "INSERT INTO credito_devolucion_venta (idventa_cliente,iddocto_xml,idcliente_cliente,idcliente,total,forma_pago,fecha,resuelto,uuid_padre) 
                                                VALUES 
                                                (:idventa_cliente,:iddocto_xml,:idcliente_cliente,:idcliente,:total,:forma_pago,:fecha,:resuelto,:uuid_padre)";
                                                try{
                                                    
                                                    // Get DB Object
                                                    $db = new db();
                                                    // Connect
                                                    $db = $db->connect();
                                                    $stmt = $db->prepare($sql);
                                                    $stmt->bindParam(':iddocto_xml', $factura->iddocto_xml );
                                                    $stmt->bindParam(':idcliente_cliente', $cliente_cliente->idcliente_cliente);
                                                    $stmt->bindParam(':idcliente', $cliente_cliente->idcliente);
                                                    if( $ventas_encontradas[$k]->total <= $total_descuento ){
                                                        $stmt->bindParam(':total', $ventas_encontradas[$k]->total);
                                                        $total_descuento = $total_descuento  - floatval($ventas_encontradas[$k]->total);
                                                    }else{
                                                        $stmt->bindParam(':total', $total_descuento);
                                                    }
                                                    $stmt->bindParam(':total', $factura->FormaPago);
                                                    $stmt->bindParam(':forma_pago', $factura->FormaPago);
                                                    $stmt->bindParam(':fecha', $factura->Fecha);
                                                    $stmt->bindParam(':resuelto', false);
                                                    $stmt->bindParam(':uuid_padre', strtoupper( $ventas_encontradas[$k]->uuid ));
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
        
                                        } catch(PDOException $e){
                                            $mensaje = array(
                                                'status' => false,
                                                'mensaje' => 'Error al buscar docto factura ingreso o egreso',
                                                'error' => $e->getMessage()
                                            );
                                            return json_encode($mensaje);
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
                                $sql_docto = "SELECT * FROM `docto-xml` WHERE UUID = '$uuid' ";
                                try{
                                    // Instanciar la base de datos
                                    $db = new db();
                                    // Conexión
                                    $db = $db->connect();
                                    $ejecutar = $db->query($sql_docto);
                                    $factura = $ejecutar->fetch(PDO::FETCH_OBJ);
                                    $db = null;
        
                                    $sql = "INSERT INTO venta_cliente (idcliente_cliente,idcliente,iddocto_xml,fecha,forma_pago,total,tipo,uuid) VALUES (:idcliente_cliente,:idcliente,:iddocto_xml,:fecha,:forma_pago,:total,:tipo,:uuid)";
                                    try{
                                        // Get DB Object
                                        $db = new db();
                                        // Connect
                                        $db = $db->connect();
                                        $stmt = $db->prepare($sql);
                                        $stmt->bindParam(':idcliente_cliente', $cliente->idcliente_cliente);
                                        $stmt->bindParam(':idcliente', $idcliente);
                                        $stmt->bindParam(':iddocto_xml', $factura->iddocto_xml);
                                        $stmt->bindParam(':fecha', $factura->Fecha);
                                        $stmt->bindParam(':forma_pago', $factura->FormaPago);
                                        $stmt->bindParam(':total', $factura->Total);
                                        $stmt->bindParam(':tipo', $tipo_factura);
                                        $stmt->bindParam(':uuid', strtoupper($factura->UUID));
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
        
                                } catch(PDOException $e){
                                    $mensaje = array(
                                        'status' => false,
                                        'mensaje' => 'Error al buscar factura',
                                        'error' => $e->getMessage()
                                    );
                                    return json_encode($mensaje);
                                }
                                //<--------------------------------------FIN FACTURA EGRESO -------------------------------------->
                            }
                            if( $xmls[$i]->TipoDeComprobante === 'P' ){
                                $cliente_cliente = '';
                                $ventas_encontradas = [];
                                $ventas_faltantes = [];
                                $pagos = [];
                                for ($j=0; $j < count($clientes); $j++) {
                                    if( $xmls[$i]->Receptor->Rfc === $clientes[$j]->rfc ){
                                        $cliente_cliente = $clientes[$j];
                                    }
                                }
                                // FOR PARA ENCONTRAR OBTENER LOS PAGOS REALIZADOS DENTRO DEL XML DE PAGO
                                for ($o=0; $o < count( $xmls[$i]->Complementos->Pagos ); $o++) { 
                                    for ($l=0; $l < count( $xmls[$i]->Complementos->Pagos[$o]->documentos ); $l++) { 
                                        # code...
                                        array_push($pagos,$xmls[$i]->Complementos->Pagos[$o]->documentos[$l] );
                                        $pagos[$o]->forma_pago = $xmls[$i]->Complementos->Pagos[$o]->FormaDePagoP;
                                        $pagos[$o]->fecha = $xmls[$i]->Complementos->Pagos[$o]->FechaPago;
                                    }
                                }
                                // For para obtener las ventas y guardarlas dentro de los pagos
                                for ($o=0; $o < count($pagos); $o++) {
                                    $uuid = strtoupper( $pagos[$o]->IdDocumento );
                                    $uuid = str_replace(" ","",$uuid);
                                    $consulta = "SELECT * FROM venta_cliente WHERE uuid='$uuid'";
                                    try{
                                        // Instanciar la base de datos
                                        $db = new db();
        
                                        // Conexión
                                        $db = $db->connect();
                                        $ejecutar = $db->query($consulta);
                                        $venta = $ejecutar->fetch(PDO::FETCH_OBJ);
                                        $db = null;
        
                                        if( $venta ){
                                            $pagos[$o]->venta = $venta;
                                        }else{
                                            $pagos[$o]->venta = null;
                                        }
        
                                    } catch(PDOException $e){
                                        $mensaje = array(
                                            'status' => false,
                                            'mensaje' => 'Venta no encontrada xml de pago',
                                            'error' => $e->getMessage()
                                        );
                                        return json_encode($mensaje);
                                    }
                                }
                                //For para obtener el iddocto del xml de pago
                                $uuid = $xmls[$i]->UUID;
                                $factura_pago = "SELECT * FROM `docto-xml` WHERE UUID='$uuid'";
                                try{
                                    // Instanciar la base de datos
                                    $db = new db();
        
                                    // Conexión
                                    $db = $db->connect();
                                    $ejecutar = $db->query($factura_pago);
                                    $factura_pago = $ejecutar->fetch(PDO::FETCH_OBJ);
                                    $db = null;
        
                                } catch(PDOException $e){
                                    $mensaje = array(
                                        'status' => false,
                                        'mensaje' => 'Error al buscar el docto-xml de el comprobante Pago',
                                        'error' => $e->getMessage()
                                    );
                                    return json_encode($mensaje);
                                }
                                //for para guardar en la base de datos los pagos con o sin el uuid padre
                                for ($o=0; $o < count($pagos) ; $o++){
                                    $sql = "INSERT INTO cliente 
                                    (idventa_cliente,iddocto_xml,idcliente_cliente,total,forma_pago,fecha,resuelto,uuid_padre) 
                                    VALUES 
                                    (:idventa_cliente,:iddocto_xml,:idcliente_cliente,:total,:forma_pago,:fecha,:resuelto,:uuid_padre)";
                                    try{
                                        // Get DB Object
                                        $db = new db();
                                        // Connect
                                        $db = $db->connect();
                                        $stmt = $db->prepare($sql);
                                        $stmt->bindParam(':iddocto_xml', $factura_pago->iddocto_xml);
                                        $stmt->bindParam(':idcliente_cliente', $cliente_cliente->idcliente_cliente);
                                        $stmt->bindParam(':total', $pagos[$o]->ImpPagado );
                                        $stmt->bindParam(':forma_pago', $pagos[$o]->forma_pago );
                                        $stmt->bindParam(':fecha', $pagos[$o]->fecha );
                                        $stmt->bindParam(':uuid_padre', $pagos[$o]->IdDocumento );
                                        if($pagos[$o]->venta != null){
                                            $stmt->bindParam(':idventa_cliente', $pagos[$o]->venta->idventa_cliente );
                                            $stmt->bindParam(':resuelto', true );
        
                                        }else{
                                            $stmt->bindParam(':resuelto', false );									
                                        }
                                        $stmt->execute();
                                        $mensaje = array(
                                            'status' => true,
                                            'mensaje' => 'Cliente creado',
                                            'rest' => ''
                                        );
                                        echo json_encode($mensaje);
                                        return;
        
                                    } catch(PDOException $e){
                                        $mensaje = array(
                                            'status' => false,
                                            'mensaje' => 'Cliente no creado',
                                            'error' => $e->getMessage()
                                        );
                                        echo json_encode($mensaje);
                                        return;
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
                        'mensaje' => 'Clientes no cargados',
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
                                $sql_docto = "SELECT * FROM `docto-xml` WHERE UUID = '$uuid' ";
                                try{
                                    // Instanciar la base de datos
                                    $db = new db();
                                    // Conexión
                                    $db = $db->connect();
                                    $ejecutar = $db->query($sql_docto);
                                    $factura_en_curso = $ejecutar->fetch(PDO::FETCH_OBJ);
                                    $db = null;
        
                                    $sql = "INSERT INTO compra_cliente 
                                    (idproveedor_cliente,idcliente,iddocto_xml,fecha,forma_pago,total,tipo,uuid) 
                                    VALUES 
                                    (:idproveedor_cliente,:idcliente,:iddocto_xml,:fecha,:forma_pago,:total,:tipo,:uuid)";
                                    try{
                                        // Get DB Object
                                        $db = new db();
                                        // Connect
                                        $db = $db->connect();
                                        $stmt = $db->prepare($sql);
                                        $stmt->bindParam(':idproveedor_cliente', $proveedor_cliente->idproveedor_cliente);
                                        $stmt->bindParam(':idcliente', $idcliente);
                                        $stmt->bindParam(':iddocto_xml', $factura_en_curso->iddocto_xml);
                                        $stmt->bindParam(':fecha', $factura_en_curso->Fecha);
                                        $stmt->bindParam(':forma_pago', $factura_en_curso->FormaPago);
                                        $stmt->bindParam(':total', $factura_en_curso->Total);
                                        $stmt->bindParam(':tipo', $tipo_compra);
                                        $stmt->bindParam(':uuid', strtoupper($factura_en_curso->UUID));
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
        
                                } catch(PDOException $e){
                                    $mensaje = array(
                                        'status' => false,
                                        'mensaje' => 'Error al buscar factura',
                                        'error' => $e->getMessage()
                                    );
                                    return json_encode($mensaje);
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
                                    $sql_docto = "SELECT * FROM compra_cliente INNER JOIN `docto-xml` AS doc ON compra_cliente.iddocto_xml=doc.iddocto_xml
                                     WHERE compra_cliente.uuid = '$uuid' ";
                                     try{
                                        // Instanciar la base de datos
                                        $db = new db();
                                        // Conexión
                                        $db = $db->connect();
                                        $ejecutar = $db->query($sql_docto);
                                        $compra = $ejecutar->fetch(PDO::FETCH_OBJ);
                                        $db = null;
                                     }catch(PDOException $e){
                                        $mensaje = array(
                                            'status' => false,
                                            'mensaje' => 'Error al buscar facturas dentro del credito o devolucion en donde solo hay un documento relacionado',
                                            'error' => $e->getMessage()
                                        );
                                        return json_encode($mensaje);
                                    }
                                }else{
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
                                                array_push($compras_encontradas, $compra);
                                            }else{
                                                $compra->uuid = $uuid;
                                                array_push($compras_faltantes, $compra);
                                            }
                                            //Buscamos el documento de la factura en nuestra base de datos
                                            $uuid = $xmls[$i]->UUID;
                                            $sql_docto = "SELECT * FROM `docto-xml` WHERE UUID = '$uuid' ";
                                            try{
                                                // Instanciar la base de datos
                                                $db = new db();
            
                                                // Conexión
                                                $db = $db->connect();
                                                $ejecutar = $db->query($sql_docto);
                                                $factura_en_curso = $ejecutar->fetch(PDO::FETCH_OBJ);
                                                $db = null;
                                                //Creamos la tabla de credito o devolucion con la factura encontrada
                                                $total_descuento = floatval($factura_en_curso->Total);
                                                $descuento = 0;
                                                for ($l=0; $l < count($compras_encontradas) ; $l++) { 
                                                    $sql_credito = "INSERT INTO `credito_devolucion_compra` (idcompra_cliente,iddocto_xml,idproveedor_cliente,idcliente,total,forma_pago,fecha,resuelto,uuid_padre) VALUES (:idcompra_cliente,:iddocto_xml,:idproveedor_cliente,:idcliente,:total,:forma_pago,:fecha,:resuelto,:uuid_padre)";
                                                    try{
                                                        // Get DB Object
                                                        $db = new db();
                                                        // Connect
                                                        $db = $db->connect();
                                                        $stmt = $db->prepare($sql_credito);
                                                        $stmt->bindParam(':idcompra_cliente', $compras_encontradas[$l]->idcompra_cliente);
                                                        $stmt->bindParam(':iddocto_xml', $factura_en_curso->iddocto_xml);
                                                        $stmt->bindParam(':idproveedor_cliente', $proveedor_cliente->idproveedor_cliente);
                                                        $stmt->bindParam(':idcliente', $proveedor_cliente->idcliente);
                                                        if($total_descuento <= 0.0){
                                                            $descuento = 0;
                                                        }else{
                                                            if( $total_descuento <= $compras_encontradas[$l]->total ){
                                                                $descuento = $total_descuento;
                                                                $total_descuento = $total_descuento - $descuento;
                                                            }else{
                                                                $descuento = $compras_encontradas[$l]->total;
                                                                $total_descuento = $total_descuento - $descuento;
                                                            }
                                                        }
                                                        $stmt->bindParam(':total', $descuento);
                                                        $stmt->bindParam(':forma_pago', $factura_en_curso->FormaPago);
                                                        $stmt->bindParam(':fecha', $factura_en_curso->Fecha);
                                                        $resuelto = 'true';
                                                        $stmt->bindParam(':resuelto', $resuelto);
                                                        $stmt->bindParam(':uuid_padre',$compras_encontradas[$l]->uuid);
                                                        
                                                        $stmt->execute();
            
                                                    } catch(PDOException $e){
                                                        $mensaje = array(
                                                            'status' => false,
                                                            'mensaje' => 'Error al crear el credito o devolucion',
                                                            'error' => $e->getMessage()
                                                        );
                                                        return $mensaje;
                                                    }
                                                }
                                                //<--------------------iNICIO DOCUMENTOS PADRE NO ENCONTRADOS ----------------------->
                                                $descuento = $total_descuento / count($compras_faltantes);
                                                for ($k=0; $k < count($compras_faltantes); $k++) {
                                                    $sql = "INSERT INTO credito_devolucion_compra (idventa_cliente,iddocto_xml,idproveedor_cliente,idcliente,total,forma_pago,fecha,resuelto,uuid_padre) 
                                                    VALUES 
                                                    (:idventa_cliente,:iddocto_xml,:idproveedor_cliente,:idcliente,:total,:forma_pago,:fecha,:resuelto,:uuid_padre)";
                                                    try{											
                                                        // Get DB Object
                                                        $db = new db();
                                                        // Connect
                                                        $db = $db->connect();
                                                        $stmt = $db->prepare($sql);
                                                        
                                                        $stmt->bindParam(':iddocto_xml', $factura_en_curso->iddocto_xml);
                                                        $stmt->bindParam(':idproveedor_cliente', $proveedor_cliente->idproveedor_cliente);
                                                        $stmt->bindParam(':idcliente', $proveedor_cliente->idcliente);
                                                        $stmt->bindParam(':total', $descuento);
                                                        $stmt->bindParam(':forma_pago', $factura_en_curso->FormaPago);
                                                        $stmt->bindParam(':fecha', $factura_en_curso->Fecha);
                                                        $resuelto = 'true';
                                                        $stmt->bindParam(':resuelto', $resuelto);
                                                        $stmt->bindParam(':uuid_padre',$compras_faltantes[$k]->uuid);
                                                        
                                                        $stmt->execute();
                
                                                    } catch(PDOException $e){
                                                        $mensaje = array(
                                                            'status' => false,
                                                            'mensaje' => 'Error al guardar compras faltantes',
                                                            'error' => $e->getMessage()
                                                        );
                                                        echo json_encode($mensaje);
                                                        return;
                                                    }
                                                }
            
                                            } catch(PDOException $e){
                                                $mensaje = array(
                                                    'status' => false,
                                                    'mensaje' => 'Error al buscar docto factura ingreso o egreso',
                                                    'error' => $e->getMessage()
                                                );
                                                return json_encode($mensaje);
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
                                        $pago->fecha = $xmls[$i]->Complementos->Pagos[$o]->FechaPago;
                                        array_push($pagos,$pago);
                                    }
                                }
                                // For para obtener las ventas y guardarlas dentro de los pagos
                                for ($o=0; $o < count($pagos); $o++) {
                                    $uuid = strtoupper( $pagos[$o]->IdDocumento );
                                    $uuid = str_replace(" ","",$uuid);
                                    $consulta = "SELECT * FROM compra_cliente WHERE uuid like '$uuid'";
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
                                array_push($pagos_revisar,$pagos);
                                //For para obtener el iddocto del xml de pago
                                $uuid = $xmls[$i]->UUID;
                                $factura_pago = "SELECT * FROM `docto-xml` WHERE UUID = '$uuid'";
                                try{
                                    // Instanciar la base de datos
                                    $db = new db();
        
                                    // Conexión
                                    $db = $db->connect();
                                    $ejecutar = $db->query($factura_pago);
                                    $factura_en_curso = $ejecutar->fetch(PDO::FETCH_OBJ);
                                    $db = null;
        
                                } catch(PDOException $e){
                                    $mensaje = array(
                                        'status' => false,
                                        'mensaje' => 'Error al buscar el docto-xml de el comprobante Pago',
                                        'error' => $e->getMessage()
                                    );
                                    return json_encode($mensaje);
                                }
                                //for para guardar en la base de datos los pagos con o sin el uuid padre
                                for ($o=0; $o < count($pagos) ; $o++){
                                    $sql = "INSERT INTO pagos_compra 
                                    (idcompra_cliente,iddocto_xml,idproveedor_cliente,idcliente,total,forma_pago,fecha,resuelto,uuid_padre) 
                                    VALUES 
                                    (:idcompra_cliente,:iddocto_xml,:idproveedor_cliente,:idcliente,:total,:forma_pago,:fecha,:resuelto,:uuid_padre)";
                                    try{
                                        // Get DB Object
                                        $db = new db();
                                        // Connect
                                        $db = $db->connect();
                                        $stmt = $db->prepare($sql);
                                        $stmt->bindParam(':iddocto_xml', $factura_en_curso->iddocto_xml);
                                        $stmt->bindParam(':idproveedor_cliente', $proveedor_cliente->idproveedor_cliente);
                                        $stmt->bindParam(':idcliente', $proveedor_cliente->idcliente);
                                        $stmt->bindParam(':total', $pagos[$o]->ImpPagado );
                                        $stmt->bindParam(':forma_pago', $pagos[$o]->forma_pago );
                                        $stmt->bindParam(':fecha', $pagos[$o]->fecha );
                                        $stmt->bindParam(':uuid_padre', $pagos[$o]->IdDocumento );
                                        $resuelto = '';
                                        $idcompra_cliente = '';
                                        if($pagos[$o]->compra != null){
                                            $resuelto = 'true';
                                            $idcompra_cliente = $pagos[$o]->compra->idcompra_cliente;
        
                                        }else{
                                            $idcompra_cliente = null;
                                            $resuelto = 'false';
                                        }
                                        $stmt->bindParam(':idcompra_cliente', $idcompra_cliente );
                                        $stmt->bindParam(':resuelto', $resuelto );									
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
                        'mensaje' => 'Clientes no cargados',
                        'error' => $e->getMessage()
                    );
                    return json_encode($mensaje);
                }
            }
        }
    }