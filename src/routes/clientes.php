<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
//Obtener todos los clientes
$app->get('/api/clientes/get', function(Request $request, Response $response){
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
    $consulta = "SELECT * FROM cliente WHERE idusuario='$idusuario'";
    try{
        // Instanciar la base de datos
        $db = new db();

        // Conexión
        $db = $db->connect();
        $ejecutar = $db->query($consulta);
        $clientes = $ejecutar->fetchAll(PDO::FETCH_OBJ);
        $db = null;

        //Exportar y mostrar en formato JSON
        $mensaje = array(
            'status' => true,
            'mensaje' => 'Clientes cargados',
            'rest' => $clientes
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
    // Guardar un cliente
$app->post('/api/clientes/new', function(Request $request, Response $response){
    $cliente = json_decode($request->getParam('cliente'));
    //  Verificacion del token
    // $auth = new Auth();
    // $token = $auth->Check($cliente->token);
    // if($token['status'] === false){
    //     echo json_encode($token);
    //     return;
    // }
    // Fin Verificacion
    $sql = "INSERT INTO cliente (idusuario,nombre,correo,rfc,calle,colonia,cp,estado,pais,persona,regimen,ejercicio,deduccion,declaracion) 
    VALUES (:idusuario,:nombre,:correo,:rfc,:calle,:colonia,:cp,:estado,:pais,:persona,:regimen,:ejercicio,:deduccion,:declaracion)";
    try{
        // Get DB Object
        $db = new db();
        // Connect
        $db = $db->connect();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':idusuario', $cliente->idusuario);
        $stmt->bindParam(':nombre', $cliente->nombre);
        $stmt->bindParam(':correo', $cliente->correo);
        $stmt->bindParam(':rfc', $cliente->rfc);
        $stmt->bindParam(':calle', $cliente->calle);
        $stmt->bindParam(':colonia', $cliente->colonia);
        $stmt->bindParam(':cp', $cliente->cp);
        $stmt->bindParam(':estado', $cliente->estado);
        $stmt->bindParam(':pais', $cliente->pais);
        $stmt->bindParam(':persona', $cliente->persona);
        $stmt->bindParam(':regimen', $cliente->regimen);
        $stmt->bindParam(':ejercicio', $cliente->regimen);
        $stmt->bindParam(':deduccion', $cliente->deduccion);
        $stmt->bindParam(':declaracion', $cliente->declaracion);
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
});
$app->put('/api/clientes/update', function(Request $request, Response $response){
    $cliente = json_decode($request->getParam('cliente'));
    //  Verificacion del token
    // $auth = new Auth();
    // $token = $auth->Check($cliente->token);
    // if($token['status'] === false){
    //     echo json_encode($token);
    //     return;
    // }
    // Fin Verificacion
    $sql = "UPDATE cliente SET
                idusuario = :idusuario,
                nombre   = :nombre,
                correo   = :correo,
                rfc     = :rfc,
                calle       = :calle,
                colonia       = :colonia,
                cp      = :cp,
                estado      = :estado,
                pais      = :pais,                
                persona     = :persona,
                regimen     = :regimen,
                ejercicio     = :ejercicio,
                deduccion     = :deduccion,
                declaracion     = :declaracion
            WHERE idcliente = $cliente->idcliente";
    try{
        // Get DB Object
        $db = new db();
        // Connect
        $db = $db->connect();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':idusuario', $cliente->idusuario);
        $stmt->bindParam(':nombre', $cliente->nombre);
        $stmt->bindParam(':correo', $cliente->correo);
        $stmt->bindParam(':rfc', $cliente->rfc);        
        $stmt->bindParam(':calle', $cliente->calle);
        $stmt->bindParam(':colonia', $cliente->colonia);
        $stmt->bindParam(':cp', $cliente->cp);
        $stmt->bindParam(':estado', $cliente->estado);
        $stmt->bindParam(':pais', $cliente->pais);
        $stmt->bindParam(':persona', $cliente->persona);
        $stmt->bindParam(':regimen', $cliente->regimen);
        $stmt->bindParam(':ejercicio', $cliente->ejercicio);
        $stmt->bindParam(':deduccion', $cliente->deduccion);
        $stmt->bindParam(':declaracion', $cliente->declaracion);
        $stmt->execute();
        $mensaje = array(
            'status' => true,
            'mensaje' => 'Cliente actualizado',
            'rest' => ''
        );
        echo json_encode($mensaje);
        return;

    } catch(PDOException $e){
        $mensaje = array(
            'status' => false,
            'mensaje' => 'Cliente no actualizado',
            'error' => $e->getMessage()
        );
        echo json_encode($mensaje);
        return;
    }
});
$app->delete('/api/clientes/delete', function(Request $request, Response $response){
    $cliente = json_decode($request->getParam('cliente'));
    //  Verificacion del token
    // $auth = new Auth();
    // $token = $auth->Check($cliente->token);
    // if($token['status'] === false){
    //     echo json_encode($token);
    //     return;
    // }
    // Fin Verificacion
    $sql = "DELETE FROM cliente WHERE idcliente = '$cliente->idcliente'";
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
            'mensaje' => 'Cliente eliminado satisfactoriamente',
            'rest' => ''
        );
        echo json_encode($mensaje);
    } catch(PDOException $e){
        $mensaje = array(
            'status' => false,
            'mensaje' => 'Error al eliminar cliente',
            'error' => $e->getMessage()
        );
        echo json_encode($mensaje);
    }
});