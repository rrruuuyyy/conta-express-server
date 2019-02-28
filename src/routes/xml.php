<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
//Obtener todos los clientes
$app->post('/api/xml/tablas-egreso', function(Request $request, Response $response){
	$data = $_POST;
	$idusuario = $data['idusuario'];
	$cliente = json_decode($data['cliente']);
	$token = $data['token'];
	$mes = $data['mes'];
	//  Verificacion del token
    // $auth = new Auth();
    // $token = $auth->Check($token);
    // if($token['status'] === false){
    //     echo json_encode($token);
    //     return;
    // }
	// Fin Verificacion
	$xml_ya_ingresados = [];
	$fechas = [];	
		//Consulta para obtener los xml ya ingresados
		$sql = "SELECT * FROM `docto-xml` WHERE idusuario='$idusuario' AND MONTH(fecha) = $mes AND idcliente = '$cliente->idcliente'";
		try{
			// Instanciar la base de datos
			$db = new db();

			// Conexión
			$db = $db->connect();
			$ejecutar = $db->query($sql);
			$xmls = $ejecutar->fetchAll(PDO::FETCH_OBJ);
			$db = null;
			$xml_ya_ingresados = $xmls;
		} catch(PDOException $e){
			$mensaje = array(
				'status' => false,
				'mensaje' => 'Xmls no cargados',
				'error' => $e->getMessage()
			);
			return json_encode($mensaje);
		}
    $tablas_xml = [];
	for ($i=0; $i < count($_FILES['file']['name']) ; $i++) { 
	    # code...
	    $xml = $_FILES['file']['tmp_name'][$i];
	    $info = new infoXML();
		$info = $info->obtener($xml);
		// Convertimos fecha: 2019/03/05 505548 en 2019/03/05
		$date = date_create($info->Fecha);
		$date =  date_format($date, 'Y-m-d');
		$info->Fecha = $date;
		$d = date_create($info->Fecha);
		$d =  date_format($d, 'n');
		if( $d !=  $mes){
			$info->estado = 'error';
			$info->tipo_error = 'El xml esta fuera del mes seleccionado';
		}else{
			if( $info->Receptor->Rfc != $cliente->rfc){
				$info->estado = 'error';
				$info->tipo_error = 'El Rfc del Receptor no es el mismo que el cliente';
				$info->deducible = 'no';
			}else{
				if( count($xml_ya_ingresados) != 0 ){
					for ($j=0; $j < count($xml_ya_ingresados) ; $j++) {
						# code...					
						if( $info->UUID === $xml_ya_ingresados[$j]->UUID ){
							$info = $xml_ya_ingresados[$j];
							$info->Emisor = json_decode($info->Emisor);
							$info->Receptor = json_decode($info->Receptor);
							$info->Conceptos = json_decode($info->Conceptos);
							$info->Complementos = json_decode($info->Complementos);
							$info->estado = 'viejo';
							break;
						}else{
							$info->estado = 'nuevo';
							$info->deducible = 'si';
						}
						
					}
				}else{
					$info->estado = 'nuevo';
					$info->deducible = 'si';
				}
			}
		}
		// Guardamos la informacion de los xmls
	    array_push($tablas_xml,$info);
	}
	usort($tablas_xml, "ordenarFecha");
	echo json_encode($tablas_xml);
});
$app->post('/api/xml/tablas-ingreso', function(Request $request, Response $response){
	$data = $_POST;
	$idusuario = $data['idusuario'];
	$cliente = json_decode($data['cliente']);
	$token = $data['token'];
	$mes = $data['mes'];
	//  Verificacion del token
    // $auth = new Auth();
    // $token = $auth->Check($token);
    // if($token['status'] === false){
    //     echo json_encode($token);
    //     return;
    // }
	// Fin Verificacion
	$xml_ya_ingresados = [];
	$tablas_xml = [];
	//Consulta para obtener los xml ya ingresados
	$sql = "SELECT * FROM `docto-xml` WHERE idusuario='$idusuario' AND MONTH(fecha) = $mes AND idcliente = '$cliente->idcliente'";
	try{
		// Instanciar la base de datos
		$db = new db();

		// Conexión
		$db = $db->connect();
		$ejecutar = $db->query($sql);
		$xmls = $ejecutar->fetchAll(PDO::FETCH_OBJ);
		$db = null;
		$xml_ya_ingresados = $xmls;
	} catch(PDOException $e){
		$mensaje = array(
			'status' => false,
			'mensaje' => 'Xmls no cargados',
			'error' => $e->getMessage()
		);
		return json_encode($mensaje);
	}
	// Fin
	for ($i=0; $i < count($_FILES['file']['name']) ; $i++) { 
	    # code...
	    $xml = $_FILES['file']['tmp_name'][$i];
	    $info = new infoXML();
		$info = $info->obtener($xml);
		// Convertimos fecha: 2019/03/05 505548 en 2019/03/05
		$date = date_create($info->Fecha);
		$date =  date_format($date, 'Y-m-d');
		$info->Fecha = $date;
		$d = date_create($info->Fecha);
		$d =  date_format($d, 'n');
		if( $d !=  $mes){
			$info->estado = 'error';
			$info->tipo_error = 'El xml esta fuera del mes seleccionado';
		}else{
			if( $info->Emisor->Rfc != $cliente->rfc){
				$info->estado = 'error';
				$info->tipo_error = 'El Rfc del Emisor no es el mismo que el cliente';
				$info->deducible = 'no';
			}else{
				if( count($xml_ya_ingresados) != 0 ){
					for ($j=0; $j < count($xml_ya_ingresados) ; $j++) {
						# code...					
						if( $info->UUID === $xml_ya_ingresados[$j]->uuid ){
							$info = $xml_ya_ingresados[$j];
							$info->estado = 'viejo';
							break;
						}
						
					}
				}else{
					$info->estado = 'nuevo';
					$info->deducible = 'si';
				}
			}
		}
		// Guardamos la informacion de los xmls
		array_push($tablas_xml,$info);
	}
	usort($tablas_xml, "ordenarFecha");
	echo json_encode($tablas_xml);
});
$app->post('/api/xml/upload', function(Request $request, Response $response){
	$idusuario = $request->getParam('idusuario');
	$idcliente = $request->getParam('idcliente');
	$token = $request->getParam('token');
	$xmls = json_decode($request->getParam('xmls'));
	$con_errores = false;
	$xml_errores = [];
	//  Verificacion del token
    // $auth = new Auth();
    // $token = $auth->Check($token);
    // if($token['status'] === false){
    //     echo json_encode($token);
    //     return;
    // }
	// Fin Verificacion
	for ($i=0; $i < count($xmls) ; $i++) { 
		# code...
		if($xmls[$i]->estado != "error"){
			if($xmls[$i]->estado === "nuevo"){
				$sql = "INSERT INTO `docto-xml` 
	(idcliente,idusuario,TipoDeComprobante,Serie,Folio,FormaPago,Subtotal,Total,Moneda,TipoCambio,MetodoPago,LugarExpedicion,Conceptos,Impuestos,Complementos, Fecha,UUID,deducible,estado,UUIDS_relacionados,Emisor,Receptor) 
	values 
	(:idcliente,:idusuario,:TipoDeComprobante,:Serie,:Folio,:FormaPago,:Subtotal,:Total,:Moneda,:TipoCambio,:MetodoPago,:LugarExpedicion,:Conceptos,:Impuestos,:Complementos,:Fecha,:UUID,:deducible,:estado,:UUIDS_relacionados,:Emisor,:Receptor) " ;
				try{
					$conceptos = json_encode($xmls[$i]->Conceptos);
					$impuestos = json_encode($xmls[$i]->Impuestos);
					$Complementos = json_encode($xmls[$i]->Complementos);
					$Emisor = json_encode($xmls[$i]->Emisor);
					$Receptor = json_encode($xmls[$i]->Receptor);
					switch ($xmls[$i]->Conceptos) {
						case 'value':
							# code...
							break;
						
						default:
							# code...
							break;
					}
					$uuids_relacionados = json_encode($xmls[$i]->UUIDS_relacionados);
					// Get DB Object
					$db = new db();
					// Connect
					$db = $db->connect();
					$stmt = $db->prepare($sql);					
					$stmt->bindParam(':idcliente', $idcliente);
					$stmt->bindParam(':idusuario', $idusuario);
					$stmt->bindParam(':TipoDeComprobante', $xmls[$i]->TipoDeComprobante);
					$stmt->bindParam(':Serie', $xmls[$i]->Serie);
					$stmt->bindParam(':Folio', $xmls[$i]->Folio);
					$stmt->bindParam(':FormaPago', $xmls[$i]->FormaPago);
					$stmt->bindParam(':Subtotal', $xmls[$i]->SubTotal);
					$stmt->bindParam(':Total', $xmls[$i]->Total);
					$stmt->bindParam(':Moneda', $xmls[$i]->Moneda);
					$stmt->bindParam(':TipoCambio', $xmls[$i]->TipoCambio);
					$stmt->bindParam(':MetodoPago', $xmls[$i]->MetodoPago);
					$stmt->bindParam(':LugarExpedicion', $xmls[$i]->LugarExpedicion);
					$stmt->bindParam(':Conceptos', $conceptos);
					$stmt->bindParam(':Impuestos', $impuestos);
					$stmt->bindParam(':Complementos', $Complementos);
					$stmt->bindParam(':Fecha', $xmls[$i]->Fecha);
					$stmt->bindParam(':UUID', $xmls[$i]->UUID);
					$stmt->bindParam(':deducible', $xmls[$i]->deducible);
					$stmt->bindParam(':estado', $xmls[$i]->estado);
					$stmt->bindParam(':UUIDS_relacionados', $uuids_relacionados);
					$stmt->bindParam(':Emisor', $Emisor);
					$stmt->bindParam(':Receptor', $Receptor);
					$stmt->execute();
			
				} catch(PDOException $e){
					$con_errores = true;
					$mensaje = array(
						'xml' => $xmls[$i],
						'error' => $e->getMessage()
					);
					array_push($xml_errores, $mensaje);
				}
			}else{
				$sql = "UPDATE `docto-xml`  SET
                deducible = :deducible
				WHERE `iddocto_xml` = {$xmls[$i]->iddocto_xml}";
				try{
					// Get DB Object
					$db = new db();
					// Connect
					$db = $db->connect();
					$stmt = $db->prepare($sql);
					$stmt->bindParam(':deducible', $xmls[$i]->deducible);
					$stmt->execute();
			
				} catch(PDOException $e){
					$con_errores = true;
					$mensaje = array(
						'error' => $e->getMessage()
					);
					$mensaje = array(
						'xml' => $xmls[$i],
						'mensaje' => 'Cliente no actualizado',
						'error' => $e->getMessage()
					);
					array_push($xml_errores, $mensaje);
				}
			}
		}
	}
	if($con_errores === true){
		$mensaje = array(
            'status' => true,
            'mensaje' => 'No todos los xmls se guardaron correctamente',
            'xmls' => $xml_errores,
            'rest' => ''
        );
        echo json_encode($mensaje);
	}else{
		$mensaje = array(
            'status' => true,
            'mensaje' => 'Xmls guardados correctamente',
            'rest' => ''
        );
        echo json_encode($mensaje);
	}
	// echo json_encode($xmls);
});
$app->post('/api/xml/archivo', function(Request $request, Response $response){
	$params = $request->getParam('params');
	//  Verificacion del token
    // $auth = new Auth();
    // $token = $auth->Check($token);
    // if($token['status'] === false){
    //     echo json_encode($token);
    //     return;
    // }
	// Fin Verificacion
	
});





function ordenarFecha($a,$b){
	return strtotime($a->Fecha) - strtotime($b->Fecha);
}