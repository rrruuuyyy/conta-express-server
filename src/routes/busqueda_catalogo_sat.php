<?php
    use \Psr\Http\Message\ServerRequestInterface as Request;
    use \Psr\Http\Message\ResponseInterface as Response;
    //Obtener todos los clientes
    $app->post('/api/busqueda_catalogo/producto_servicio', function(Request $request, Response $response){
        $busqueda = $request->getParam('busqueda');
        //  Verificacion del token
        // $auth = new Auth();
        // $token = $auth->Check($token);
        // if($token['status'] === false){
        //     echo json_encode($token);
        //     return;
        // }
        // Fin Verificacion 
        $consulta = "SELECT * FROM producto_servicio WHERE id LIKE '%{$busqueda}%' OR descripcion LIKE '%{$busqueda}%' OR palabrasSimilares LIKE '%{$busqueda}%' ";
        try{
            // Instanciar la base de datos
            $db = new db_sat();

            // Conexión
            $db = $db->connect();
            $ejecutar = $db->query($consulta);
            $clientes = $ejecutar->fetchAll(PDO::FETCH_OBJ);
            $db = null;

            //Exportar y mostrar en formato JSON
            $mensaje = array(
                'status' => true,
                'mensaje' => 'Catalogo productos y servicios cargados',
                'rest' => $clientes
            );
            echo json_encode($mensaje);
            return;
        } catch(PDOException $e){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Error al cargar productos y servicios',
                'error' => $e->getMessage()
            );
            echo json_encode($mensaje);
            return;
        }
    });
    $app->post('/api/busqueda_catalogo/unidades_medida', function(Request $request, Response $response){
        $busqueda = $request->getParam('busqueda');
        $consulta = "SELECT * FROM clave_unidad WHERE id LIKE '%{$busqueda}%' OR nombre LIKE '%{$busqueda}%' ";
        try{
            // Instanciar la base de datos
            $db = new db_sat();

            // Conexión
            $db = $db->connect();
            $ejecutar = $db->query($consulta);
            $unidades = $ejecutar->fetchAll(PDO::FETCH_OBJ);
            $db = null;

            //Exportar y mostrar en formato JSON
            $mensaje = array(
                'status' => true,
                'mensaje' => 'Catalogo undades de medida cargados',
                'rest' => $unidades
            );
            echo json_encode($mensaje);
            return;
        } catch(PDOException $e){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Error al cargar unidades de medida',
                'error' => $e->getMessage()
            );
            echo json_encode($mensaje);
            return;
        }
    });