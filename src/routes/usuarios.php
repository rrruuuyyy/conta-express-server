<?php
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
$app->put('/api/usuarios/update', function(Request $request, Response $response){
    $usuario = json_decode($request->getParam('usuario'));
    // Validacion del token
    $sql = "UPDATE usuario SET
                nombre   = :nombre,
                apellidos   = :apellidos,
                correo   = :correo,
                calle       = :calle,
                colonia       = :colonia,
                cp      = :cp,
                estado      = :estado,
                pais      = :pais
            WHERE idusuario = $usuario->idusuario";
    try{
        // Get DB Object
        $db = new db();
        // Connect
        $db = $db->connect();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':nombre', $usuario->nombre);
        $stmt->bindParam(':apellidos', $usuario->apellidos);
        $stmt->bindParam(':correo', $usuario->correo);
        $stmt->bindParam(':calle', $usuario->calle);
        $stmt->bindParam(':colonia', $usuario->colonia);
        $stmt->bindParam(':cp', $usuario->cp);
        $stmt->bindParam(':estado', $usuario->estado);
        $stmt->bindParam(':pais', $usuario->pais);
        $stmt->execute();
        $mensaje = array(
            'status' => true,
            'mensaje' => 'Usuario actualizado',
            'rest' => ''
        );
        echo json_encode($mensaje);
        return;

    } catch(PDOException $e){
        $mensaje = array(
            'status' => false,
            'mensaje' => 'Usuario no actualizado',
            'error' => $e->getMessage()
        );
        echo json_encode($mensaje);
        return;
    }
});
$app->post('/api/usuarios/access', function(Request $request, Response $response){
    $correo = $request->getParam('correo');
    $password = $request->getParam('password');
    $sql = "SELECT * FROM usuario WHERE correo = '$correo'";
    $sql2 = "SELECT * FROM sub_usuario WHERE correo = '$correo'";
    try{
        // Get DB Object
        $db = new db();
        // Connect
        $db = $db->connect();
        $stmt = $db->query($sql);
        $usuario = $stmt->fetch(PDO::FETCH_OBJ);
        $db = null;
        if($usuario === false){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Usuario o contraseña incorrecta',
                'error' => 'acceso denegado' 
            );
            echo json_encode($mensaje);
            return;
        }
        //Desencriptar contraseña y validar si es correcta
        if ( password_verify($password, $usuario->password) ) {
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
$app->get('/api/usuarios/get', function(Request $request, Response $response){
    $idusuario = $request->getParam('idusuario');
    $token = $request->getParam('token');
    $sql ="SELECT * FROM usuario WHERE idusuario = '$idusuario'";
    try{
        // Get DB Object
        $db = new db();
        // Connect
        $db = $db->connect();
        $stmt = $db->query($sql);
        $usuario = $stmt->fetch(PDO::FETCH_OBJ);
        $db = null;
        $usuario->password = ":)";
        $mensaje = array(
            'status' => true,
            'mensaje' => 'Usuario cargado',
            'rest' => $usuario 
        );
        return json_encode($mensaje);

    } catch(PDOException $e){
        $mensaje = array(
            'status' => false,
            'mensaje' => 'Error al cargar usuario',
            'error' => $e->getMessage() 
        );
        return json_encode($mensaje);
    }
});
$app->post('/api/usuarios/pass_change', function(Request $request, Response $response){
    $pass = $request->getParam('pass');
    $idusuario = $request->getParam('idusuario');
    $token = $request->getParam('token');
    $password = password_hash($pass, PASSWORD_DEFAULT);
    $sql = "UPDATE usuario SET
                password   = :password
            WHERE idusuario = '$idusuario'";
    try{
        // Get DB Object
        $db = new db();
        // Connect
        $db = $db->connect();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':password', $password);
        $stmt->execute();
        $mensaje = array(
            'status' => true,
            'mensaje' => 'Contraseña actualizada',
            'rest' => ''
        );
        echo json_encode($mensaje);
        return;

    } catch(PDOException $e){
        $mensaje = array(
            'status' => false,
            'mensaje' => 'Contraseña no actualizada',
            'error' => $e->getMessage()
        );
        echo json_encode($mensaje);
        return;
    }
});
