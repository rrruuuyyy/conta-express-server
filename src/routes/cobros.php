<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
$app->get('/api/cobros/get', function(Request $request, Response $response){
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
    $sql = "SELECT * FROM cobros WHERE idusuario='{$idusuario}' ";
    try{
        // Instanciar la base de datos
        $db = new db();

        // Conexión
        $db = $db->connect();
        $ejecutar = $db->query($sql);
        $cobros = $ejecutar->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        for ($i=0; $i < count($cobros) ; $i++) {
            for ($j=0; $j < count($clientes) ; $j++) {
                if($cobros[$i]->idcliente === $clientes[$j]->idcliente){
                    $cobros[$i]->cliente = $clientes[$j];
                }
            }
        }
        $mensaje = array(
            'status' => true,
            'mensaje' => 'Cobros cargados',
            'rest' => $cobros
        );
        echo json_encode($mensaje);
        return;
    } catch(PDOException $e){
        $mensaje = array(
            'status' => false,
            'mensaje' => 'Error al cargar cobros',
            'error' => $e->getMessage()
        );
        echo json_encode($mensaje);
        return;
    }
});
$app->post('/api/cobros/enviar_correo', function(Request $request, Response $response){
    $correo = json_decode($request->getParam('correo'));
    try {
        // ENVIAMOS EL XML Y PDF POR CORREO AL RECEPTOR DE LA FACTURA
        $mail = new PHPMailer();
        $mail->isSMTP();
        //$mail->SMTPDebug = 2;
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPSecure = 'ssl';                            
        $mail->Port = 465; 
        $mail->SMTPAuth = true;
        $mail->Username = 'nosolocodigo@gmail.com';
        $mail->Password = 'Quiencomoel1';
        $mail->setFrom('sanmarig@hotmail.com','Despacho contable SanMarig');
        $mail->addAddress($correo->correo);
        $mail->Subject = $correo->mensaje;
        $mail->Body = $correo->mensaje;
        $mail->addAttachment("docs/{$correo->archivo}.pdf","Comprobante.pdf");
        $mail->send();
        $mensaje = array(
            'status' => true,
            'mensaje' => 'Correo enviado',
            'rest' => ''
        );
        echo json_encode($mensaje);
        return;
    } catch (Exception $e) {
        $mensaje = array(
            'status' => true,
            'mensaje' => 'Correo enviado',
            'error' => $mail->ErrorInfo
        );
        echo json_encode($mensaje);
        return;
    }
});