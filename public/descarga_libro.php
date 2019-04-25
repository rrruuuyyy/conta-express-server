<?php
require '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    $nombre_archivo = $_GET['nombre_archivo'];
    $nombre_archivo =str_replace(' ', '', $nombre_archivo);
    // $token = $_GET['token'];
    copy("calculos/{$nombre_archivo}.xlsx","{$nombre_archivo}.xlsx");
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load("{$nombre_archivo}.xlsx");
    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xlsx");
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="Libro.xlsx"');
    $writer->save("php://output");
    unlink("{$nombre_archivo}.xlsx");
?>