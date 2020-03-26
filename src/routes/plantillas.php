<?php
    error_reporting(-1);
    use \Psr\Http\Message\ServerRequestInterface as Request;
    use \Psr\Http\Message\ResponseInterface as Response;

    //OBTENER TODAS LAS PLANTILLAS
    $app->post('/api/plantilla/get', function(Request $request, Response $response){
        $idcliente = $request->getParam('idcliente');
        $token = $request->getParam('token');
        $plantillas = null;
        $sql_cert = "SELECT * FROM plantilla WHERE idcliente = '$idcliente'";
        try{
            // Instanciar la base de datos
            $db = new db();
            // Conexión
            $db = $db->connect();
            $ejecutar = $db->query($sql_cert);
            $plantillas = $ejecutar->fetchAll(PDO::FETCH_OBJ);
            $db = null;
            for ($i=0; $i < count($plantillas); $i++) { 
                $plantillas[$i]->concepto = json_decode($plantillas[$i]->concepto);
                $plantillas[$i]->impuestos_locales = json_decode($plantillas[$i]->impuestos_locales);
            }
            $mensaje = array(
                'status' => true,
                'mensaje' => 'Plantillas cargadas correctamente',
                'rest' => $plantillas
            );
            echo json_encode($mensaje);
            return;
        } catch(PDOException $e){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Error al cargar plantillas',
                'error' => $e->getMessage()
            );
            echo json_encode($mensaje);
            return;
        }
    });
    //CREAR UNA NUEVA PLANTILLA
    $app->post('/api/plantilla/new', function(Request $request, Response $response){
        $idcliente = $request->getParam('idcliente');
        $nombre = $request->getParam('nombre');
        $codigo_postal = $request->getParam('codigo_postal');
        $moneda = $request->getParam('moneda');
        $forma_pago = $request->getParam('forma_pago');
        $metodo_pago = $request->getParam('metodo_pago');
        $concepto = $request->getParam('concepto');
        $impuestos_locales = $request->getParam('impuestos_locales');
        $token = $request->getParam('token');
        // Concertimos en string nuestras variables para poder guardarlas en mysql
        $concepto = json_encode( $concepto );
        $impuestos_locales = json_encode( $impuestos_locales );
        $sql = "INSERT INTO plantilla (idcliente,nombre,codigo_postal,moneda,forma_pago,metodo_pago,concepto,impuestos_locales) 
        VALUES (:idcliente,:nombre,:codigo_postal,:moneda,:forma_pago,:metodo_pago,:concepto,:impuestos_locales)";
        try{
            // Get DB Object
            $db = new db();
            // Connect
            $db = $db->connect();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':idcliente', $idcliente);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':codigo_postal', $codigo_postal);
            $stmt->bindParam(':moneda', $moneda);
            $stmt->bindParam(':forma_pago', $forma_pago);
            $stmt->bindParam(':metodo_pago', $metodo_pago);
            $stmt->bindParam(':concepto', $concepto);
            $stmt->bindParam(':impuestos_locales', $impuestos_locales);
            $stmt->execute();
            $mensaje = array(
                'status' => true,
                'mensaje' => 'Plantilla creada correctamente',
                'rest' => ''
            );
            echo json_encode($mensaje);
            return;

        } catch(PDOException $e){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Error al guardar la plantilla',
                'error' => $e->getMessage()
            );
            echo json_encode($mensaje);
            return;
        }
    });
    //ACTUALIZAR UNA NUEVA PLANTILLA
    $app->put('/api/plantilla/update', function(Request $request, Response $response){
        $idcliente = $request->getParam('idcliente');
        $nombre = $request->getParam('nombre');
        $codigo_postal = $request->getParam('codigo_postal');
        $moneda = $request->getParam('moneda');
        $forma_pago = $request->getParam('forma_pago');
        $metodo_pago = $request->getParam('metodo_pago');
        $concepto = $request->getParam('concepto');
        $impuestos_locales = $request->getParam('impuestos_locales');
        $token = $request->getParam('token');
        // Concertimos en string nuestras variables para poder guardarlas en mysql
        $concepto = json_encode( $concepto );
        $impuestos_locales = json_encode( $impuestos_locales );
        $sql = "UPDATE plantilla SET
                nombre   = :nombre,
                codigo_postal   = :codigo_postal,
                moneda     = :moneda,
                forma_pago       = :forma_pago,
                metodo_pago       = :metodo_pago,
                concepto      = :concepto,
                impuestos_locales      = :impuestos_locales
            WHERE idplantilla = $idplantilla";
        try{
            // Get DB Object
            $db = new db();
            // Connect
            $db = $db->connect();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':codigo_postal', $codigo_postal);
            $stmt->bindParam(':moneda', $moneda);
            $stmt->bindParam(':forma_pago', $forma_pago);        
            $stmt->bindParam(':metodo_pago', $metodo_pago);
            $stmt->bindParam(':concepto', $concepto);
            $stmt->bindParam(':impuestos_locales', $impuestos_locales);
            $stmt->execute();
            $mensaje = array(
                'status' => true,
                'mensaje' => 'Plantilla actualizada correctamente',
                'rest' => ''
            );
            echo json_encode($mensaje);
            return;

        } catch(PDOException $e){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Error al actualizar la plantilla',
                'error' => $e->getMessage()
            );
            echo json_encode($mensaje);
            return;
        }
    });
    //ACTUALIZAR UNA NUEVA PLANTILLA
    $app->put('/api/plantilla/delete', function(Request $request, Response $response){
        $idplantilla = $request->getParam('idplantilla');
        $token = $request->getParam('token');
        $sql = "DELETE FROM plantilla WHERE idplantilla = '$idplantilla'";
        try{
            // Get DB Object
            $db = new db();
            // Connect
            $db = $db->connect();

            $stmt = $db->prepare($sql);
            $stmt->execute();
            $db = null;
            $mensaje = array(
                'status' => true,
                'mensaje' => 'Plantilla eliminada satisfactoriamente',
                'rest' => ''
            );
            echo json_encode($mensaje);
        } catch(PDOException $e){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Error al eliminar plantilla',
                'error' => $e->getMessage()
            );
            echo json_encode($mensaje);
        }
    });