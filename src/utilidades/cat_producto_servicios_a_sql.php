<?php
    class db{
        // Variables de conexión
        private $host = 'localhost';
        private $usuario = 'root';
        private $password = '';
        private $base = 'catalogo_sat';
        

        // Conectar a BD
        public function connect(){
            $conexion_mysql = "mysql:host=$this->host;dbname=$this->base";
            $conexionDB = new PDO($conexion_mysql, $this->usuario, $this->password,[]);
            $conexionDB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            //Esta linea arregla la codificacion sino no aparecen en la salida en JSON quedan NULL
            $conexionDB -> exec("set names utf8");
            return $conexionDB;
        }
    }
    set_time_limit(3000);
    $url = "https://raw.githubusercontent.com/bambucode/catalogos_sat_JSON/master/c_ClaveProdServ.json";
    $json = file_get_contents($url);
    $productos_servicios = json_decode( $json );
    // LOS PRIMERO ES BORRAR LA TABLA
    $sql = "TRUNCATE TABLE producto_servicio";
    try{
        // Instanciar la base de datos
        $db = new db();
        // Conexión
        $db = $db->connect();
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $db = null;

    } catch(PDOException $e){
        $mensaje = array(
            'status' => false,
            'mensaje' => 'Nose pudo borrar la base de datps',
            'error' => $e->getMessage()
        );
        echo json_encode($mensaje);
        return;
    }
    for ($i=0; $i < count($productos_servicios); $i++) { 
        # code...
        // AQUI GUARDAMOS TODO EL CATALOGO A NUESTRA BASE DE DATOS
        $sql = "INSERT INTO producto_servicio (id,descripcion,estimuloFranjaFronteriza,palabrasSimilares) 
        VALUES (:id,:descripcion,:estimuloFranjaFronteriza,:palabrasSimilares)";
        try{
            // Get DB Object
            $db = new db();
            // Connect
            $db = $db->connect();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $productos_servicios[$i]->id);
            $stmt->bindParam(':descripcion', $productos_servicios[$i]->descripcion);
            $stmt->bindParam(':estimuloFranjaFronteriza', $productos_servicios[$i]->estimuloFranjaFronteriza);
            $stmt->bindParam(':palabrasSimilares', $productos_servicios[$i]->palabrasSimilares);
            $stmt->execute();
            
    
        } catch(PDOException $e){
            $mensaje = array(
                'status' => false,
                'mensaje' => 'Error al guardar producto o servicio',
                'error' => $e->getMessage()
            );
            echo json_encode($mensaje);
            return;
        }
    }
    echo 'Todos los productos y servicios se guardaron correctamente';
?>