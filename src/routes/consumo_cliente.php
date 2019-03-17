<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
$app->post('/api/consumo_cliente/get', function(Request $request, Response $response){
    // $idusuario = $request->getParam('idusuario');
    $idcliente = $request->getParam('idcliente');
    $token = $request->getParam('token');
    //  Verificacion del token
    // $auth = new Auth();
    // $token = $auth->Check($token);
    // if($token['status'] === false){
    //     echo json_encode($token);
    //     return;
    // }
    // Fin Verificacion
    $consulta = "SELECT * FROM `consumo-cliente` WHERE idcliente = '$idcliente' ";
    try{
        // Instanciar la base de datos
        $db = new db();

        // Conexión
        $db = $db->connect();
        $ejecutar = $db->query($consulta);
        $consumo = $ejecutar->fetchAll(PDO::FETCH_OBJ);
        $db = null;

        //Exportar y mostrar en formato JSON
        $mensaje = array(
            'status' => true,
            'mensaje' => 'Clientes cargados',
            'rest' => $consumo
        );
        return json_encode($mensaje);
    } catch(PDOException $e){
        $mensaje = array(
            'status' => false,
            'mensaje' => 'Clientes no cargados',
            'error' => $e->getMessage()
        );
        return json_encode($mensaje);
    }
});
$app->post('/api/consumo_cliente/new', function(Request $request, Response $response){
    $consumo = json_decode($request->getParam('consumo'));
    $token = $request->getParam('token');
    //  Verificacion del token
    // $auth = new Auth();
    // $token = $auth->Check($token);
    // if($token['status'] === false){
    //     echo json_encode($token);
    //     return;
    // }
    // Fin Verificacion
    $fecha = date($consumo->fecha_inicial);
    $fecha_pago = sumar_periodo($fecha,$consumo->periodicidad_pago);
    $sql = "INSERT INTO `consumo-cliente` (idcliente,fecha_inicial,importe_consumo,periodicidad_pago,fecha_pago,descripcion,idusuario) 
    VALUES 
    (:idcliente,:fecha_inicial,:importe_consumo,:periodicidad_pago,:fecha_pago,:descripcion,:idusuario)";
    try{
        // Get DB Object
        $db = new db();
        // Connect
        $db = $db->connect();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':idcliente', $consumo->idcliente);
        $stmt->bindParam(':fecha_inicial', $consumo->fecha_inicial);
        $stmt->bindParam(':importe_consumo', $consumo->importe_consumo);
        $stmt->bindParam(':periodicidad_pago', $consumo->periodicidad_pago);
        $stmt->bindParam(':fecha_pago', $fecha_pago);
        $stmt->bindParam(':descripcion', $consumo->descripcion);
        $stmt->bindParam(':idusuario', $consumo->idusuario);
        $stmt->execute();
        $mensaje = array(
            'status' => true,
            'mensaje' => 'Consumo creado',
            'rest' => ''
        );
        echo json_encode($mensaje);
        return;

    } catch(PDOException $e){
        $mensaje = array(
            'status' => false,
            'mensaje' => 'Consumo no creado',
            'error' => $e->getMessage()
        );
        echo json_encode($mensaje);
        return;
    }
});
$app->put('/api/consumo_cliente/update', function(Request $request, Response $response){
    $consumo = json_decode($request->getParam('consumo'));
    //  Verificacion del token
    // $auth = new Auth();
    // $token = $auth->Check($cliente->token);
    // if($token['status'] === false){
    //     echo json_encode($token);
    //     return;
    // }
    // Fin Verificacion
    $fecha = date($consumo->fecha_inicial);
    $fecha_pago = sumar_periodo($fecha,$consumo->periodicidad_pago);
    $sql = "UPDATE `consumo-cliente` SET
                idcliente   = :idcliente,
                fecha_inicial   = :fecha_inicial,
                importe_consumo     = :importe_consumo,
                periodicidad_pago       = :periodicidad_pago,
                fecha_pago       = :fecha_pago,
                descripcion      = :descripcion
            WHERE idconsumo_cliente = $consumo->idconsumo_cliente";
    try{
        // Get DB Object
        $db = new db();
        // Connect
        $db = $db->connect();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':idcliente', $consumo->idcliente);
        $stmt->bindParam(':fecha_inicial', $consumo->fecha_inicial);
        $stmt->bindParam(':importe_consumo', $consumo->importe_consumo);
        $stmt->bindParam(':periodicidad_pago', $consumo->periodicidad_pago);
        $stmt->bindParam(':fecha_pago', $fecha_pago);
        $stmt->bindParam(':descripcion', $consumo->descripcion);
        $stmt->execute();
        $mensaje = array(
            'status' => true,
            'mensaje' => 'Consumo actualizado',
            'rest' => ''
        );
        echo json_encode($mensaje);
        return;

    } catch(PDOException $e){
        $mensaje = array(
            'status' => false,
            'mensaje' => 'Consumo no actualizado',
            'error' => $e->getMessage()
        );
        echo json_encode($mensaje);
        return;
    }
});
$app->delete('/api/consumo_cliente/delet', function(Request $request, Response $response){
    $consumo = json_decode($request->getParam('consumo'));
    //  Verificacion del token
    // $auth = new Auth();
    // $token = $auth->Check($cliente->token);
    // if($token['status'] === false){
    //     echo json_encode($token);
    //     return;
    // }
    // Fin Verificacion
    $sql = "DELETE  FROM `consumo-cliente` WHERE idcliente = '$consumo->idcliente'";
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
            'mensaje' => 'Consumo eliminado satisfactoriamente',
            'rest' => ''
        );
        echo json_encode($mensaje);
    } catch(PDOException $e){
        $mensaje = array(
            'status' => false,
            'mensaje' => 'Error al eliminar consumo',
            'error' => $e->getMessage()
        );
        echo json_encode($mensaje);
    }
});