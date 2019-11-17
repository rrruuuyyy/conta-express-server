<?php
    use \Psr\Http\Message\ServerRequestInterface as Request;
    use \Psr\Http\Message\ResponseInterface as Response;
    //Obtener todos los clientes
    $app->post('/api/certificados/info', function(Request $request, Response $response){
        $idcliente = $request->getParam('idcliente');
        $token = $request->getParam('token');
        $info_fiel = null;
        $info_csd = null;
        $sql_cert = "SELECT * FROM certificado_fiel_cliente WHERE idcliente = '$idcliente'";
        try{
            // Instanciar la base de datos
            $db = new db();
            // Conexión
            $db = $db->connect();
            $ejecutar = $db->query($sql_cert);
            $info_fiel = $ejecutar->fetch(PDO::FETCH_OBJ);
            $db = null;
            if(!$info_fiel){
                $info_fiel = null;
            }
        } catch(PDOException $e){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Error al obtener info de certificados fiel',
                'error' => $e->getMessage()
            );
            return json_encode($mensaje);
        }
        $sql_cert = "SELECT * FROM certificado_csd_cliente WHERE idcliente = '$idcliente'";
        try{
            // Instanciar la base de datos
            $db = new db();
            // Conexión
            $db = $db->connect();
            $ejecutar = $db->query($sql_cert);
            $info_csd = $ejecutar->fetch(PDO::FETCH_OBJ);
            if(!$info_csd){
                $info_csd = null;
            }
            $db = null;
        } catch(PDOException $e){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Error al obtener info de certificados fiel',
                'error' => $e->getMessage()
            );
            return json_encode($mensaje);
        }
        $respuesta  = (object)[];
        $respuesta->info_fiel = $info_fiel;
        $respuesta->info_csd = $info_csd;
        // Enviamos respuesta cuando todo se cumpla
        $mensaje = array(
            'status' => true,
            'mensaje' => 'Info cargada',
            'rest' => $respuesta
        );
        echo json_encode($mensaje);
    });