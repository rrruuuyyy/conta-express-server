<?php
header("Content-Type: text/html;charset=utf-8");
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


$app->post('/api/usuarios/new', function(Request $request, Response $response){
    $nombre = $request->getParam('nombre');
    $apellidos = $request->getParam('apellidos');
    $correo = $request->getParam('email');
    $password = $request->getParam('password');
    $rfc = $request->getParam('rfc');
    $password = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO usuario (usuario,correo,password,nombre,apellidos) VALUES (:usuario,:correo,:password,:nombre
    ,:apellidos)";
    try{
        // Get DB Object
        $db = new db();
        // Connect
        $db = $db->connect();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':usuario', $rfc);
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':password', $password);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':apellidos', $apellidos);
        $stmt->execute();
        $mensaje = array(
            'status' => true,
            'mensaje' => 'Usuario creado',
            'rest' => ''
        );
        echo json_encode($mensaje);
        return;

    } catch(PDOException $e){
        $mensaje = array(
            'status' => false,
            'mensaje' => 'Usuario no creado',
            'error' => $e->getMessage()
        );
        echo json_encode($mensaje);
        return;
    }
});
$app->post('/api/usuarios/access', function(Request $request, Response $response){
    $correo = $request->getParam('email');
    $pass = $request->getParam('password');
    $sql = "SELECT * FROM usuario WHERE correo = '$correo'";

    try{
        // Get DB Object
        $db = new db();
        // Connect
        $db = $db->connect();
        $stmt = $db->query($sql);
        $usuario = $stmt->fetch(PDO::FETCH_OBJ);
        $db = null;
        //Desencriptar contraseña y validar si es correcta
        if ( password_verify($pass, $usuario->password) ) {
            $auth = new Auth();
            $tokenPHP = $auth->SignIn($usuario);
            $usuario->password = ":)";
            $respuesta = [
                "status" => true,
                "user" => $usuario,
                "token" => $tokenPHP
                // "tokenFIREBASECUSTOME" => $tokenFIREBASE->idToken,  
                // "tokenFIREBASE" => $tokenFIREBASECUSTOME,  
            ];
            echo json_encode($respuesta,JSON_UNESCAPED_UNICODE);
           
        } else {
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Usuario o contraseña incorrecta',
                'error' => 'acceso denegado' 
            );
            return json_encode($mensaje);
        }
    } catch(PDOException $e){
        $mensaje = array(
            'status' => false,
            'mensaje' => 'Error al conectar a la base de datos',
            'error' => $e->getMessage() 
        );
        return json_encode($mensaje);
    }
    // $mensaje = array(
    //     'status' => true,
    //     'mensaje' => 'Clientes cargados',
    //     'rest' => $usuario
    // );
    // return json_encode($mensaje);
});
