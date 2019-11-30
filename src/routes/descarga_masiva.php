<?php
// Descarga masica SAT SW
    // require "./../SWInclude.php";
    use \Psr\Http\Message\ServerRequestInterface as Request;
    use \Psr\Http\Message\ResponseInterface as Response;
    use PhpCfdi\SatWsDescargaMasiva\PackageReader\CfdiPackageReader;
    use PhpCfdi\SatWsDescargaMasiva\PackageReader\MetadataPackageReader;
    use PhpCfdi\SatWsDescargaMasiva\Service;
    use PhpCfdi\SatWsDescargaMasiva\Services\Query\QueryParameters;
    use PhpCfdi\SatWsDescargaMasiva\Shared\DateTime;
    use PhpCfdi\SatWsDescargaMasiva\Shared\DateTimePeriod;
    use PhpCfdi\SatWsDescargaMasiva\Shared\DownloadType;
    use PhpCfdi\SatWsDescargaMasiva\Shared\Fiel;
    use PhpCfdi\SatWsDescargaMasiva\Shared\RequestType;
    use PhpCfdi\SatWsDescargaMasiva\WebClient\WebClientInterface;
    
    
    $app->post('/api/descarga_masiva/autenticacion', function(Request $request, Response $response){
        $idcliente = $request->getParam('idcliente');
        $token = $request->getParam('token');
        $cliente = null;
        $certificado = null;
        // Variables para la peticion
        $cert = '';
        $key = '';
        $rfc = 'LAN7008173R5';
        $fechaInicial = '2018-06-02T00:00:00';
        $fechaFinal = '2018-06-02T12:59:59';
        $TipoSolicitud = 'CFDI';
        $idSolicitud = '1fb832ff-6a25-4616-8ca8-04478690cc29';
        $idPaquete = '1fb832ff-6a25-4616-8ca8-04478690cc29_01';

        // Sentencia para obtener la direccion de la fiel
        $sql = "SELECT * FROM certificado_fiel_cliente WHERE idcliente = '$idcliente'";
        try{
            // Instanciar la base de datos
            $db = new db();
    
            // Conexión
            $db = $db->connect();
            $ejecutar = $db->query($sql);
            $certificado = $ejecutar->fetch(PDO::FETCH_OBJ);
            $db = null;
            if($certificado === false){
                $mensaje = array(
                    'status' => false,
                    'mensaje' => 'No se tienen cargado los certificados del cliente',
                    'error' => 'Sin certificados'
                );
                echo json_encode($mensaje);
                return;
            }

        } catch(PDOException $e){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Error al cargar certificados',
                'error' => $e->getMessage()
            );
            return json_encode($mensaje);
        }
        $sql = "SELECT rfc FROM cliente WHERE idcliente = '$idcliente'";
        try{
            // Instanciar la base de datos
            $db = new db();
    
            // Conexión
            $db = $db->connect();
            $ejecutar = $db->query($sql);
            $cliente = $ejecutar->fetch(PDO::FETCH_OBJ);
            $db = null;

        } catch(PDOException $e){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Error al cargar clientes',
                'error' => $e->getMessage()
            );
            return json_encode($mensaje);
        }
        $uuid = gen_uuid();
        $cerFile = file_get_contents("./docs/{$cliente->rfc}/{$certificado->nombre_archivo_cer}");
        $pemKeyFile = file_get_contents("./docs/{$cliente->rfc}/{$certificado->nombre_archivo_key}");
        // Emprezamos a realizar la autenticacion ante el sat
        $fiel = Fiel::create(
            $cerFile, // en formato PEM
            $pemKeyFile,      // en formato PEM o DER
            '12345678a'
        );
        // Creación del servicio
        /** @var WebClientInterface $webClient */
        $webClient = new GuzzleWebClient();
        // // Creación del servicio
        $service = new Service($fiel, $webClient);
        
    });


    function gen_uuid() {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
    
            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),
    
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,
    
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,
    
            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }