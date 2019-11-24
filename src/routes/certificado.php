<?php
    error_reporting(-1);
    // require '../vendor/autoload.php';
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
    $app->post('/api/certificados/new_csd', function(Request $request, Response $response){
        $data = $_POST;
        $archivo_cer = $_FILES['cer'];
        $archivo_key = $_FILES['key'];
        $pass = $data['pass'];
        $idcliente = $data['idcliente'];
        $cliente = '';
        $openssl = new \CfdiUtils\OpenSSL\OpenSSL();
        $archivos_nuevos = false;
        $sql_cliente = "SELECT rfc FROM cliente WHERE idcliente = '$idcliente'";
        try{
            // Instanciar la base de datos
            $db = new db();    
            // Conexión
            $db = $db->connect();
            $ejecutar = $db->query($sql_cliente);
            $cliente = $ejecutar->fetch(PDO::FETCH_OBJ);
            $db = null;
        } catch(PDOException $e){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Error al cargar rfc del cliente',
                'error' => $e->getMessage()
            );
            return json_encode($mensaje);
        }
        $path = "./docs/$cliente->rfc";
        if( !file_exists($path)){
            mkdir($path,0777);
        }
        //Buscamos si el archivo cer existe
        if( file_exists( $path. "/{$cliente->rfc}CSD.cer.pem" ) ){
            unlink( $path. "/{$cliente->rfc}CSD.cer.pem" );
            $archivos_nuevos = true;
            //Decodificamos los archivo .cer a formato pem
            $openssl->derCerConvert( $archivo_cer['tmp_name'], $path."/{$cliente->rfc}CSD.cer.pem" );
        }else{
            $openssl->derCerConvert( $archivo_cer['tmp_name'], $path."/{$cliente->rfc}CSD.cer.pem" );
        }
        if( file_exists( $path. "/{$cliente->rfc}CSD.cer" ) ){
            unlink( $path. "/{$cliente->rfc}CSD.cer" );
            copy($archivo_cer['tmp_name'], $path."/{$cliente->rfc}CSD.cer");
        }else{
            copy($archivo_cer['tmp_name'], $path."/{$cliente->rfc}CSD.cer");
        }
        //Buscamos si el archivo key existe
        
        if( file_exists( $path. "/{$cliente->rfc}CSD.key.pem" ) ){
            unlink( $path. "/{$cliente->rfc}CSD.key.pem" );
            //Decodificamos los archivo .key a formato pem
            try {
                //code...
                $openssl->derKeyConvert($archivo_key['tmp_name'], $pass, $path."/{$cliente->rfc}CSD.key.pem");
            } catch (Exception  $e) {
                $mensaje = array(
                'status' => false,
                'mensaje' => 'La contraseña del archivo KEY es incorrecta',
                'error' => $e->getMessage()
                );
                return json_encode($mensaje);
            }
        }else{
            // $openssl->derKeyConvert($archivo_key['tmp_name'], $pass, $path."/{$cliente->rfc}CSD.key.pem");     
            try {
                //code...
                $openssl->derKeyConvert($archivo_key['tmp_name'], $pass, $path."/{$cliente->rfc}CSD.key.pem");
            } catch (Exception  $e) {
                $mensaje = array(
                'status' => false,
                'mensaje' => 'La contraseña del archivo KEY es incorrecta',
                'error' => $e->getMessage()
                );
                return json_encode($mensaje);
            }       
        }
        if( file_exists( $path. "/{$cliente->rfc}CSD.key" ) ){
            unlink( $path. "/{$cliente->rfc}CSD.key" );
            copy($archivo_key['tmp_name'], $path."/{$cliente->rfc}CSD.key");
        }else{
            copy($archivo_key['tmp_name'], $path."/{$cliente->rfc}CSD.key");
        }
        //Vamos a obtener informacion del certificado
        $certificate = new \CfdiUtils\Certificado\Certificado( $path."/{$cliente->rfc}CSD.cer" );
        $rfc_certificado = $certificate->getRfc();
        $num_serie = $certificate->getSerial();
        $vigencia = $certificate->getValidTo();
        $date = new DateTime();
        $vigencia = $date->setTimestamp($vigencia);
        $vigencia = $vigencia->format('Y-m-d');
        $nombre_cer = "{$cliente->rfc}CSD.cer.pem";
        $nombre_key = "{$cliente->rfc}CSD.key.pem";
        if( $rfc_certificado != $cliente->rfc ){
            $mensaje = array(
            'status' => false,
            'mensaje' => 'El rfc del certificado es diferente al del cliente',
            'error' => 'RFC invalid'
            );
            return json_encode($mensaje);
        }
        if( $archivos_nuevos === false ){
            $sql_cer = "INSERT INTO certificado_csd_cliente (nombre_archivo_key,nombre_archivo_cer,vigencia,idcliente) 
            VALUES (:nombre_archivo_key,:nombre_archivo_cer,:vigencia,:idcliente)";
            try{
                // Get DB Object
                $db = new db();
                // Connect
                $db = $db->connect();
                $stmt = $db->prepare($sql_cer);
                $stmt->bindParam(':idcliente', $idcliente);
                $stmt->bindParam(':nombre_archivo_cer', $nombre_cer);
                $stmt->bindParam(':nombre_archivo_key', $nombre_key);
                $stmt->bindParam(':vigencia', $vigencia);
                $stmt->execute();
                $mensaje = array(
                    'status' => true,
                    'mensaje' => 'Certificado CSD guardado correctamente',
                    'rest' => ''
                );
                echo json_encode($mensaje);
                return;
        
            } catch(PDOException $e){
                $mensaje = array(
                    'status' => false,
                    'mensaje' => 'Error al guardar CSD en base de datos',
                    'error' => $e->getMessage()
                );
                echo json_encode($mensaje);
                return;
            }
        }else{
            $sql = "UPDATE certificado_csd_cliente SET
                nombre_archivo_cer = :nombre_archivo_cer,
                nombre_archivo_key = :nombre_archivo_key,
                vigencia = :vigencia
            WHERE idcliente = $idcliente";
            try{
                // Get DB Object
                $db = new db();
                // Connect
                $db = $db->connect();
                $stmt = $db->prepare($sql);
                $stmt->bindParam(':nombre_archivo_cer', $nombre_cer);
                $stmt->bindParam(':nombre_archivo_key', $nombre_key);
                $stmt->bindParam(':vigencia', $vigencia);
                $stmt->execute();
                $mensaje = array(
                    'status' => true,
                    'mensaje' => 'Certificado CSD actualizado correctamente',
                    'rest' => ''
                );
                echo json_encode($mensaje);
                return;
            } catch(PDOException $e){
                $mensaje = array(
                    'status' => false,
                    'mensaje' => 'Error al actualizar el CSD',
                    'error' => $e->getMessage()
                );
                echo json_encode($mensaje);
                return;
            }
        }
    });
    $app->post('/api/certificados/new_fiel', function(Request $request, Response $response){
        $data = $_POST;
        $archivo_cer = $_FILES['cer'];
        $archivo_key = $_FILES['key'];
        $pass = $data['pass'];
        $idcliente = $data['idcliente'];
        $cliente = '';
        $openssl = new \CfdiUtils\OpenSSL\OpenSSL();
        $archivos_nuevos = false;
        $sql_cliente = "SELECT rfc FROM cliente WHERE idcliente = '$idcliente'";
        try{
            // Instanciar la base de datos
            $db = new db();    
            // Conexión
            $db = $db->connect();
            $ejecutar = $db->query($sql_cliente);
            $cliente = $ejecutar->fetch(PDO::FETCH_OBJ);
            $db = null;
        } catch(PDOException $e){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Error al cargar rfc del cliente',
                'error' => $e->getMessage()
            );
            return json_encode($mensaje);
        }
        $path = "./docs/$cliente->rfc";
        if( !file_exists($path)){
            mkdir($path,0777);
        }
        //Buscamos si el archivo cer existe
        if( file_exists( $path. "/{$cliente->rfc}FIEL.cer.pem" ) ){
            unlink( $path. "/{$cliente->rfc}FIEL.cer.pem" );
            $archivos_nuevos = true;
            //Decodificamos los archivo .cer a formato pem
            $openssl->derCerConvert( $archivo_cer['tmp_name'], $path."/{$cliente->rfc}FIEL.cer.pem" );
        }else{
            $openssl->derCerConvert( $archivo_cer['tmp_name'], $path."/{$cliente->rfc}FIEL.cer.pem" );
        }
        if( file_exists( $path. "/{$cliente->rfc}FIEL.cer" ) ){
            unlink( $path. "/{$cliente->rfc}FIEL.cer" );
            copy($archivo_cer['tmp_name'], $path."/{$cliente->rfc}FIEL.cer");
        }else{
            copy($archivo_cer['tmp_name'], $path."/{$cliente->rfc}FIEL.cer");
        }
        //Buscamos si el archivo key existe
        
        if( file_exists( $path. "/{$cliente->rfc}FIEL.key.pem" ) ){
            unlink( $path. "/{$cliente->rfc}FIEL.key.pem" );
            //Decodificamos los archivo .key a formato pem
            try {
                //code...
                $openssl->derKeyConvert($archivo_key['tmp_name'], $pass, $path."/{$cliente->rfc}FIEL.key.pem");
            } catch (Exception  $e) {
                $mensaje = array(
                'status' => false,
                'mensaje' => 'La contraseña del archivo KEY es incorrecta',
                'error' => $e->getMessage()
                );
                return json_encode($mensaje);
            }
        }else{   
            try {
                //code...
                $openssl->derKeyConvert($archivo_key['tmp_name'], $pass, $path."/{$cliente->rfc}FIEL.key.pem");
            } catch (Exception  $e) {
                $mensaje = array(
                'status' => false,
                'mensaje' => 'La contraseña del archivo KEY es incorrecta',
                'error' => $e->getMessage()
                );
                return json_encode($mensaje);
            }       
        }
        if( file_exists( $path. "/{$cliente->rfc}FIEL.key" ) ){
            unlink( $path. "/{$cliente->rfc}FIEL.key" );
            copy($archivo_key['tmp_name'], $path."/{$cliente->rfc}FIEL.key");
        }else{
            copy($archivo_key['tmp_name'], $path."/{$cliente->rfc}FIEL.key");
        }
        //Vamos a obtener informacion del certificado
        $certificate = new \CfdiUtils\Certificado\Certificado( $path."/{$cliente->rfc}FIEL.cer" );
        $rfc_certificado = $certificate->getRfc();
        $num_serie = $certificate->getSerial();
        $vigencia = $certificate->getValidTo();
        $date = new DateTime();
        $vigencia = $date->setTimestamp($vigencia);
        $vigencia = $vigencia->format('Y-m-d');
        $nombre_cer = "{$cliente->rfc}FIEL.cer.pem";
        $nombre_key = "{$cliente->rfc}FIEL.key.pem";
        if( $rfc_certificado != $cliente->rfc ){
            $mensaje = array(
            'status' => false,
            'mensaje' => 'El rfc del certificado es diferente al del cliente',
            'error' => 'RFC invalid'
            );
            return json_encode($mensaje);
        }
        if( $archivos_nuevos === false ){
            $sql_cer = "INSERT INTO certificado_fiel_cliente (nombre_archivo_key,nombre_archivo_cer,vigencia,idcliente) 
            VALUES (:nombre_archivo_key,:nombre_archivo_cer,:vigencia,:idcliente)";
            try{
                // Get DB Object
                $db = new db();
                // Connect
                $db = $db->connect();
                $stmt = $db->prepare($sql_cer);
                $stmt->bindParam(':idcliente', $idcliente);
                $stmt->bindParam(':nombre_archivo_cer', $nombre_cer);
                $stmt->bindParam(':nombre_archivo_key', $nombre_key);
                $stmt->bindParam(':vigencia', $vigencia);
                $stmt->execute();
                $mensaje = array(
                    'status' => true,
                    'mensaje' => 'Certificado FIEL guardado correctamente',
                    'rest' => ''
                );
                echo json_encode($mensaje);
                return;
        
            } catch(PDOException $e){
                $mensaje = array(
                    'status' => false,
                    'mensaje' => 'Error al guardar CSD en base de datos',
                    'error' => $e->getMessage()
                );
                echo json_encode($mensaje);
                return;
            }
        }else{
            $sql = "UPDATE certificado_csd_cliente SET
                nombre_archivo_cer = :nombre_archivo_cer,
                nombre_archivo_key = :nombre_archivo_key,
                vigencia = :vigencia
            WHERE idcliente = $idcliente";
            try{
                // Get DB Object
                $db = new db();
                // Connect
                $db = $db->connect();
                $stmt = $db->prepare($sql);
                $stmt->bindParam(':nombre_archivo_cer', $nombre_cer);
                $stmt->bindParam(':nombre_archivo_key', $nombre_key);
                $stmt->bindParam(':vigencia', $vigencia);
                $stmt->execute();
                $mensaje = array(
                    'status' => true,
                    'mensaje' => 'Certificado CSD actualizado correctamente',
                    'rest' => ''
                );
                echo json_encode($mensaje);
                return;
            } catch(PDOException $e){
                $mensaje = array(
                    'status' => false,
                    'mensaje' => 'Error al actualizar el CSD',
                    'error' => $e->getMessage()
                );
                echo json_encode($mensaje);
                return;
            }
        }
    });