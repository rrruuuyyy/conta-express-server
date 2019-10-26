<?php
	use \Psr\Http\Message\ServerRequestInterface as Request;
	use \Psr\Http\Message\ResponseInterface as Response;

	$app->post('/api/proveedores_cliente/get', function(Request $request, Response $response){
		$idcliente = $request->getParam('idcliente');
		$token = $request->getParam('token');
	    //  Verificacion del token
	    // $auth = new Auth();
	    // $token = $auth->Check($token);
	    // if($token['status'] === false){
	    //     echo json_encode($token);
	    //     return;
	    // }
	    // Fin Verificacion
	    $sql_clientes = "SELECT * FROM proveedor_cliente WHERE idcliente = '$idcliente' ";
		try{
	        // Instanciar la base de datos
	        $db = new db();

	        // Conexión
	        $db = $db->connect();
	        $ejecutar = $db->query($sql_clientes);
	        $proveedores = $ejecutar->fetchAll(PDO::FETCH_OBJ);
	        $db = null;
	        $mensaje = array(
	            'status' => true,
	            'mensaje' => 'Proveedores cargados',
	            'rest' => $proveedores
	        );
	        return json_encode($mensaje);
	    } catch(PDOException $e){
	        $mensaje = array(
	            'status' => false,
	            'mensaje' => 'Erro al cargar los proveedores',
	            'error' => $e->getMessage()
	        );
	        return json_encode($mensaje);
	    }	
	});
	$app->post('/api/proveedores_cliente/news', function(Request $request, Response $response){
		$idcliente = $request->getParam('idcliente');
		$token = $request->getParam('token');
		$tipo = $request->getParam('tipo');
		$xmls = json_decode($request->getParam('xmls'));
	    //  Verificacion del token
	    // $auth = new Auth();
	    // $token = $auth->Check($token);
	    // if($token['status'] === false){
	    //     echo json_encode($token);
	    //     return;
	    // }
	    // Fin Verificacion
	    if( $tipo === 'egreso' ){
			$sql_clientes = "SELECT * FROM proveedor_cliente WHERE idcliente = '$idcliente' ";
			try{
		        // Instanciar la base de datos
		        $db = new db();

		        // Conexión
		        $db = $db->connect();
		        $ejecutar = $db->query($sql_clientes);
		        $proveedores = $ejecutar->fetchAll(PDO::FETCH_OBJ);
		        $db = null;
		        for ($i=0; $i < count($xmls); $i++) { 
		        	$xmls[$i]->Emisor->Rfc = strtoupper($xmls[$i]->Emisor->Rfc);
		        	$xmls[$i]->Emisor->Rfc = str_replace(' ','',$xmls[$i]->Emisor->Rfc);
		        }
		        //<----------------------Borramos proveedores duplicados ---------------------------------------->
		        $xmls = quitarProveedoresRepetidos($xmls);
		        //<---------------- Comenzamos a verificar cada xml si el proveedor ya existe ------------------->
		        $no_repetidos = [];
		        $proveedores_guardados = [];
		        $errores = false;
		        for ($i=0; $i < count($xmls); $i++) {
		        	$guardado = false;
		        	for ($j=0; $j < count($proveedores); $j++) {
		        		if($xmls[$i]->estado != "error"){
		        			if( $xmls[$i]->Emisor->Rfc === $proveedores[$j]->rfc ){
		        				$guardado = true;
		        			}	
		        		}
		        	}
		        	if( $guardado === false ){
	        			$sql_guardado = "INSERT INTO proveedor_cliente (idcliente,rfc,nombre) VALUES (:idcliente,:rfc,:nombre)";
	        			try{
					        // Get DB Object
					        $db = new db();
					        // Connect
					        $db = $db->connect();
					        $stmt = $db->prepare($sql_guardado);
					        $stmt->bindParam(':idcliente', $idcliente);
					        $stmt->bindParam(':rfc', $xmls[$i]->Emisor->Rfc);
					        $stmt->bindParam(':nombre', $xmls[$i]->Emisor->Nombre);
					        $stmt->execute();

					    } catch(PDOException $e){
					    	$errores = true;
					        $mensaje = array(
					            'status' => false,
					            'mensaje' => 'Cliente no creado',
					            'Proveedor' => $xmls[$i],
					            'error' => $e->getMessage()
					        );
					        array_push($proveedores_guardados, $mensaje);
					    }
	        		}
		        }
		        if($errores === true){
		        	$mensaje = array(
			            'status' => false,
			            'mensaje' => 'Proveedores guardados con errores',
			            'rest' => $proveedores_guardados
			        );
			        return json_encode($mensaje);
		        }else{
		        	$mensaje = array(
			            'status' => true,
			            'mensaje' => 'Proveedores guardados',
			            'rest' => ''
			        );
			        return json_encode($mensaje);
		        }
		        
		    } catch(PDOException $e){
		        $mensaje = array(
		            'status' => false,
		            'mensaje' => 'Erro al cargar los proveedores',
		            'error' => $e->getMessage()
		        );
		        return json_encode($mensaje);
		    }
		}
		if( $tipo === 'ingreso' ){
			$sql_clientes = "SELECT * FROM cliente_cliente WHERE idcliente = '$idcliente' ";
			try{
		        // Instanciar la base de datos
		        $db = new db();

		        // Conexión
		        $db = $db->connect();
		        $ejecutar = $db->query($sql_clientes);
		        $clientes = $ejecutar->fetchAll(PDO::FETCH_OBJ);
		        $db = null;
		        for ($i=0; $i < count($xmls); $i++) { 
		        	$xmls[$i]->Receptor->Rfc = strtoupper($xmls[$i]->Receptor->Rfc);
		        	$xmls[$i]->Receptor->Rfc = str_replace(' ','',$xmls[$i]->Receptor->Rfc);
		        }
		        //<----------------------Borramos clientes duplicados ---------------------------------------->
		        $xmls = quitarclientesRepetidos($xmls);
		        //<---------------- Comenzamos a verificar cada xml si el proveedor ya existe ------------------->
		        $no_repetidos = [];
		        $clientes_guardados = [];
		        $errores = false;
		        for ($i=0; $i < count($xmls); $i++) {
		        	$guardado = false;
		        	for ($j=0; $j < count($clientes); $j++) {
		        		if($xmls[$i]->estado != "error"){
		        			if( $xmls[$i]->Receptor->Rfc === $clientes[$j]->rfc ){
		        				$guardado = true;
		        			}	
		        		}
		        	}
		        	if( $guardado === false ){
	        			$sql_guardado = "INSERT INTO cliente_cliente (idcliente,rfc,nombre) VALUES (:idcliente,:rfc,:nombre)";
	        			try{
					        // Get DB Object
					        $db = new db();
					        // Connect
					        $db = $db->connect();
					        $stmt = $db->prepare($sql_guardado);
					        $stmt->bindParam(':idcliente', $idcliente);
					        $stmt->bindParam(':rfc', $xmls[$i]->Receptor->Rfc);
					        $stmt->bindParam(':nombre', $xmls[$i]->Receptor->Nombre);
					        $stmt->execute();

					    } catch(PDOException $e){
					    	$errores = true;
					        $mensaje = array(
					            'status' => false,
					            'mensaje' => 'Cliente no creado',
					            'Proveedor' => $xmls[$i],
					            'error' => $e->getMessage()
					        );
					        array_push($clientes_guardados, $mensaje);
					    }
	        		}
		        }
		        if($errores === true){
		        	$mensaje = array(
			            'status' => false,
			            'mensaje' => 'Clientes guardados con errores',
			            'rest' => $clientes_guardados
			        );
			        return json_encode($mensaje);
		        }else{
		        	$mensaje = array(
			            'status' => true,
			            'mensaje' => 'Clientes guardados',
			            'rest' => ''
			        );
			        return json_encode($mensaje);
		        }
		        
		    } catch(PDOException $e){
		        $mensaje = array(
		            'status' => false,
		            'mensaje' => 'Erro al cargar los clientes',
		            'error' => $e->getMessage()
		        );
		        return json_encode($mensaje);
		    }
		}	
	});

	function quitarProveedoresRepetidos($array) {
	    $temp_array = array();
	    $i = 0;
	    $key_array = array();
	   
	    foreach($array as $val) {
	        if (!in_array($val->Emisor->Rfc, $key_array)) {
	            $key_array[$i] = $val->Emisor->Rfc;
	            array_push($temp_array, $val);
	            //$temp_array[$i] = $val;
	        }
	        $i++;
	    }
	    return $temp_array;
	}
	function quitarClientesRepetidos($array) {
	    $temp_array = array();
	    $i = 0;
	    $key_array = array();
	   
	    foreach($array as $val) {
	        if (!in_array($val->Receptor->Rfc, $key_array)) {
	            $key_array[$i] = $val->Receptor->Rfc;
	            array_push($temp_array, $val);
	            //$temp_array[$i] = $val;
	        }
	        $i++;
	    }
	    return $temp_array;
	}