<?php
namespace Laurensmedia\Productdesigner\Helper;

class Tcpdfhelper
{
	function getPdfObject($baseDir){
		require_once($baseDir.'tcpdf/tcpdf.php');
		
		$pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		return $pdf;
	}
	
}