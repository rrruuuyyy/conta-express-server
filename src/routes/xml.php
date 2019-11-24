<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
require '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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
		$sql = "SELECT * FROM `docto-xml` WHERE idusuario='$idusuario' AND MONTH(fecha) = $mes AND idcliente = '$cliente->idcliente' AND conta='egreso'";
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
		$info->UUID = strtoupper($info->UUID);
        $info->UUID = str_replace(" ","",$info->UUID);
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
							$info->Impuestos = json_decode($info->Impuestos);
							$info->estado = 'viejo';
							break;
						}else{
							$info->estado = 'nuevo';
							$info->deducible = 'si';
							$info->conta = 'egreso';
						}
						
					}
				}else{
					$info->estado = 'nuevo';
					$info->deducible = 'si';
					$info->conta = 'egreso';
				}
			}
		}
		//Guardamos la informacion de los xmls
	    array_push($tablas_xml,$info);
	}
	usort($tablas_xml, "ordenarFecha");
	echo json_encode($tablas_xml);
});
// <-------------------- INGRESO ------------------------------>
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
	$sql = "SELECT * FROM `docto-xml` WHERE idusuario='$idusuario' AND MONTH(fecha) = $mes AND idcliente = '$cliente->idcliente' AND conta = 'ingreso'";
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
		$info->UUID = strtoupper($info->UUID);
        $info->UUID = str_replace(" ","",$info->UUID);
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
						if( $info->UUID === $xml_ya_ingresados[$j]->UUID ){
							$info = $xml_ya_ingresados[$j];
							$info->Emisor = json_decode($info->Emisor);
							$info->Receptor = json_decode($info->Receptor);
							$info->Conceptos = json_decode($info->Conceptos);
							$info->Complementos = json_decode($info->Complementos);
							$info->Impuestos = json_decode($info->Impuestos);
							$info->estado = 'viejo';
							$info->conta = 'ingreso';
							break;
						}else{
							$info->estado = 'nuevo';
							$info->deducible = 'si';
							$info->conta = 'ingreso';
						}
						
					}
				}else{
					$info->estado = 'nuevo';
					$info->deducible = 'si';
					$info->conta = 'ingreso';
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
	$tipo = $request->getParam('tipo');
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
				(idcliente,idusuario,TipoDeComprobante,Serie,Folio,FormaPago,SubTotal,Total,Moneda,TipoCambio,MetodoPago,LugarExpedicion,Conceptos,Impuestos,Complementos, Fecha,UUID,deducible,estado,UUIDS_relacionados,Emisor,Receptor,conta,TotalGravado,TotalExento,Descuento,TotalImpuestosRetenidos,TotalImpuestosTrasladados,Otros,Certificado,Sello,status) 
				values 
				(:idcliente,:idusuario,:TipoDeComprobante,:Serie,:Folio,:FormaPago,:SubTotal,:Total,:Moneda,:TipoCambio,:MetodoPago,:LugarExpedicion,:Conceptos,:Impuestos,:Complementos,:Fecha,:UUID,:deducible,:estado,:UUIDS_relacionados,:Emisor,:Receptor,:conta,:TotalGravado,:TotalExento,:Descuento,:TotalImpuestosRetenidos,:TotalImpuestosTrasladados,:Otros,:Certificado,:Sello,:status) " ;
				try{
					$conceptos = json_encode($xmls[$i]->Conceptos);
					$impuestos = json_encode($xmls[$i]->Impuestos);
					$Complementos = json_encode($xmls[$i]->Complementos);
					$Emisor = json_encode($xmls[$i]->Emisor);
					$Receptor = json_encode($xmls[$i]->Receptor);
					$uuids_relacionados = json_encode($xmls[$i]->UUIDS_relacionados);
					$Otros = json_encode($xmls[$i]->Otros);
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
					$stmt->bindParam(':SubTotal', $xmls[$i]->SubTotal);
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
					$stmt->bindParam(':conta', $xmls[$i]->conta);
					$stmt->bindParam(':TotalGravado', $xmls[$i]->TotalGravado);
					$stmt->bindParam(':TotalExento', $xmls[$i]->TotalExento);
					$stmt->bindParam(':Descuento', $xmls[$i]->Descuento);
					$stmt->bindParam(':TotalImpuestosRetenidos', $xmls[$i]->TotalImpuestosRetenidos);
					$stmt->bindParam(':TotalImpuestosTrasladados', $xmls[$i]->TotalImpuestosTrasladados);
					$status = 'vigente';
					$stmt->bindParam(':Otros', $Otros);
					$Certificado = substr($xmls[$i]->Certificado,-8);
					$Sello = substr($xmls[$i]->Sello,-8);
					$stmt->bindParam(':Certificado', $Certificado);
					$stmt->bindParam(':Sello', $Sello);
					$stmt->bindParam(':status', $status);
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
		$respuesta = new ProcesadorXml;
		$respuesta = $respuesta->procesar($tipo,$xmls,$idcliente);
		if( $respuesta === true){			
			$mensaje = array(
				'status' => true,
				'mensaje' => 'Xmls guardados correctamente',
				'rest' => ''
			);
		}else{
			$mensaje = $respuesta;
		}
        echo json_encode($mensaje);
	}
	// echo json_encode($xmls);
});
$app->post('/api/xml/archivo', function(Request $request, Response $response){
	$params = $request->getParam('params');
	$idusuario = $params['idusuario'];
	$mes = $params['mes'];
	$year = $params['year'];
	$cliente = $params['cliente'];
	$idcliente = $cliente['idcliente'];
	//  Verificacion del token
    // $auth = new Auth();
    // $token = $auth->Check($token);
    // if($token['status'] === false){
    //     echo json_encode($token);
    //     return;
    // }
	// Fin Verificacion
	// Obtenemos los xmls segun su tipo (Ingreso/Egreso)
	$xmls_ingreso = [];
	$xmls_egreso = [];
	$sql_ingreso = "SELECT * FROM `docto-xml` WHERE idusuario='$idusuario' AND MONTH(fecha) = $mes AND YEAR(fecha) = $year AND idcliente = '$idcliente' AND conta='ingreso' ";
	$sql_egreso = "SELECT * FROM `docto-xml` WHERE idusuario='$idusuario' AND MONTH(fecha) = $mes AND YEAR(fecha) = $year AND idcliente = '$idcliente' AND conta='egreso' ";
	try{
		// Instanciar la base de datos
		$db = new db();
		// Conexión
		$db = $db->connect();
		$ejecutar = $db->query($sql_ingreso);
		$xmls = $ejecutar->fetchAll(PDO::FETCH_OBJ);
		$db = null;
		$xmls_ingreso = $xmls;
	} catch(PDOException $e){
		$mensaje = array(
			'status' => false,
			'mensaje' => 'Xmls no cargados',
			'error' => $e->getMessage()
		);
		return json_encode($mensaje);
	}
	try{
		// Instanciar la base de datos
		$db = new db();
		// Conexión
		$db = $db->connect();
		$ejecutar = $db->query($sql_egreso);
		$xmls = $ejecutar->fetchAll(PDO::FETCH_OBJ);
		$db = null;
		$xmls_egreso = $xmls;
	} catch(PDOException $e){
		$mensaje = array(
			'status' => false,
			'mensaje' => 'Xmls no cargados',
			'error' => $e->getMessage()
		);
		return json_encode($mensaje);
	}
	
	$array_split = [];
	for ($i=0; $i < count($xmls_ingreso) ; $i++) { 
		# code...
		if( $xmls_ingreso[$i]->TipoDeComprobante === "N" ){
			array_push($xmls_egreso, $xmls_ingreso[$i]);
			array_push($array_split, $i);
		}else{
			$xmls_ingreso[$i]->Emisor = json_decode($xmls_ingreso[$i]->Emisor);
			$xmls_ingreso[$i]->Receptor = json_decode($xmls_ingreso[$i]->Receptor);
			$xmls_ingreso[$i]->Conceptos = json_decode($xmls_ingreso[$i]->Conceptos);
			$xmls_ingreso[$i]->Impuestos = json_decode($xmls_ingreso[$i]->Impuestos);
			$xmls_ingreso[$i]->Complementos = json_decode($xmls_ingreso[$i]->Complementos);
		}
	}
	for ($i=count($array_split)-1; $i >= 0 ; $i--) { 
		array_splice($xmls_ingreso,$array_split[$i],1);
	}
	for ($i=0; $i < count($xmls_egreso) ; $i++) { 
		# code...
		$xmls_egreso[$i]->Emisor = json_decode($xmls_egreso[$i]->Emisor);
		$xmls_egreso[$i]->Receptor = json_decode($xmls_egreso[$i]->Receptor);
		$xmls_egreso[$i]->Conceptos = json_decode($xmls_egreso[$i]->Conceptos);
		$xmls_egreso[$i]->Impuestos = json_decode($xmls_egreso[$i]->Impuestos);
		$xmls_egreso[$i]->Complementos = json_decode($xmls_egreso[$i]->Complementos);
		$xmls_egreso[$i]->Otros = json_decode($xmls_egreso[$i]->Otros);
	}
	
	$spreadsheet = new Spreadsheet();
	$spreadsheet->getActiveSheet()->getPageSetup()->setScale(75);
	// Creacion de autor, titulo, etc
	$spreadsheet->getProperties()
    ->setCreator("No solo codigo")
    ->setLastModifiedBy("No solo codigo");
	// Establecer paginas
	// $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);
	// $spreadsheet->getActiveSheet()->getPageSetup()->setFitToHeight(0);	
	// Configuracion del margen
	$spreadsheet->getActiveSheet()->getPageMargins()->setTop(1);
	$spreadsheet->getActiveSheet()->getPageMargins()->setRight(0.75);
	$spreadsheet->getActiveSheet()->getPageMargins()->setLeft(0.75);
	$spreadsheet->getActiveSheet()->getPageMargins()->setBottom(1);
	// Horizontal
	$spreadsheet->getActiveSheet()->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
	// Variables de stylo de celdas
	$styleCabeceras = [
		'font' => [
			'bold' => true,
		],
		'alignment' => [
			
		],
		'borders' => [
			'top' => [
				'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
			],
		],
	];
	$styleCabeceraTabla = [
		'alignment' => [
			'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
			'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
		],
		'borders' => [
			'allBorders' => [
				'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
				// 'color' => \PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLACK,
			],
		],
	];
	$styleCuerpoTabla = [
		'borders' => [
			'right' => [
				'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
			],
			'left' => [
				'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
			],
			'vertical' => [
				'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
			],
		],
	];
	$mesSave = $mes;
	$mes = obtenerMes($mes);
		// libro de ingresos
	$base = 32;
	$hoja = 1;
	$sheet = $spreadsheet->getActiveSheet();
	$sheet->setTitle("Ingresos {$year}");
	// <------------------------------------------ INGRESOS ------------------------------------------------>
	// Documentos para el libro de Ingreso
		$sheet->setCellValue('A1', strtoupper($cliente['nombre']) );
		$sheet->setCellValue('L1', "HOJA {$hoja}" ); 
		$sheet->setCellValue('A2', strtoupper("Ingresos correspondientes al mes de {$mes} del {$year}") ); 
		$spreadsheet->getActiveSheet()->mergeCells('A4:A5');
		$sheet->setCellValue('A4', "Factura" );
		$spreadsheet->getActiveSheet()->mergeCells('B4:B5');
		$sheet->setCellValue('B4', "Concepto" );
		$spreadsheet->getActiveSheet()->mergeCells('C4:E4');
		$sheet->setCellValue('C4', "Publico General" );
		$sheet->setCellValue('C5', "Total" );
		$sheet->setCellValue('D5', "16%" );
		$sheet->setCellValue('E5', "0%" );
		$spreadsheet->getActiveSheet()->mergeCells('F4:G4');
		$sheet->setCellValue('F4', "Cliente" );
		$sheet->setCellValue('F5', "16%" );
		$sheet->setCellValue('G5', "0%" );
		$spreadsheet->getActiveSheet()->mergeCells('H4:I4');
		$sheet->setCellValue('H4', "Parcialidad" );
		$sheet->setCellValue('H5', "16%" );
		$sheet->setCellValue('I5', "0%" );
		$spreadsheet->getActiveSheet()->mergeCells('J4:K4');
		$sheet->setCellValue('J4', "Retenciones" );
		$sheet->setCellValue('J5', "IVA" );
		$sheet->setCellValue('K5', "ISR" );
		$spreadsheet->getActiveSheet()->mergeCells('L4:M4');
		$sheet->setCellValue('L4', "Impuestos" );
		$sheet->setCellValue('L5', "IVA" );
		$sheet->setCellValue('M5', "IEPS" );
		$spreadsheet->getActiveSheet()->mergeCells('N4:N5');
		$sheet->setCellValue('N4', "Credito o devoluciones" );
		$spreadsheet->getActiveSheet()->mergeCells('O4:O5');
		$sheet->setCellValue('O4', "Pagos" );
		$spreadsheet->getActiveSheet()->mergeCells('P4:Q4');
		$sheet->setCellValue('P4', "Imp. Locales" );
		$sheet->setCellValue('P5', "Trasl" );
		$sheet->setCellValue('Q5', "Reten" );
		$spreadsheet->getActiveSheet()->mergeCells('R4:R5');
		$sheet->setCellValue('R4', "Total" );
		
		$spreadsheet->getActiveSheet()->getStyle('A4:R4')->applyFromArray($styleCabeceraTabla);
		$spreadsheet->getActiveSheet()->getStyle('A5:R5')->applyFromArray($styleCabeceraTabla);
		// El siguiente codigo agrega a las filas que se ajusten al tamaño de su contenido
		$spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(47);
		// $spreadsheet->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
		$spreadsheet->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
		$spreadsheet->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
		$spreadsheet->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
		$spreadsheet->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
		$spreadsheet->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
		$spreadsheet->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);
		$spreadsheet->getActiveSheet()->getColumnDimension('I')->setAutoSize(true);
		$spreadsheet->getActiveSheet()->getColumnDimension('J')->setAutoSize(true);
		$spreadsheet->getActiveSheet()->getColumnDimension('K')->setAutoSize(true);
		$spreadsheet->getActiveSheet()->getStyle('L4')->getAlignment()->setWrapText(true);
		$spreadsheet->getActiveSheet()->getColumnDimension('M')->setAutoSize(true);
		$num = 6;
		$start = 6;
		$limite = 42;
		$rango = 42;
		
		$limite_superado = true;
		// ORDENAR ARRAY DE XMLS POR FACTURA
		$aux = [];
		foreach ($xmls_ingreso as $key => $row) {
			$aux[$key] = (int)$row->Folio;
		}
		array_multisort($aux, SORT_ASC, $xmls_ingreso);
		for ($i=0; $i < count($xmls_ingreso) ; $i++) {
			if($num === $limite){
				// if ($hoja === 1) {
					$final = $limite  - 1;
					$spreadsheet->getActiveSheet()->getStyle("C{$start}:N{$limite}")->getNumberFormat()->setFormatCode('#,##0.00');
					# code...
					$sheet->setCellValue("B{$limite}" , "INGRESOS TOTALES" );
					$sheet->setCellValue("C{$limite}" , "=SUM(C{$start}:C{$limite})" );
					$sheet->setCellValue("D{$limite}" , "=SUM(D{$start}:D{$limite})" );
					$sheet->setCellValue("E{$limite}" , "=SUM(E{$start}:E{$limite})" );
					$sheet->setCellValue("F{$limite}" , "=SUM(F{$start}:F{$limite})" );
					$sheet->setCellValue("G{$limite}" , "=SUM(G{$start}:G{$limite})" );
					$sheet->setCellValue("H{$limite}" , "=SUM(H{$start}:H{$limite})" );
					$sheet->setCellValue("I{$limite}" , "=SUM(I{$start}:I{$limite})" );
					$sheet->setCellValue("J{$limite}" , "=SUM(J{$start}:J{$limite})" );
					$sheet->setCellValue("K{$limite}" , "=SUM(K{$start}:K{$limite})" );
					$sheet->setCellValue("L{$limite}" , "=SUM(L{$start}:L{$limite})" );
					$sheet->setCellValue("M{$limite}" , "=SUM(M{$start}:M{$limite})" );
					$sheet->setCellValue("N{$limite}" , "=SUM(N{$start}:N{$limite})" );
					$sheet->setCellValue("O{$limite}" , "=SUM(O{$start}:O{$limite})" );
					$sheet->setCellValue("P{$limite}" , "=SUM(P{$start}:P{$limite})" );
					$sheet->setCellValue("Q{$limite}" , "=SUM(Q{$start}:Q{$limite})" );
					$sheet->setCellValue("R{$limite}" , "=SUM(R{$start}:R{$limite})" );
					$spreadsheet->getActiveSheet()->getStyle("A{$limite}:R{$limite}")->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
					$spreadsheet->getActiveSheet()->getStyle("A{$start}:R{$limite}")->applyFromArray($styleCuerpoTabla);
					$num++;
					$hoja++;
					$sheet->setCellValue("A{$num}", strtoupper($cliente['nombre']) );
					$sheet->setCellValue("H{$num}", "HOJA {$hoja}" );
					$num++;
					$sheet->setCellValue("A{$num}", strtoupper("Ingresos correspondientes al mes de {$mes} del {$year}") ); 
					$num++;
					$num++;
					$help = $num + 1;
					
					$spreadsheet->getActiveSheet()->mergeCells("A{$num}:A{$help}");
					$sheet->setCellValue("A{$num}", "Factura" );
					$spreadsheet->getActiveSheet()->mergeCells("B{$num}:B{$help}");
					$sheet->setCellValue("B{$num}", "Concepto" );
					$spreadsheet->getActiveSheet()->mergeCells("C{$num}:E{$num}");
					$sheet->setCellValue("C{$num}", "Publico General" );
					$sheet->setCellValue("C{$help}", "Total" );
					$sheet->setCellValue("D{$help}", "16%" );
					$sheet->setCellValue("E{$help}", "0%" );
					$spreadsheet->getActiveSheet()->mergeCells("F{$num}:G{$num}");
					$sheet->setCellValue("F{$num}", "Cliente" );
					$sheet->setCellValue("F{$help}", "16%" );
					$sheet->setCellValue("G{$help}", "0%" );		
					$spreadsheet->getActiveSheet()->mergeCells("H{$num}:I{$num}");
					$sheet->setCellValue("H{$num}", "Parcialidad" );
					$sheet->setCellValue("H{$help}", "16%" );
					$sheet->setCellValue("I{$help}", "0%" );
					$spreadsheet->getActiveSheet()->mergeCells("J{$num}:K{$num}");
					$sheet->setCellValue("J{$num}", "Retenciones" );
					$sheet->setCellValue("J{$help}", "IVA" );
					$sheet->setCellValue("K{$help}", "ISR" );
					$spreadsheet->getActiveSheet()->mergeCells("L{$num}:M{$num}");
					$sheet->setCellValue("L{$num}", "Impuestos" );
					$sheet->setCellValue("L{$help}", "IVA" );
					$sheet->setCellValue("M{$help}", "IEPS" );
					$spreadsheet->getActiveSheet()->mergeCells("N{$num}:N{$help}");
					$sheet->setCellValue("N{$num}", "Credito o devoluciones" );
					$spreadsheet->getActiveSheet()->mergeCells("O{$num}:O{$help}");
					$sheet->setCellValue("O{$num}", "Pagos" );
					$spreadsheet->getActiveSheet()->mergeCells("P{$num}:Q{$num}");
					$sheet->setCellValue("P{$num}", "Imp. Locales" );
					$sheet->setCellValue("P{$help}", "Trasl" );
					$sheet->setCellValue("Q{$help}", "Reten" );
					$spreadsheet->getActiveSheet()->mergeCells("R{$num}:R{$help}");
					$sheet->setCellValue("R{$num}", "Total" );

					$spreadsheet->getActiveSheet()->getStyle("A{$num}:R{$help}")->applyFromArray($styleCabeceraTabla);
					$num++;
					$num++;
					$help = $num + 1;
					$start = $num;
					$datos = $limite;
					$sheet->setCellValue("B{$num}" , "SUMA ANTERIOR" );
					$sheet->setCellValue("C{$num}" , "=C{$datos}" );
					$sheet->setCellValue("D{$num}" , "=D{$datos}" );
					$sheet->setCellValue("E{$num}" , "=E{$datos}" );
					$sheet->setCellValue("F{$num}" , "=F{$datos}" );
					$sheet->setCellValue("G{$num}" , "=G{$datos}" );
					$sheet->setCellValue("H{$num}" , "=H{$datos}" );
					$sheet->setCellValue("I{$num}" , "=I{$datos}" );
					$sheet->setCellValue("J{$num}" , "=J{$datos}" );
					$sheet->setCellValue("K{$num}" , "=K{$datos}" );
					$sheet->setCellValue("L{$num}" , "=L{$datos}" );
					$sheet->setCellValue("M{$num}" , "=M{$datos}" );
					$sheet->setCellValue("N{$num}" , "=N{$datos}" );
					$sheet->setCellValue("O{$num}" , "=O{$datos}" );
					$sheet->setCellValue("P{$num}" , "=P{$datos}" );
					$sheet->setCellValue("Q{$num}" , "=Q{$datos}" );
					$sheet->setCellValue("R{$num}" , "=R{$datos}" );
					$num++;
					// <---------------------------------------Empezamos en la siguiente hoja ------------------------------------>
					// <------------------- Comenzamos --------------------->
					$sheet->setCellValue("C{$num}" , 0 );
					$sheet->setCellValue("D{$num}" , 0 );
					$sheet->setCellValue("E{$num}" , 0 );
					$sheet->setCellValue("F{$num}" , 0 );
					$sheet->setCellValue("G{$num}" , 0 );
					$sheet->setCellValue("H{$num}" , 0 );
					$sheet->setCellValue("I{$num}" , 0 );
					$sheet->setCellValue("J{$num}" , 0 );
					$sheet->setCellValue("K{$num}" , 0 );
					$sheet->setCellValue("L{$num}" , 0 );
					$sheet->setCellValue("M{$num}" , 0 );
					$sheet->setCellValue("N{$num}" , 0 );
					$sheet->setCellValue("O{$num}" , 0 );
					$sheet->setCellValue("P{$num}" , 0 );
					$sheet->setCellValue("Q{$num}" , 0 );
					$sheet->setCellValue("R{$num}" , 0 );
					$sheet->setCellValue('A'.$num , "{$xmls_ingreso[$i]->Folio}" );
					$sheet->setCellValue('B'.$num , mb_convert_case("{$xmls_ingreso[$i]->Receptor->Nombre}", MB_CASE_TITLE, "UTF-8")  );
					// <----------Detectamos si el MetodoDePago es PPD
					if($xmls_ingreso[$i]->TipoDeComprobante === "E"){
						$sheet->setCellValue('N'.$num , "{$xmls_ingreso[$i]->Total}" );
					}else{
						// CODIGO SI EL TIPO DE COMPROBANTE ES INGRESO
						// <----------Detectamos si el MetodoDePago es PPD
						if( $xmls_ingreso[$i]->MetodoPago === "PPD" ){
							//16%
							$sheet->setCellValue('H'.$num , "{$xmls_ingreso[$i]->TotalGravado}" );
							//0%
							$sheet->setCellValue('I'.$num , "{$xmls_ingreso[$i]->TotalExento}" );
						}else{
							// <------------------- Si es ventas al publico en general -------------------------->
							if($xmls_ingreso[$i]->TipoDeComprobante === "I"){
								if( $xmls_ingreso[$i]->Receptor->Rfc === "XAXX010101000" ){
									$sheet->setCellValue('C'.$num , "{$xmls_ingreso[$i]->SubTotal}" );
									$sheet->setCellValue('D'.$num , "{$xmls_ingreso[$i]->TotalGravado}" );
									$sheet->setCellValue('E'.$num , "{$xmls_ingreso[$i]->TotalExento}" );
									// $subT = 0;
									// $subT = (float)$xmls_ingreso[$i]->TotalGravado + (float)$xmls_ingreso[$i]->TotalExento;
									// if($subT === 0.0){
									// 	$sheet->setCellValue('C'.$num , "{$xmls_ingreso[$i]->Total}" );
									// }else{
									// 	$sheet->setCellValue('C'.$num , "{$subT}" );									
									// }
								}else{
									//SI NO ES EN PARCIALIDADES SE VA A LA DE CLIENTE
									//16%
									$sheet->setCellValue('F'.$num , "{$xmls_ingreso[$i]->TotalGravado}" );
									//0%
									$sheet->setCellValue('G'.$num , "{$xmls_ingreso[$i]->TotalExento}" );
								}
							}
							if($xmls_ingreso[$i]->TipoDeComprobante === "ingreso"){
								$sheet->setCellValue('C'.$num , "{$xmls_ingreso[$i]->SubTotal}" );
							}
						}
					}
					
					if($xmls_ingreso[$i]->TipoDeComprobante === "E" OR $xmls_ingreso[$i]->TipoDeComprobante === "I" OR $xmls_ingreso[$i]->TipoDeComprobante === "ingreso" ){
						$sheet->setCellValue('J'.$num , "{$xmls_ingreso[$i]->Impuestos->TotalRetencionIVA}" );
						$sheet->setCellValue('K'.$num , "{$xmls_ingreso[$i]->Impuestos->TotalRetencionISR}" );					
						$sheet->setCellValue('L'.$num , "{$xmls_ingreso[$i]->Impuestos->TotalTrasladoIVA}" );
						$sheet->setCellValue('M'.$num , "{$xmls_ingreso[$i]->Impuestos->TotalTrasladoIEPS}" );
						$sheet->setCellValue('P'.$num , "{$xmls_ingreso[$i]->Impuestos->TotaldeTrasladosLoc}" );
						$sheet->setCellValue('Q'.$num , "{$xmls_ingreso[$i]->Impuestos->TotaldeRetencionesLoc}" );
					}
					if($xmls_ingreso[$i]->TipoDeComprobante === "P"){
						$Monto = 0;
						for ($s=0; $s < count($xmls_ingreso[$i]->Complementos->Pagos) ; $s++) {
							$Monto = $Monto + $xmls_ingreso[$i]->Complementos->Pagos[$s]->Monto;
						}
						$sheet->setCellValue('O'.$num , "{$Monto}" );
					}
					$sheet->setCellValue('R'.$num , "=sum(D{$num}:I{$num})-J{$num}-K{$num}+sum(L{$num}:M{$num})-N{$num}+ sum(O{$num}:Q{$num})" );
					// <------------------------------ Detector de errores en estructura de XMLS ------------------------------>
					$sheet->setCellValue('S'.$num , "{$xmls_ingreso[$i]->Total}" );
					
					// $hoja++;
					$num++;
					$limite = $limite + $rango;
				// }else{
				// }
			}else{
				// <------------------------------------Comienzo de la hoja de calculo --------------------------------------------------------------->
				$sheet->setCellValue("C{$num}" , 0 );
				$sheet->setCellValue("D{$num}" , 0 );
				$sheet->setCellValue("E{$num}" , 0 );
				$sheet->setCellValue("F{$num}" , 0 );
				$sheet->setCellValue("G{$num}" , 0 );
				$sheet->setCellValue("H{$num}" , 0 );
				$sheet->setCellValue("I{$num}" , 0 );
				$sheet->setCellValue("J{$num}" , 0 );
				$sheet->setCellValue("K{$num}" , 0 );
				$sheet->setCellValue("L{$num}" , 0 );
				$sheet->setCellValue("M{$num}" , 0 );
				$sheet->setCellValue("N{$num}" , 0 );
				$sheet->setCellValue("O{$num}" , 0 );
				$sheet->setCellValue("P{$num}" , 0 );
				$sheet->setCellValue("Q{$num}" , 0 );
				$sheet->setCellValue("R{$num}" , 0 );
				// <------------------- Comenzamos --------------------->
				$sheet->setCellValue('A'.$num , "{$xmls_ingreso[$i]->Folio}" );
				$sheet->setCellValue('B'.$num , mb_convert_case("{$xmls_ingreso[$i]->Receptor->Nombre}", MB_CASE_TITLE, "UTF-8")  );
				// <----------Detectamos si el MetodoDePago es PPD
				if($xmls_ingreso[$i]->TipoDeComprobante === "E"){
					$sheet->setCellValue('N'.$num , "{$xmls_ingreso[$i]->Total}" );
				}else{
					// CODIGO SI EL TIPO DE COMPROBANTE ES INGRESO
					// <----------Detectamos si el MetodoDePago es PPD
					if( $xmls_ingreso[$i]->MetodoPago === "PPD" ){
						//16%
						$sheet->setCellValue('H'.$num , "{$xmls_ingreso[$i]->TotalGravado}" );
						//0%
						$sheet->setCellValue('I'.$num , "{$xmls_ingreso[$i]->TotalExento}" );
					}else{
						// <------------------- Si es ventas al publico en general -------------------------->
						if($xmls_ingreso[$i]->TipoDeComprobante === "I"){
							if( $xmls_ingreso[$i]->Receptor->Rfc === "XAXX010101000" ){
								$sheet->setCellValue('C'.$num , "{$xmls_ingreso[$i]->SubTotal}" );
								$sheet->setCellValue('D'.$num , "{$xmls_ingreso[$i]->TotalGravado}" );
								$sheet->setCellValue('E'.$num , "{$xmls_ingreso[$i]->TotalExento}" );
								// $subT = 0;
								// $subT = (float)$xmls_ingreso[$i]->TotalGravado + (float)$xmls_ingreso[$i]->TotalExento;
								// if($subT === 0.0){
								// 	$sheet->setCellValue('C'.$num , "{$xmls_ingreso[$i]->Total}" );
								// }else{
								// 	$sheet->setCellValue('C'.$num , "{$subT}" );									
								// }
							}else{
								//SI NO ES EN PARCIALIDADES SE VA A LA DE CLIENTE
								//16%
								$sheet->setCellValue('F'.$num , "{$xmls_ingreso[$i]->TotalGravado}" );
								//0%
								$sheet->setCellValue('G'.$num , "{$xmls_ingreso[$i]->TotalExento}" );
							}
						}
						if($xmls_ingreso[$i]->TipoDeComprobante === "ingreso"){
							$sheet->setCellValue('C'.$num , "{$xmls_ingreso[$i]->SubTotal}" );					
							$sheet->setCellValue('L'.$num , "{$xmls_ingreso[$i]->Impuestos->TrasladoIVA}" );
						}
					}
				}
				if($xmls_ingreso[$i]->TipoDeComprobante === "E" OR $xmls_ingreso[$i]->TipoDeComprobante === "I"){
					$sheet->setCellValue('J'.$num , "{$xmls_ingreso[$i]->Impuestos->TotalRetencionIVA}" );
					$sheet->setCellValue('K'.$num , "{$xmls_ingreso[$i]->Impuestos->TotalRetencionISR}" );					
					$sheet->setCellValue('L'.$num , "{$xmls_ingreso[$i]->Impuestos->TotalTrasladoIVA}" );
					$sheet->setCellValue('M'.$num , "{$xmls_ingreso[$i]->Impuestos->TotalTrasladoIEPS}" );
					$sheet->setCellValue('P'.$num , "{$xmls_ingreso[$i]->Impuestos->TotaldeTrasladosLoc}" );
				}
				if($xmls_ingreso[$i]->TipoDeComprobante === "P"){
					$Monto = 0;
					for ($s=0; $s < count($xmls_ingreso[$i]->Complementos->Pagos) ; $s++) {
						$Monto = $Monto + $xmls_ingreso[$i]->Complementos->Pagos[$s]->Monto;
					}
					$sheet->setCellValue('O'.$num , "{$Monto}" );
				}
				$sheet->setCellValue('R'.$num , "=sum(D{$num}:I{$num})-J{$num}-K{$num}+sum(L{$num}:M{$num})-N{$num}+ sum(O{$num}:Q{$num})" );
				// <------------------------------ Detector de errores en estructura de XMLS ------------------------------>
				$sheet->setCellValue('S'.$num , "{$xmls_ingreso[$i]->Total}" );
				
				$num++;
			}
			# code...
			
		}
		if( $num < $limite ){
			$spreadsheet->getActiveSheet()->getStyle("C{$start}:R{$limite}")->getNumberFormat()->setFormatCode('#,##0.00');
			$final = $limite - 1;
			# code...
			$sheet->setCellValue("B{$limite}" , "INGRESOS TOTALES" );
			$sheet->setCellValue("C{$limite}" , "=SUM(C{$start}:C{$limite})" );
			$sheet->setCellValue("D{$limite}" , "=SUM(D{$start}:D{$limite})" );
			$sheet->setCellValue("E{$limite}" , "=SUM(E{$start}:E{$limite})" );
			$sheet->setCellValue("F{$limite}" , "=SUM(F{$start}:F{$limite})" );
			$sheet->setCellValue("G{$limite}" , "=SUM(G{$start}:G{$limite})" );
			$sheet->setCellValue("H{$limite}" , "=SUM(H{$start}:H{$limite})" );
			$sheet->setCellValue("I{$limite}" , "=SUM(I{$start}:I{$limite})" );
			$sheet->setCellValue("J{$limite}" , "=SUM(J{$start}:J{$limite})" );
			$sheet->setCellValue("K{$limite}" , "=SUM(K{$start}:K{$limite})" );
			$sheet->setCellValue("L{$limite}" , "=SUM(L{$start}:L{$limite})" );
			$sheet->setCellValue("M{$limite}" , "=SUM(M{$start}:M{$limite})" );
			$sheet->setCellValue("N{$limite}" , "=SUM(N{$start}:N{$limite})" );
			$sheet->setCellValue("O{$limite}" , "=SUM(O{$start}:O{$limite})" );
			$sheet->setCellValue("P{$limite}" , "=SUM(P{$start}:P{$limite})" );
			$sheet->setCellValue("Q{$limite}" , "=SUM(Q{$start}:Q{$limite})" );
			$sheet->setCellValue("R{$limite}" , "=SUM(R{$start}:R{$limite})" );
			$hoja++;
			$spreadsheet->getActiveSheet()->getStyle("A{$limite}:R{$limite}")->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
			$spreadsheet->getActiveSheet()->getStyle("A{$start}:R{$final}")->applyFromArray($styleCuerpoTabla);
		}
	// Fin para el libro de ingreso
	// <------------------------------------------------------------------------ EGRESOS -------------------------------------------------------------->







	// Documentos para el libro de Egresos
		// <--------------------------------- Parametros iniciales de la hoja de excel --------------------------------------------->
		$hoja = 1;
		$sheet = $spreadsheet->createSheet();
		$sheet->setTitle("Egresos {$year}");
		$spreadsheet->setActiveSheetIndex(1);
		$spreadsheet->getActiveSheet()->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
		$spreadsheet->getActiveSheet()->getPageSetup()->setScale(70);
		$spreadsheet->getActiveSheet()->getPageMargins()->setTop(1);
		$spreadsheet->getActiveSheet()->getPageMargins()->setRight(0.75);
		$spreadsheet->getActiveSheet()->getPageMargins()->setLeft(0.75);
		$spreadsheet->getActiveSheet()->getPageMargins()->setBottom(1);
		$sheet->setCellValue('A1', strtoupper($cliente['nombre']) );
		$sheet->setCellValue('L1', "HOJA {$hoja}" ); 
		$sheet->setCellValue('A2', strtoupper("Egresos correspondientes al mes de {$mes} del {$year}") ); 
		$spreadsheet->getActiveSheet()->mergeCells("A4:A5");
		$sheet->setCellValue("A4", "Factura" );
		$spreadsheet->getActiveSheet()->mergeCells("B4:B5");
		$sheet->setCellValue("B4", "Concepto" );
		$spreadsheet->getActiveSheet()->mergeCells("C4:C5");
		$sheet->setCellValue("C4", "No Deducibles" );
		$spreadsheet->getActiveSheet()->mergeCells("D4:E4");
		$sheet->setCellValue("D4", "Compras y Gastos" );
		$sheet->setCellValue("D5", "16%" );
		$sheet->setCellValue("E5", "0%" );
		$spreadsheet->getActiveSheet()->mergeCells("F4:G4");
		$sheet->setCellValue("F4", "Impuestos" );
		$sheet->setCellValue("F5", "Iva" );
		$sheet->setCellValue("G5", "Ieps" );
		$spreadsheet->getActiveSheet()->mergeCells("H4:J4");
		$sheet->setCellValue("H4", "Nomina" );
		$sheet->setCellValue("H5", "Subsidio" );
		$sheet->setCellValue("I5", "ISR" );
		$sheet->setCellValue("J5", "Otros" );
		$spreadsheet->getActiveSheet()->mergeCells("K4:L4");
		$sheet->setCellValue("K4", "Parcialidades" );
		$sheet->setCellValue("K5", "16 %" );
		$sheet->setCellValue("L5", "0 %" );
		$spreadsheet->getActiveSheet()->mergeCells("M4:M5");
		$sheet->setCellValue("M4", "Cuota Imss" );
		$spreadsheet->getActiveSheet()->mergeCells("N4:N5");
		$sheet->setCellValue("N4", "Pagos" );
		$spreadsheet->getActiveSheet()->mergeCells("O4:O5");
		$sheet->setCellValue("O4", "Dev. o Cred." );
		$spreadsheet->getActiveSheet()->mergeCells("P4:P5");
		$sheet->setCellValue("P4", "Total" );

		$spreadsheet->getActiveSheet()->getStyle("A4:P5")->applyFromArray($styleCabeceraTabla);

		// El siguiente codigo agrega a las filas que se ajusten al tamaño de su contenido
		$spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(47);
		// $spreadsheet->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
		$spreadsheet->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
		$spreadsheet->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
		$spreadsheet->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
		$spreadsheet->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
		$spreadsheet->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
		$spreadsheet->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);
		$spreadsheet->getActiveSheet()->getColumnDimension('I')->setAutoSize(true);
		$spreadsheet->getActiveSheet()->getColumnDimension('J')->setAutoSize(true);
		$spreadsheet->getActiveSheet()->getColumnDimension('K')->setAutoSize(true);
		$spreadsheet->getActiveSheet()->getColumnDimension('L')->setAutoSize(true);
		$spreadsheet->getActiveSheet()->getColumnDimension('M')->setAutoSize(true);
		$spreadsheet->getActiveSheet()->getColumnDimension('N')->setAutoSize(true);
		$spreadsheet->getActiveSheet()->getColumnDimension('P')->setAutoSize(true);
		$hoja = 1;
		$num = 6;
		$start = 6;
		$limite = 42;
		$rango = 42;
		
		$limite_superado = true;
		// ORDENAR ARRAY DE XMLS POR FACTURA
		$aux = [];
		foreach ($xmls_egreso as $key => $row) {
			$aux[$key] = (int)$row->Folio;
		}
		array_multisort($aux, SORT_ASC, $xmls_egreso);
		for ($i=0; $i < count($xmls_egreso) ; $i++) {
			if($num === $limite){
				// if ($hoja === 1) {
					$final = $limite  - 1;
					$spreadsheet->getActiveSheet()->getStyle("C{$start}:P{$limite}")->getNumberFormat()->setFormatCode('#,##0.00');
					# code...
					$sheet->setCellValue("B{$limite}" , "EGRESOS TOTALES" );
					$sheet->setCellValue("C{$limite}" , "=SUM(C{$start}:C{$limite})" );
					$sheet->setCellValue("D{$limite}" , "=SUM(D{$start}:D{$limite})" );
					$sheet->setCellValue("E{$limite}" , "=SUM(E{$start}:E{$limite})" );
					$sheet->setCellValue("F{$limite}" , "=SUM(F{$start}:F{$limite})" );
					$sheet->setCellValue("G{$limite}" , "=SUM(G{$start}:G{$limite})" );
					$sheet->setCellValue("H{$limite}" , "=SUM(H{$start}:H{$limite})" );
					$sheet->setCellValue("I{$limite}" , "=SUM(I{$start}:I{$limite})" );
					$sheet->setCellValue("J{$limite}" , "=SUM(J{$start}:J{$limite})" );
					$sheet->setCellValue("K{$limite}" , "=SUM(K{$start}:K{$limite})" );
					$sheet->setCellValue("L{$limite}" , "=SUM(L{$start}:L{$limite})" );
					$sheet->setCellValue("M{$limite}" , "=SUM(M{$start}:M{$limite})" );
					$sheet->setCellValue("N{$limite}" , "=SUM(N{$start}:N{$limite})" );
					$sheet->setCellValue("O{$limite}" , "=SUM(O{$start}:O{$limite})" );
					$sheet->setCellValue("O{$limite}" , "=SUM(P{$start}:P{$limite})" );
					$spreadsheet->getActiveSheet()->getStyle("A{$limite}:P{$limite}")->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
					$spreadsheet->getActiveSheet()->getStyle("A{$start}:P{$limite}")->applyFromArray($styleCuerpoTabla);
					$num++;
					$hoja++;
					$sheet->setCellValue("A{$num}", strtoupper($cliente['nombre']) );
					$sheet->setCellValue("H{$num}", "HOJA {$hoja}" );
					$num++;
					$sheet->setCellValue("A{$num}", strtoupper("Ingresos correspondientes al mes de {$mes} del {$year}") ); 
					$num++;
					$num++;
					$help = $num + 1;
					
					$spreadsheet->getActiveSheet()->mergeCells("A{$num}:A{$help}");
					$sheet->setCellValue("A{$num}", "Factura" );
					$spreadsheet->getActiveSheet()->mergeCells("B{$num}:B{$help}");
					$sheet->setCellValue("B{$num}", "Concepto" );
					$spreadsheet->getActiveSheet()->mergeCells("C{$num}:C{$help}");
					$sheet->setCellValue("C{$num}", "No Deducibles" );
					$spreadsheet->getActiveSheet()->mergeCells("D{$num}:E{$num}");
					$sheet->setCellValue("D{$num}", "Compras y Gastos" );
					$sheet->setCellValue("D{$help}", "16%" );
					$sheet->setCellValue("E{$help}", "0%" );
					$spreadsheet->getActiveSheet()->mergeCells("F{$num}:G{$num}");
					$sheet->setCellValue("F{$num}", "Impuestos" );
					$sheet->setCellValue("F{$help}", "Iva" );
					$sheet->setCellValue("G{$help}", "Ieps" );
					$spreadsheet->getActiveSheet()->mergeCells("H{$num}:J{$num}");
					$sheet->setCellValue("H{$num}", "Nomina" );
					$sheet->setCellValue("H{$help}", "Subsidio" );
					$sheet->setCellValue("I{$help}", "ISR" );
					$sheet->setCellValue("J{$help}", "Otros" );
					$spreadsheet->getActiveSheet()->mergeCells("K{$num}:L{$num}");
					$sheet->setCellValue("K{$num}", "Parcialidades" );
					$sheet->setCellValue("K{$help}", "16 %" );
					$sheet->setCellValue("L{$help}", "0 %" );
					$spreadsheet->getActiveSheet()->mergeCells("M{$num}:M{$help}");
					$sheet->setCellValue("M{$num}", "Cuota Imss" );
					$spreadsheet->getActiveSheet()->mergeCells("N{$num}:N{$help}");
					$sheet->setCellValue("N{$num}", "Pagos" );
					$spreadsheet->getActiveSheet()->mergeCells("O{$num}:O{$help}");
					$sheet->setCellValue("O{$num}", "Dev. o Cred" );
					$spreadsheet->getActiveSheet()->mergeCells("P{$num}:P{$help}");
					$sheet->setCellValue("P{$num}", "Total" );
					$spreadsheet->getActiveSheet()->getStyle("A{$num}:P{$help}")->applyFromArray($styleCabeceraTabla);
					$num++;
					$num++;
					$help = $num + 1;
					$start = $num;
					$datos = $limite;
					$sheet->setCellValue("B{$num}" , "SUMA ANTERIOR" );
					$sheet->setCellValue("C{$num}" , "=C{$datos}" );
					$sheet->setCellValue("D{$num}" , "=D{$datos}" );
					$sheet->setCellValue("E{$num}" , "=E{$datos}" );
					$sheet->setCellValue("F{$num}" , "=F{$datos}" );
					$sheet->setCellValue("G{$num}" , "=G{$datos}" );
					$sheet->setCellValue("H{$num}" , "=H{$datos}" );
					$sheet->setCellValue("I{$num}" , "=I{$datos}" );
					$sheet->setCellValue("J{$num}" , "=J{$datos}" );
					$sheet->setCellValue("K{$num}" , "=K{$datos}" );
					$sheet->setCellValue("L{$num}" , "=L{$datos}" );
					$sheet->setCellValue("M{$num}" , "=M{$datos}" );
					$sheet->setCellValue("N{$num}" , "=N{$datos}" );
					$sheet->setCellValue("O{$num}" , "=O{$datos}" );
					$sheet->setCellValue("P{$num}" , "=P{$datos}" );
					$num++;
					// <---------------------------------------Empezamos en la siguiente hoja ------------------------------------>
					$sheet->setCellValue("C{$num}" , 0 );
					$sheet->setCellValue("D{$num}" , 0 );
					$sheet->setCellValue("E{$num}" , 0 );
					$sheet->setCellValue("F{$num}" , 0 );
					$sheet->setCellValue("G{$num}" , 0 );
					$sheet->setCellValue("H{$num}" , 0 );
					$sheet->setCellValue("I{$num}" , 0 );
					$sheet->setCellValue("J{$num}" , 0 );
					$sheet->setCellValue("K{$num}" , 0 );
					$sheet->setCellValue("L{$num}" , 0 );
					$sheet->setCellValue("M{$num}" , 0 );
					$sheet->setCellValue("N{$num}" , 0 );
					$sheet->setCellValue("O{$num}" , 0 );
					$sheet->setCellValue("P{$num}" , 0 );
					// <------------------- Comenzamos --------------------->
					$sheet->setCellValue('A'.$num , "{$xmls_egreso[$i]->Folio}" );
					$sheet->setCellValue('B'.$num , mb_convert_case("{$xmls_egreso[$i]->Emisor->Nombre}", MB_CASE_TITLE, "UTF-8")  );
					// <----------Detectamos si es deducible
					if( $xmls_egreso[$i]->deducible === "si" ){
						if( $xmls_egreso[$i]->TipoDeComprobante === "ingreso"){
							$sheet->setCellValue('D'.$num , "{$xmls_egreso[$i]->SubTotal}" );
							$sheet->setCellValue('F'.$num , "{$xmls_egreso[$i]->Impuestos->TrasladoIVA}" );
						}
						if( $xmls_egreso[$i]->TipoDeComprobante === "I"){
							if( $xmls_egreso[$i]->MetodoPago === "PPD" ){
								//16%
								$sheet->setCellValue('K'.$num , "{$xmls_egreso[$i]->TotalGravado}" );
								//0%
								$sheet->setCellValue('L'.$num , "{$xmls_egreso[$i]->TotalExento}" );
								
							}else{
								//16%
								$sheet->setCellValue('D'.$num , "{$xmls_egreso[$i]->TotalGravado}" );
								//0%
								$sheet->setCellValue('E'.$num , "{$xmls_egreso[$i]->TotalExento}" );
							}
							$sheet->setCellValue('F'.$num , "{$xmls_egreso[$i]->Impuestos->TotalTrasladoIVA}" );
							$sheet->setCellValue('G'.$num , "{$xmls_egreso[$i]->Impuestos->TotalTrasladoIEPS}" );								
						}
						if( $xmls_egreso[$i]->TipoDeComprobante === "E" ){
							$sheet->setCellValue('O'.$num , "{$xmls_egreso[$i]->Total}" );
						}
						if($xmls_egreso[$i]->TipoDeComprobante === "P"){
							$Monto = 0;
							for ($s=0; $s < count($xmls_egreso[$i]->Complementos->Pagos) ; $s++) {
								$Monto = $Monto + $xmls_egreso[$i]->Complementos->Pagos[$s]->Monto;
							}
							$sheet->setCellValue('N'.$num , "{$Monto}" );
						}
						if( $xmls_egreso[$i]->TipoDeComprobante === "N" ){
							$sheet->setCellValue('H'.$num , "{$xmls_egreso[$i]->Complementos->Nomina->SubsidioImporte}" );
							$sheet->setCellValue('I'.$num , "{$xmls_egreso[$i]->Complementos->Nomina->TotalImpuestosRetenidos}" );
							$monto = 0;
							$monto = $xmls_egreso[$i]->Complementos->Nomina->TotalExento+$xmls_egreso[$i]->Complementos->Nomina->TotalGravado;
							$sheet->setCellValue('J'.$num , "{$monto}" );
						}
						$sheet->setCellValue('P'.$num , "=sum(C{$num}:H{$num})-I{$num}+sum(J{$num}:N{$num})" );
						// <------------ CUOTAS IMSS ------------------------------->
						$sheet->setCellValue('M'.$num , "{$xmls_egreso[$i]->Otros->CuotaImss}" );
						// <------------------------------ Detector de errores en estructura de XMLS ------------------------------>
						$sheet->setCellValue('Q'.$num , "{$xmls_egreso[$i]->Total}" );
					}else{
						$sheet->setCellValue('C'.$num , "{$xmls_egreso[$i]->Total}" );
					}
					$num++;
					$limite = $limite + $rango;
				// }else{
				// }
			}else{
				// <------------------------------------Comienzo de la hoja de calculo --------------------------------------------------------------->
				$sheet->setCellValue("C{$num}" , 0 );
				$sheet->setCellValue("D{$num}" , 0 );
				$sheet->setCellValue("E{$num}" , 0 );
				$sheet->setCellValue("F{$num}" , 0 );
				$sheet->setCellValue("G{$num}" , 0 );
				$sheet->setCellValue("H{$num}" , 0 );
				$sheet->setCellValue("I{$num}" , 0 );
				$sheet->setCellValue("J{$num}" , 0 );
				$sheet->setCellValue("K{$num}" , 0 );
				$sheet->setCellValue("L{$num}" , 0 );
				$sheet->setCellValue("M{$num}" , 0 );
				$sheet->setCellValue("N{$num}" , 0 );
				$sheet->setCellValue("O{$num}" , 0 );
				$sheet->setCellValue("P{$num}" , 0 );
				// <------------------- Comenzamos --------------------->
				$sheet->setCellValue('A'.$num , "{$xmls_egreso[$i]->Folio}" );
				$sheet->setCellValue('B'.$num , mb_convert_case("{$xmls_egreso[$i]->Emisor->Nombre}", MB_CASE_TITLE, "UTF-8")  );
				// <----------Detectamos si es deducible
				if( $xmls_egreso[$i]->deducible === "si" ){
					if( $xmls_egreso[$i]->TipoDeComprobante === "ingreso"){
						$sheet->setCellValue('D'.$num , "{$xmls_egreso[$i]->SubTotal}" );
						$sheet->setCellValue('F'.$num , "{$xmls_egreso[$i]->Impuestos->TrasladoIVA}" );
					}
					if( $xmls_egreso[$i]->TipoDeComprobante === "I" ){
						if( $xmls_egreso[$i]->MetodoPago === "PPD" ){
							//16%
							$sheet->setCellValue('K'.$num , "{$xmls_egreso[$i]->TotalGravado}" );
							//0%
							$sheet->setCellValue('L'.$num , "{$xmls_egreso[$i]->TotalExento}" );
						}else{
							//16%
							$sheet->setCellValue('D'.$num , "{$xmls_egreso[$i]->TotalGravado}" );
							//0%
							$sheet->setCellValue('E'.$num , "{$xmls_egreso[$i]->TotalExento}" );
						}
						$sheet->setCellValue('F'.$num , "{$xmls_egreso[$i]->Impuestos->TotalTrasladoIVA}" );
						$sheet->setCellValue('G'.$num , "{$xmls_egreso[$i]->Impuestos->TotalTrasladoIEPS}" );
					}
					if( $xmls_egreso[$i]->TipoDeComprobante === "E" ){
						$sheet->setCellValue('O'.$num , "{$xmls_egreso[$i]->Total}" );
					}
					if($xmls_egreso[$i]->TipoDeComprobante === "P"){
						$Monto = 0;
						for ($s=0; $s < count($xmls_egreso[$i]->Complementos->Pagos) ; $s++) {
							$Monto = $Monto + $xmls_egreso[$i]->Complementos->Pagos[$s]->Monto;
						}
						$sheet->setCellValue('N'.$num , "{$Monto}" );
					}
					if( $xmls_egreso[$i]->TipoDeComprobante === "N" ){
						$sheet->setCellValue('H'.$num , "{$xmls_egreso[$i]->Complementos->Nomina->SubsidioImporte}" );
						$sheet->setCellValue('I'.$num , "{$xmls_egreso[$i]->Complementos->Nomina->TotalImpuestosRetenidos}" );
						$monto = 0;
						$monto = $xmls_egreso[$i]->Complementos->Nomina->TotalExento+$xmls_egreso[$i]->Complementos->Nomina->TotalGravado;
						$sheet->setCellValue('J'.$num , "{$monto}" );
					}
					$sheet->setCellValue('P'.$num , "=sum(C{$num}:H{$num})-I{$num}+sum(J{$num}:N{$num})" );
					// <------------ CUOTAS IMSS ------------------------------->
					$sheet->setCellValue('M'.$num , "{$xmls_egreso[$i]->Otros->CuotaImss}" );
					// <------------------------------ Detector de errores en estructura de XMLS ------------------------------>
					$sheet->setCellValue('Q'.$num , "{$xmls_egreso[$i]->Total}" );
					
				}else{
					$sheet->setCellValue('C'.$num , "{$xmls_egreso[$i]->Total}" );
				}
				$num++;
			}
			if( $num < $limite ){
				$spreadsheet->getActiveSheet()->getStyle("C{$start}:P{$limite}")->getNumberFormat()->setFormatCode('#,##0.00');
				$final = $limite - 1;
				# code...
				$sheet->setCellValue("B{$limite}" , "INGRESOS TOTALES" );
				$sheet->setCellValue("C{$limite}" , "=SUM(C{$start}:C{$limite})" );
				$sheet->setCellValue("D{$limite}" , "=SUM(D{$start}:D{$limite})" );
				$sheet->setCellValue("E{$limite}" , "=SUM(E{$start}:E{$limite})" );
				$sheet->setCellValue("F{$limite}" , "=SUM(F{$start}:F{$limite})" );
				$sheet->setCellValue("G{$limite}" , "=SUM(G{$start}:G{$limite})" );
				$sheet->setCellValue("H{$limite}" , "=SUM(H{$start}:H{$limite})" );
				$sheet->setCellValue("I{$limite}" , "=SUM(I{$start}:I{$limite})" );
				$sheet->setCellValue("J{$limite}" , "=SUM(J{$start}:J{$limite})" );
				$sheet->setCellValue("K{$limite}" , "=SUM(K{$start}:K{$limite})" );
				$sheet->setCellValue("L{$limite}" , "=SUM(L{$start}:L{$limite})" );
				$sheet->setCellValue("M{$limite}" , "=SUM(M{$start}:M{$limite})" );
				$sheet->setCellValue("N{$limite}" , "=SUM(N{$start}:N{$limite})" );
				$sheet->setCellValue("O{$limite}" , "=SUM(O{$start}:O{$limite})" );
				$sheet->setCellValue("O{$limite}" , "=SUM(P{$start}:P{$limite})" );
				$hoja++;
				$spreadsheet->getActiveSheet()->getStyle("A{$limite}:P{$limite}")->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
	
				$spreadsheet->getActiveSheet()->getStyle("A{$start}:P{$final}")->applyFromArray($styleCuerpoTabla);
			}
			
		}
		
	// Fin para el libro de Egresos
	$spreadsheet->setActiveSheetIndex(0);
	$writer = new Xlsx($spreadsheet);
	$cliente['nombre'] =str_replace(' ', '', $cliente['nombre']);
	$nombre_archivo = "Ingresos_Egresos_{$mes}_{$year}".$cliente['nombre'];
	$name = $nombre_archivo.".xlsx";
	$carpeta = 'calculos' ;
	if (!file_exists($carpeta)) {
		mkdir($carpeta, 0777, true);
	}
	$writer->save("{$carpeta}/{$name}");
	$sql = "INSERT INTO documento 
	(idusuario,idcliente,nombre_archivo,mes,year)
	VALUES
	(:idusuario,:idcliente,:nombre_archivo,:mes,:year)";
	try{
        // Get DB Object
        $db = new db();
        // Connect
        $db = $db->connect();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':idusuario', $idusuario);
        $stmt->bindParam(':idcliente', $idcliente);
        $stmt->bindParam(':nombre_archivo', $nombre_archivo);
        $stmt->bindParam(':mes', $mes);
        $stmt->bindParam(':year', $year);
        $stmt->execute();
    } catch(PDOException $e){
        $mensaje = array(
            'status' => false,
            'mensaje' => 'Error al guardar documento',
            'error' => $e->getMessage()
        );
        echo json_encode($mensaje);
        return;
    }
	$mensaje = array(
		'status' => true,
		'mensaje' => 'Archivo creado',
		'rest' => $nombre_archivo
	);
	echo json_encode($mensaje);
});
function ordenarFecha($a,$b){
	return strtotime($a->Fecha) - strtotime($b->Fecha);
}
function ordenarNumFactura($a,$b){
	return (int)$a - (int)b;
}
function obtenerMes($mes){
	switch ($mes) {
		case 1:
			return 'Enero';
		case 2:
			return 'Febrero';
		case 3:
			return 'Marzo';
		case 4:
			return 'Abril';
		case 5:
			return 'Mayo';
		case 6:
			return 'Junio';
		case 7:
			return 'Julio';
		case 8:
			return 'Agosto';
		case 9:
			return 'Septiembre';
		case 10:
			return 'Octubre';
		case 11:
			return 'Noviembre';
		case 12:
			return 'Diciembre';
	}
}