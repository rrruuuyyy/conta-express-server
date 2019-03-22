<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->get('/api/recibos/get', function(Request $request, Response $response){
	$idusuario = $request->getParam('idusuario');
    $token = $request->getParam('token');
    $sql = "SELECT * FROM cliente WHERE idusuario='{$idusuario}' ";
    try{
        // Instanciar la base de datos
        $db = new db();

        // Conexión
        $db = $db->connect();
        $ejecutar = $db->query($sql);
        $clientes = $ejecutar->fetchAll(PDO::FETCH_OBJ);
        $db = null;

    } catch(PDOException $e){
        $mensaje = array(
            'status' => false,
            'mensaje' => 'Error al cargar clientes',
            'error' => $e->getMessage()
        );
        echo json_encode($mensaje);
        return;
    }
    $sql = "SELECT * FROM recibos WHERE idusuario='{$idusuario}' ";
    try{
        // Instanciar la base de datos
        $db = new db();

        // Conexión
        $db = $db->connect();
        $ejecutar = $db->query($sql);
        $recibos = $ejecutar->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        for ($i=0; $i < count($recibos) ; $i++) {
            for ($j=0; $j < count($clientes) ; $j++) {
                if($recibos[$i]->idcliente === $clientes[$j]->idcliente){
                    $recibos[$i]->cliente = $clientes[$j];
                }
            }
        }
        $mensaje = array(
            'status' => true,
            'mensaje' => 'Recibos cargados',
            'rest' => $recibos
        );
        echo json_encode($mensaje);
        return;
    } catch(PDOException $e){
        $mensaje = array(
            'status' => false,
            'mensaje' => 'Error al cargar recibos',
            'error' => $e->getMessage()
        );
        echo json_encode($mensaje);
        return;
    }
});
$app->post('/api/recibo/descargar', function(Request $request, Response $response){
	$recibo = json_decode($request->getParam('recibo'));
});