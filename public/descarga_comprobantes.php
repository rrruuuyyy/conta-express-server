<?php
    $nombre_archivo = $_GET['nombre_archivo'];
    $token = $_GET['token'];
    copy("docs/{$nombre_archivo}.pdf","Comprobante.pdf");
    // Creamos un instancia de la clase ZipArchive
    // $zip = new ZipArchive();
    // // Creamos y abrimos un archivo zip temporal
    // $zip->open("comprobantes.zip",ZipArchive::CREATE);
    // $zip->addFromString("Comprobante.pdf",$pdf);
    // $zip->close();
    header("Content-disposition: attachment; filename=Comprobante.pdf","Comprobante.pdf");
    header("Content-type: application/pdf");
    readfile("Comprobante.pdf","Comprobante.pdf");
    // Por último eliminamos el archivo temporal creado
    unlink('Comprobante.pdf');//Destruye el archivo temporal
?>