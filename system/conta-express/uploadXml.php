<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require("../../src/config/xml_info.php");
$tablas_xml = [];
for ($i=0; $i < count($_FILES['file']['name']) ; $i++) { 
    # code...
    $xml = $_FILES['file']['tmp_name'][$i];
    $info = new infoXML();
    $info = $info->obtener($xml);
    array_push($tablas_xml,$info);
}
echo json_encode($tablas_xml);
?>