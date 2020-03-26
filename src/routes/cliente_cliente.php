<?php
    use \Psr\Http\Message\ServerRequestInterface as Request;
    use \Psr\Http\Message\ResponseInterface as Response;
    //Obtener todos los clientes
    $app->post('/api/clientes_cliente/get', function(Request $request, Response $response){
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
        $consulta = "SELECT * FROM cliente_cliente WHERE idcliente='$idcliente'";
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
                'mensaje' => 'Clientes cargados correctamente',
                'rest' => $clientes
            );
            return json_encode($mensaje);
        } catch(PDOException $e){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Error al cargar clientes',
                'error' => $e->getMessage()
            );
            return json_encode($mensaje);
        }
    });