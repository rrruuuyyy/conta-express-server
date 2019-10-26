<?php
	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;
	require '../vendor/autoload.php';
	use PhpOffice\PhpSpreadsheet\Spreadsheet;
	use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
	//Obtener todos los xmls por parametros de busqueda
	$app->post('/api/xmls_info/todo', function(Request $request, Response $response){
		$idusuario = $request->getParam('idusuario');
		$idcliente = $request->getParam('idcliente');
    	$token = $request->getParam('token');
    	$mes = $request->getParam('mes');
    	$year = $request->getParam('year');
    	$tipo = $request->getParam('tipo');
    	$consulta = "SELECT * FROM `docto-xml` WHERE idusuario='$idusuario'AND idcliente='$idcliente' AND MONTH(Fecha)= '$mes' AND conta='$tipo'";
	    try{
	        // Instanciar la base de datos
	        $db = new db();

	        // Conexión
	        $db = $db->connect();
	        $ejecutar = $db->query($consulta);
	        $xmls = $ejecutar->fetchAll(PDO::FETCH_OBJ);
	        $db = null;
	        // Bucle donde se convierte a JSON
	        for ($i=0; $i < count($xmls) ; $i++) { 
	        	$xmls[$i]->Complementos = json_decode($xmls[$i]->Complementos);
	        	$xmls[$i]->Conceptos = json_decode($xmls[$i]->Conceptos);
	        	$xmls[$i]->Emisor = json_decode($xmls[$i]->Emisor);
	        	$xmls[$i]->Impuestos = json_decode($xmls[$i]->Impuestos);
	        	$xmls[$i]->Receptor = json_decode($xmls[$i]->Receptor);
	        	$xmls[$i]->Otros = json_decode($xmls[$i]->Otros);
	        	$xmls[$i]->UUIDS_relacionados = json_decode($xmls[$i]->UUIDS_relacionados);
	        }
	        //Exportar y mostrar en formato JSON
	        $mensaje = array(
	            'status' => true,
	            'mensaje' => 'Xmls cargados',
	            'rest' => $xmls
	        );
	        return json_encode($mensaje);
	    } catch(PDOException $e){
	        $mensaje = array(
	            'status' => false,
	            'mensaje' => 'Xmls no cargados',
	            'error' => $e->getMessage()
	        );
	        return json_encode($mensaje);
	    }
    	echo json_encode($mes);
	});
	//Obtener todo el detallle de un xml
	$app->post('/api/xml_info/one', function(Request $request, Response $response){
    	$token = $request->getParam('token');
    	$xml = $request->getParam('xml');
    	$xml = json_encode($xml);
    	$xml = json_decode($xml);
    	$hoy = date("Y/m/d");
    	$xmls_relacionados = [];
    	//Detectamos que tipo de xml nos estan enviando
    	// <----------------------------------------- XML DE TIPO INGRESO -------------------------------------------------->
    	if( $xml->TipoDeComprobante === 'I'){
    		$consulta = "SELECT * FROM `docto-xml` WHERE idusuario='$xml->idusuario' ";
    		try{
		        // Instanciar la base de datos
		        $db = new db();

		        // Conexión
		        $db = $db->connect();
		        $ejecutar = $db->query($consulta);
		        $xmls = $ejecutar->fetchAll(PDO::FETCH_OBJ);
		        $db = null;
		        // Bucle donde se convierte a JSON
		        for ($i=0; $i < count($xmls) ; $i++) { 
		        	$xmls[$i]->Complementos = json_decode($xmls[$i]->Complementos);
		        	$xmls[$i]->Conceptos = json_decode($xmls[$i]->Conceptos);
		        	$xmls[$i]->Emisor = json_decode($xmls[$i]->Emisor);
		        	$xmls[$i]->Impuestos = json_decode($xmls[$i]->Impuestos);
		        	$xmls[$i]->Receptor = json_decode($xmls[$i]->Receptor);
		        	$xmls[$i]->Otros = json_decode($xmls[$i]->Otros);
		        	$xmls[$i]->UUIDS_relacionados = json_decode($xmls[$i]->UUIDS_relacionados);
		        }
		        // Buscamos dentro de los xml una que este relacionada a esta factura
		        for ($i=0; $i < count($xmls) ; $i++) {
		        	if( $xmls[$i]->TipoDeComprobante === 'P' ){
		        		for ($j=0; $j < count( $xmls[$i]->Complementos->Pagos ) ; $j++) {
		        			for ($k=0; $k < count( $xmls[$i]->Complementos->Pagos[$j]->documentos ) ; $k++) { 
		        				if( $xmls[$i]->Complementos->Pagos[$j]->documentos[$k]->IdDocumento === $xml->UUID ){
		        					array_push($xmls_relacionados, $xmls[$i]);
		        				}
		        			}
		        		}
		        	}else{
			        	for ($j=0; $j < count($xmls[$i]) ; $j++) {
			        		if( $xmls[$i]->UUIDS_relacionados[$j] === $xml->UUID ){
			        			array_push($xmls_relacionados, $xmls[$i]);
			        		}
			        	}
		        	}
		        }
		        //Exportar y mostrar en formato JSON
		        $mensaje = array(
		            'status' => true,
		            'mensaje' => 'Xmls cargados',
		            'rest' => $xmls_relacionados
		        );
		        return json_encode($mensaje);
		    } catch(PDOException $e){
		        $mensaje = array(
		            'status' => false,
		            'mensaje' => 'Xmls no cargados',
		            'error' => $e->getMessage()
		        );
		        return json_encode($mensaje);
		    }
    	}
	});
	//Obtener todo el detallle de un xml
	$app->post('/api/xml_info/uuid', function(Request $request, Response $response){
		$token = $request->getParam('token');
    	$uuid = $request->getParam('uuid');
    	$consulta = "SELECT * FROM `docto-xml` WHERE UUID='$uuid' ";
	    try{
	        // Instanciar la base de datos
	        $db = new db();

	        // Conexión
	        $db = $db->connect();
	        $ejecutar = $db->query($consulta);
	        $xml = $ejecutar->fetch(PDO::FETCH_OBJ);
	        $db = null;
	        
	        $xml->Complementos = json_decode($xml->Complementos);
	        $xml->Conceptos = json_decode($xml->Conceptos);
	        $xml->Emisor = json_decode($xml->Emisor);
	        $xml->Impuestos = json_decode($xml->Impuestos);
	        $xml->Receptor = json_decode($xml->Receptor);
	        $xml->Otros = json_decode($xml->Otros);
	        $xml->UUIDS_relacionados = json_decode($xml->UUIDS_relacionados);
	        //Exportar y mostrar en formato JSON
	        $xmls_relacionados = [];
	    	//Detectamos que tipo de xml nos estan enviando
	    	// <----------------------------------------- XML DE TIPO INGRESO -------------------------------------------------->
	    	if( $xml->TipoDeComprobante === 'I'){
	    		$consulta = "SELECT * FROM `docto-xml` WHERE idusuario='$xml->idusuario' ";
	    		try{
			        // Instanciar la base de datos
			        $db = new db();

			        // Conexión
			        $db = $db->connect();
			        $ejecutar = $db->query($consulta);
			        $xmls = $ejecutar->fetchAll(PDO::FETCH_OBJ);
			        $db = null;
			        // Bucle donde se convierte a JSON
			        for ($i=0; $i < count($xmls) ; $i++) { 
			        	$xmls[$i]->Complementos = json_decode($xmls[$i]->Complementos);
			        	$xmls[$i]->Conceptos = json_decode($xmls[$i]->Conceptos);
			        	$xmls[$i]->Emisor = json_decode($xmls[$i]->Emisor);
			        	$xmls[$i]->Impuestos = json_decode($xmls[$i]->Impuestos);
			        	$xmls[$i]->Receptor = json_decode($xmls[$i]->Receptor);
			        	$xmls[$i]->Otros = json_decode($xmls[$i]->Otros);
			        	$xmls[$i]->UUIDS_relacionados = json_decode($xmls[$i]->UUIDS_relacionados);
			        }
			        // Buscamos dentro de los xml una que este relacionada a esta factura
			        for ($i=0; $i < count($xmls) ; $i++) {
			        	if( $xmls[$i]->TipoDeComprobante === 'P' ){
			        		for ($j=0; $j < count( $xmls[$i]->Complementos->Pagos ) ; $j++) {
			        			for ($k=0; $k < count( $xmls[$i]->Complementos->Pagos[$j]->documentos ) ; $k++) { 
			        				if( $xmls[$i]->Complementos->Pagos[$j]->documentos[$k]->IdDocumento === $xml->UUID ){
			        					array_push($xmls_relacionados, $xmls[$i]);
			        				}
			        			}
			        		}
			        	}else{
				        	for ($j=0; $j < count($xmls[$i]) ; $j++) {
				        		if( $xmls[$i]->UUIDS_relacionados[$j] === $xml->UUID ){
				        			array_push($xmls_relacionados, $xmls[$i]);
				        		}
				        	}
			        	}
			        }
			        //Exportar y mostrar en formato JSON
			        $xml->relaciones = $xmls_relacionados;
			        $mensaje = array(
			            'status' => true,
			            'mensaje' => 'Xmls cargados',
			            'rest' => $xml
			        );
			        return json_encode($mensaje);
			    } catch(PDOException $e){
			        $mensaje = array(
			            'status' => false,
			            'mensaje' => 'Xmls no cargados',
			            'error' => $e->getMessage()
			        );
			        return json_encode($mensaje);
			    }
	    	}
	    	if( $xml->TipoDeComprobante === 'P' ){
	    		$uuids_padre = [];
	    		for ($i=0; $i < count( $xml->Complementos->Pagos[$i] ) ; $i++) { 
	    			for ($j=0; $j < count( $xml->Complementos->Pagos[$i]->documentos ); $j++) {
	    				array_push( $uuids_padre, $xml->Complementos->Pagos[$i]->documentos[$j]->IdDocumento );
	    			}
	    		}
	    		$uuids_padre = array_unique($uuids_padre);
	    		$facturas = [];
	    		$mensaje = array(
			            'status' => true,
			            'mensaje' => 'Xmls PADRE',
			            'rest' => $xml
			        );
			        return json_encode($uuids_padre);
	    	}
	    } catch(PDOException $e){
	        $mensaje = array(
	            'status' => false,
	            'mensaje' => 'Xml no cargados',
	            'error' => $e->getMessage()
	        );
	        return json_encode($mensaje);
	    }
	});

