<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
$app->get('/api/sub_usuarios/get', function(Request $request, Response $response){
    $idusuario = $request->getParam('idusuario');
    $token = $request->getParam('token');
    $sql = "SELECT * FROM sub_usuario WHERE idusuario='{$idusuario}' ";
    try{
        // Instanciar la base de datos
        $db = new db();

        // Conexión
        $db = $db->connect();
        $ejecutar = $db->query($sql);
        $sub_usuarios = $ejecutar->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        $mensaje = array(
            'status' => true,
            'mensaje' => 'Cobros cargados',
            'rest' => $sub_usuarios
        );
        echo json_encode($mensaje);
        return;

    } catch(PDOException $e){
        $mensaje = array(
            'status' => false,
            'mensaje' => 'Error al cargar sub usuarios',
            'error' => $e->getMessage()
        );
        echo json_encode($mensaje);
        return;
    }
});
$app->post('/api/sub_usuarios/new', function(Request $request, Response $response){
    $sub_usuario = json_decode($request->getParam('sub_usuario'));
    // Validacion del token
    $sql = "INSERT INTO sub_usuario 
    (idusuario,rol,correo,password,nombre)
    VALUES 
    (:idusuario,:rol,:correo,:password,:nombre)";
    try{
        // Get DB Object
        $db = new db();
        // Connect
        $db = $db->connect();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':idusuario', $sub_usuario->idusuario);
        $stmt->bindParam(':rol', $sub_usuario->rol);
        $stmt->bindParam(':correo', $sub_usuario->correo);
        $stmt->bindParam(':password', $sub_usuario->password2);
        $stmt->bindParam(':nombre', $sub_usuario->nombre);
        $stmt->execute();
        $mensaje = array(
            'status' => true,
            'mensaje' => 'Sub usuario creado',
            'rest' => ''
        );
        echo json_encode($mensaje);
        return;

    } catch(PDOException $e){
        $mensaje = array(
            'status' => false,
            'mensaje' => 'Sub usuario no creado',
            'error' => $e->getMessage()
        );
        echo json_encode($mensaje);
        return;
    }
});
$app->put('/api/sub_usuarios/update', function(Request $request, Response $response){
    $sub_usuario = json_decode($request->getParam('sub_usuario'));
    // Validacion del token
    $sql = "UPDATE sub_usuario SET
                idusuario   = :idusuario,
                rol   = :rol,
                correo   = :correo,
                password       = :password,
                nombre       = :nombre
            WHERE idsub_usuario = $sub_usuario->idsub_usuario";
    try{
        // Get DB Object
        $db = new db();
        // Connect
        $db = $db->connect();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':idusuario', $sub_usuario->idusuario);
        $stmt->bindParam(':rol', $sub_usuario->rol);
        $stmt->bindParam(':correo', $sub_usuario->correo);
        $stmt->bindParam(':password', $sub_usuario->password2);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->execute();
        $mensaje = array(
            'status' => true,
            'mensaje' => 'Sub usuario actualizado',
            'rest' => ''
        );
        echo json_encode($mensaje);
        return;

    } catch(PDOException $e){
        $mensaje = array(
            'status' => false,
            'mensaje' => 'Sub usuario no actualizado',
            'error' => $e->getMessage()
        );
        echo json_encode($mensaje);
        return;
    }
});
$app->delete('/api/sub_usuarios/eliminar', function(Request $request, Response $response){
    $idsub_usuario = $request->getParam('idsub_usuario');
    $token = $request->getParam('token');
    $sql = "DELETE FROM sub_usuario WHERE idsub_usuario = '$idsub_usuario'";
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
            'mensaje' => 'Error al eliminar sub_usuario',
            'error' => $e->getMessage()
        );
        echo json_encode($mensaje);
    }
    $mensaje = array(
        'status' => true,
        'mensaje' => 'Sub-usuario eliminado satisfactoriamente',
        'rest' => ''
    );
    echo json_encode($mensaje);
});