<?php
    $nombre_archivos = json_decode($_GET('nombre_archivos'));
    $token = json_decode($_GET('token'));
    // Creamos un instancia de la clase ZipArchive
    $zip = new ZipArchive();
    // Creamos y abrimos un archivo zip temporal
    $zip->open("comprobantes.zip",ZipArchive::CREATE);
    // Añadimos un directorio
    $zip->addEmptyDir($dir);
    for ($i=0; $i < count($nombre_archivos) ; $i++) { 
        # code...
        $zip->addFile("../../public/docs/{$nombre_archivos[$i]}.pdf","Comprobante{$i}.pdf");
    }
    $zip->close();
    // Creamos las cabezeras que forzaran la descarga del archivo como archivo zip.
    header("Content-type: application/octet-stream");
    header("Content-disposition: attachment; filename=comprobantes.zip");
    // leemos el archivo creado
    readfile('comprobantes.zip');
    // Por último eliminamos el archivo temporal creado
    unlink('comprobantes.zip');//Destruye el archivo temporal
?>