<?php
    use \Psr\Http\Message\ServerRequestInterface as Request;
    use \Psr\Http\Message\ResponseInterface as Response;
    use SWServices\Authentication\AuthenticationService as Authentication;

    $app->post('/api/notificaciones/pendientes_cancelacion', function(Request $request, Response $response){
        $idcliente = $request->getParam('idcliente');
        $rfc = $request->getParam('rfc');
        $barer = '';
        $params = array( 
            "url"=>"http://services.test.sw.com.mx" ,
            "user"=>"demo",
            "password"=> "123456789",
        );
        try{
            header('Content-type: application/json');
            $auth = Authentication::auth($params);
            $token = $auth::Token();
            $barer = $token->data->token;
            $opciones = array('http' =>
                array(
                    'protocol_version' => 1.1,
                    'method'  => 'GET',
                    'header'  => [                    
                        "Content-Type: application/json",
                        "authorization: bearer {$barer}",
                        "Cache-Control: no-cache"
                    ]
                )
            );
            $data = file_get_contents("https://services.sw.com.mx/pendings/{$rfc}", null, stream_context_create($opciones));
            $data = json_decode( $data );
            $mensaje = array(
                'status' => true,
                'mensaje' => 'Pendientes de cancelacion cargados',
                'rest' => $data
            );
            echo json_encode($mensaje);
            return;
        }
        catch(Exception $e){
            header('Content-type: text/plain');
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
        // try {
        //     $consultaPendientes = cancelationService::Set($params);
        //     $result= cancelationService::PendientesPorCancelar($rfc);
        //     $respuesta = (object)[];
        //     //Para Obtener el codStatus
        //     $respuesta->codigo = $result->codStatus;
        //     //Para obtener el array con los UUIDs
        //     $respuesta->uuids = $result->data->uuid;
        //     echo json_encode($respuesta);
        //     return;
        
        //     //     //En caso de error, se pueden visualizar los campos message y/o messageDetail
        //     // $result->message;
        //     // $result->messageDetail;
        // } catch(Exception $e) { //en caso de obtener una excepción
        //     echo json_encode( $e->getMessage() );
        // }
        
    });