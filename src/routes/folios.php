<?php
header("Content-Type: text/html;charset=utf-8");
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->get('/api/folios/get', function(Request $request, Response $response){
	$idusuario = $request->getParam('idusuario');
    $token = $request->getParam('token');
    //  Verificacion del token
    // $auth = new Auth();
    // $token = $auth->Check($token);
    // if($token['status'] === false){
    //     echo json_encode($token);
    //     return;
    // }
    // Fin Verificacion
    $sql = "SELECT * FROM folios WHERE idusuario = '$idusuario'";
    try{
        // Instanciar la base de datos
        $db = new db();

        // Conexión
        $db = $db->connect();
        $ejecutar = $db->query($sql);
        $consumo = $ejecutar->fetchAll(PDO::FETCH_OBJ);
        $db = null;

        //Exportar y mostrar en formato JSON
        $mensaje = array(
            'status' => true,
            'mensaje' => 'folios cargados',
            'rest' => $consumo
        );
        return json_encode($mensaje);
    } catch(PDOException $e){
        $mensaje = array(
            'status' => false,
            'mensaje' => 'folios no cargados',
            'error' => $e->getMessage()
        );
        return json_encode($mensaje);
    }
});
$app->post('/api/folios/new', function(Request $request, Response $response){
	$folios = json_decode($request->getParam('folios'));
    //  Verificacion del token
    // $auth = new Auth();
    // $token = $auth->Check($token);
    // if($token['status'] === false){
    //     echo json_encode($token);
    //     return;
    // }
    // Fin Verificacion
    $sql = "INSERT INTO folios (idusuario, serie, folio) VALUES 
    (:idusuario, :serie, :folio)";
    try{
        // Get DB Object
        $db = new db();
        // Connect
        $db = $db->connect();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':idusuario', $folios->idusuario);
        $stmt->bindParam(':serie', $folios->serie);
        $stmt->bindParam(':folio', $folios->folio);
        $stmt->execute();
        $mensaje = array(
            'status' => true,
            'mensaje' => 'Folios guardados',
            'rest' => ''
        );
        echo json_encode($mensaje);
        return;

    } catch(PDOException $e){
        $mensaje = array(
            'status' => false,
            'mensaje' => 'folios no creados',
            'error' => $e->getMessage()
        );
        echo json_encode($mensaje);
        return;
    }
});
$app->post('/api/folios/update', function(Request $request, Response $response){
	$folios = json_decode($request->getParam('folios'));
    //  Verificacion del token
    // $auth = new Auth();
    // $token = $auth->Check($token);
    // if($token['status'] === false){
    //     echo json_encode($token);
    //     return;
    // }
    // Fin Verificacion
    $sql = "UPDATE `folios` SET
                serie   = :serie,
                folio   = :folio
            WHERE idusuario = $folios->idusuario";
    try{
        // Get DB Object
        $db = new db();
        // Connect
        $db = $db->connect();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':serie', $folios->serie);
        $stmt->bindParam(':folio', $folios->folio);
        $stmt->execute();
        $mensaje = array(
            'status' => true,
            'mensaje' => 'Folio actualizado',
            'rest' => ''
        );
        echo json_encode($mensaje);
        return;

    } catch(PDOException $e){
        $mensaje = array(
            'status' => false,
            'mensaje' => 'Folio no actualizado',
            'error' => $e->getMessage()
        );
        echo json_encode($mensaje);
        return;
    }
});
$app->delete('/api/folios/delet', function(Request $request, Response $response){
    	$idusuario = $request->getParam('idusuario');
    	$token = $request->getParam('token');
    	$sql = "DELETE  FROM `folios` WHERE idcliente = '$idcliente'";
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