function obtenerInfoPadre($idusuario,$uuids){
	$xml_padre;
	$xmls_padre = [];
	if( is_string($uuids) ){
		$consulta = "SELECT * FROM `docto-xml` WHERE UUID='$uuid' ";
		try{
	        // Instanciar la base de datos
	        $db = new db();

	        // Conexión
	        $db = $db->connect();
	        $ejecutar = $db->query($consulta);
	        $xml = $ejecutar->fetch(PDO::FETCH_OBJ);
	        $db = null;
	        
	        $xml->Complementos = json_decode($xml->Complementos);
	        $xml->Conceptos = json_decode($xml->Conceptos);
	        $xml->Emisor = json_decode($xml->Emisor);
	        $xml->Impuestos = json_decode($xml->Impuestos);
	        $xml->Receptor = json_decode($xml->Receptor);
	        $xml->Otros = json_decode($xml->Otros);
	        $xml->UUIDS_relacionados = json_decode($xml->UUIDS_relacionados);
	        $xml_padre = $xml;

	    } catch(PDOException $e){
	        $mensaje = array(
	            'status' => false,
	            'mensaje' => 'Xml no cargados desde busqueda padre',
	            'error' => $e->getMessage()
	        );
	        return json_encode($mensaje);
	    }
	}else{
		for ($i=0; $i < count($uuids) ; $i++) { 
			$consulta = "SELECT * FROM `docto-xml` WHERE UUID='$uuids[$i]' ";
			try{
		        // Instanciar la base de datos
		        $db = new db();

		        // Conexión
		        $db = $db->connect();
		        $ejecutar = $db->query($consulta);
		        $xml = $ejecutar->fetch(PDO::FETCH_OBJ);
		        $db = null;
		        
		        $xml->Complementos = json_decode($xml->Complementos);
		        $xml->Conceptos = json_decode($xml->Conceptos);
		        $xml->Emisor = json_decode($xml->Emisor);
		        $xml->Impuestos = json_decode($xml->Impuestos);
		        $xml->Receptor = json_decode($xml->Receptor);
		        $xml->Otros = json_decode($xml->Otros);
		        $xml->UUIDS_relacionados = json_decode($xml->UUIDS_relacionados);

		        array_push($xmls_padre, $xml);

		    } catch(PDOException $e){
		        $mensaje = array(
		            'status' => false,
		            'mensaje' => 'Xmls no cargados desde busqueda padres',
		            'error' => $e->getMessage()
		        );
		        return json_encode($mensaje);
		    }
		}
	}
	$consulta = "SELECT * FROM `docto-xml` WHERE idusuario='$xml->idusuario' ";
	try{
		// Instanciar la base de datos
		$db = new db();
		// Conexión
		$db = $db->connect();
		$ejecutar = $db->query($consulta);
		$xmls = $ejecutar->fetchAll(PDO::FETCH_OBJ);
		$db = null;
		// Bucle donde se convierte a JSON
        for ($i=0; $i < count($xmls) ; $i++) { 
        	$xmls[$i]->Complementos = json_decode($xmls[$i]->Complementos);
        	$xmls[$i]->Conceptos = json_decode($xmls[$i]->Conceptos);
        	$xmls[$i]->Emisor = json_decode($xmls[$i]->Emisor);
        	$xmls[$i]->Impuestos = json_decode($xmls[$i]->Impuestos);
        	$xmls[$i]->Receptor = json_decode($xmls[$i]->Receptor);
        	$xmls[$i]->Otros = json_decode($xmls[$i]->Otros);
        	$xmls[$i]->UUIDS_relacionados = json_decode($xmls[$i]->UUIDS_relacionados);
        }
        if( is_string($uuids) ){
        	$xmls_relacionados = [];
        	// Buscamos dentro de los xml una que este relacionada a esta factura
	        for ($i=0; $i < count($xmls) ; $i++) {
	        	if( $xmls[$i]->TipoDeComprobante === 'P' ){
	        		for ($j=0; $j < count( $xmls[$i]->Complementos->Pagos ) ; $j++) {
	        			for ($k=0; $k < count( $xmls[$i]->Complementos->Pagos[$j]->documentos ) ; $k++) { 
	        				if( $xmls[$i]->Complementos->Pagos[$j]->documentos[$k]->IdDocumento === $xml->UUID ){
	        					array_push($xmls_relacionados, $xmls[$i]);
	        				}
	        			}
	        		}
	        	}else{
		        	for ($j=0; $j < count($xmls[$i]) ; $j++) {
		        		if( $xmls[$i]->UUIDS_relacionados[$j] === $xml->UUID ){
		        			array_push($xmls_relacionados, $xmls[$i]);
		        		}
		        	}
	        	}
	        }
	        $xml_padre->relaciones = $xmls_relacionados;
	        return $xml_padre;
        }else{
        	for ($o=0; $o < count($xmls_padre) ; $o++) { 
        		$xmls_relacionados = [];
	        	// Buscamos dentro de los xml una que este relacionada a esta factura
		        for ($i=0; $i < count($xmls) ; $i++) {
		        	if( $xmls[$i]->TipoDeComprobante === 'P' ){
		        		for ($j=0; $j < count( $xmls[$i]->Complementos->Pagos ) ; $j++) {
		        			for ($k=0; $k < count( $xmls[$i]->Complementos->Pagos[$j]->documentos ) ; $k++) { 
		        				if( $xmls[$i]->Complementos->Pagos[$j]->documentos[$k]->IdDocumento === $xmls_padre[$o]->UUID ){
		        					array_push($xmls_relacionados, $xmls[$i]);
		        				}
		        			}
		        		}
		        	}else{
			        	for ($j=0; $j < count($xmls[$i]) ; $j++) {
			        		if( $xmls[$i]->UUIDS_relacionados[$j] === $xml->UUID ){
			        			array_push($xmls_relacionados, $xmls[$i]);
			        		}
			        	}
		        	}
		        }
		        $xmls_padre->relaciones = $xmls_relacionados;
		        return $xmls_padre;
        	}
        }
    } catch(PDOException $e){
        $mensaje = array(
            'status' => false,
            'mensaje' => 'Xmls no cargados',
            'error' => $e->getMessage()
        );
        return json_encode($mensaje);
    }
}