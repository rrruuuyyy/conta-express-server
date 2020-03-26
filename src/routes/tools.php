<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
// VERIFICAR SI EL TOKEN NO HA EXPIRADO O ES INCORRECTO
$app->get('/api/tools/token', function(Request $request, Response $response){
    $token = $request->getParam('token');
    if($token === 'undefined'){
        $mensaje = array(
            'status' => false,
            'mensaje' => 'El token es invalido'
        );
        return json_encode($mensaje);
    }
    //  Verificacion del token
    $auth = new Auth();
    $token = $auth->Check($token);
    if($token['status'] === false){
        $mensaje = array(
            'status' => false,
            'mensaje' => 'El token es invalido'
        );
        return json_encode($mensaje);
    }else{
        $mensaje = array(
            'status' => true,
            'mensaje' => 'El token es valido'
        );
        return json_encode($mensaje);
    }
});