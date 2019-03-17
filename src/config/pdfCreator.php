<?php
require '../vendor/autoload.php';
use Spipu\Html2Pdf\Html2Pdf;
use Spipu\Html2Pdf\Exception\Html2PdfException;
use Spipu\Html2Pdf\Exception\ExceptionFormatter;
use Dompdf\Dompdf;
use Mpdf\Mpdf;
class pdfCreator{
	public static function PDF_cobro($cobro,$folios,$hoy){
		$mpdf = new \Mpdf\Mpdf();
		$html = "";

		ob_start(); 
        require_once '../src/plantillas/sanmarig/plantilla.php';
		$html=ob_get_clean();
		// $html = file_get_contents
		
		$stylesheet = file_get_contents('../src/plantillas/sanmarig/plantilla.css');
		$mpdf->WriteHTML($stylesheet,1);
		$mpdf->WriteHTML($html,2);
		$nombre = new idCreator();
		$nombre = $nombre->generar();
		$carpeta = 'docs' ;
		if (!file_exists($carpeta)) {
			mkdir($carpeta, 0777, true);
		}
		$rutaPDF = "docs/{$nombre}.pdf";
		$mpdf->Output($rutaPDF,'F');
		$html = "";
		$mpdf = null;
		return $nombre;
	}
}