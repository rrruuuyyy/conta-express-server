<?php

    class infoXML{
        public static function obtener($archivo_xml){
            $CFDI = (object)[];
            $CFDI->Receptor = (object)[];
            $CFDI->Emisor = (object)[];
            $CFDI->Impuestos = (object)[];
            $CFDI->Complementos = (object)[];
            $CFDI->Otros = (object)[];
            $Conceptos = [];
            $UUID_vinculados = [];
            $xml = simplexml_load_file( $archivo_xml );
            // if( $xml === false ){
            //     return false;
            // }           
            $ns = $xml->getNamespaces(true);
            $xml->registerXPathNamespace('c', $ns['cfdi']);
            //Detectamos si es version 3.3 o 3.2
            foreach ($xml->xpath('//cfdi:Comprobante') as $cfdiComprobante){ 
                $CFDI->Version = "{$cfdiComprobante['Version']}"; 
                $CFDI->Version2 = "{$cfdiComprobante['version']}"; 
            }
            if($CFDI->Version === "3.3"){
                //EMPIEZO A LEER LA INFORMACION DEL CFDI
                foreach ($xml->xpath('//cfdi:Comprobante') as $cfdiComprobante){ 
                    $CFDI->Version = "{$cfdiComprobante['Version']}";  
                    $CFDI->Fecha = "{$cfdiComprobante['Fecha']}";  
                    $CFDI->Sello = "{$cfdiComprobante['Sello']}";  
                    $CFDI->Total = "{$cfdiComprobante['Total']}";  
                    $CFDI->SubTotal = "{$cfdiComprobante['SubTotal']}";  
                    $CFDI->Certificado = "{$cfdiComprobante['Certificado']}";  
                    $CFDI->FormaPago = "{$cfdiComprobante['FormaPago']}";  
                    $CFDI->NoCertificado = "{$cfdiComprobante['NoCertificado']}";  
                    $CFDI->TipoDeComprobante = "{$cfdiComprobante['TipoDeComprobante']}";  
                    $CFDI->LugarExpedicion = "{$cfdiComprobante['LugarExpedicion']}";  
                    $CFDI->Serie = "{$cfdiComprobante['Serie']}";  
                    $CFDI->Folio = "{$cfdiComprobante['Folio']}";  
                    $CFDI->MetodoPago = "{$cfdiComprobante['MetodoPago']}";  
                    $CFDI->Moneda = "{$cfdiComprobante['Moneda']}";  
                    $CFDI->Descuento = "{$cfdiComprobante['Descuento']}";  
                }
                $CFDI->Fecha = str_replace("T"," ",$CFDI->Fecha);
                foreach ($xml->xpath('/cfdi:Comprobante/cfdi:CfdiRelacionados') as $cfdiTipoRelacion){ 
                    $CFDI->UUID_tipo_relacion = "{$cfdiTipoRelacion['TipoRelacion']}";
                }
                foreach ($xml->xpath('/cfdi:Comprobante/cfdi:CfdiRelacionados/cfdi:CfdiRelacionado') as $cfdiRelacionado){
                    array_push($UUID_vinculados, "{$cfdiRelacionado['UUID']}");
                }
                $CFDI->UUIDS_relacionados = $UUID_vinculados;

                    // OBTENCION GENERAL DE VARIABLES GLOBALES DENTRO DE LA FACTURA

                foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Emisor') as $Emisor){ 
                    $CFDI->Emisor->Rfc = "{$Emisor['Rfc']}";              
                    $CFDI->Emisor->Nombre = "{$Emisor['Nombre']}";
                    $CFDI->Emisor->RegimenFiscal = "{$Emisor['RegimenFiscal']}";              
                } 
                foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Receptor') as $Receptor){ 
                    $CFDI->Receptor->Rfc = "{$Receptor['Rfc']}";              
                    $CFDI->Receptor->Nombre = "{$Receptor['Nombre']}";              
                    $CFDI->Receptor->UsoCFDI = "{$Receptor['UsoCFDI']}";              
                }
                // Variables para los impuestos
                //Impuestos Base
                $TotalBase16 = 0.0;
                $TotalBase0 = 0.0;
                $TotalBaseIEPS = 0.0;
                $RetencionISR = 0;
                $RetencionIVA = 0;
                $RetencionIEPS = 0;
                $TrasladoIVA = 0;
                $TrasladoIEPS = 0;
                foreach ($xml->xpath('/cfdi:Comprobante/cfdi:Conceptos/cfdi:Concepto') as $Concepto){
                    $impuestos = [];      
                    $concepto = null;
                    $concepto['ClaveProdServ'] = "{$Concepto['ClaveProdServ']}";
                    $concepto['Descuento'] = "{$Concepto['Descuento']}";
                    $concepto['Cantidad'] = "{$Concepto['Cantidad']}";
                    $concepto['ClaveUnidad'] = "{$Concepto['ClaveUnidad']}";
                    $concepto['Unidad'] = "{$Concepto['Unidad']}";
                    $concepto['Descripcion'] = "{$Concepto['Descripcion']}";
                    $concepto['ValorUnitario'] = "{$Concepto['ValorUnitario']}";
                    $concepto['Importe'] = "{$Concepto['Importe']}";
                    $con = $Concepto->asXML();
                    $con = str_replace("cfdi:","",$con);
                    $con = str_replace("terceros:","",$con);
                    $con = simplexml_load_string($con);
                    // Bucle para obtener los Traslados del producto
                    foreach ($con->xpath('/Concepto/Impuestos/Traslados/Traslado') as $Traslado){
                        $traslado = null; 
                        $traslado['Importe'] = "{$Traslado['Importe']}";
                        $traslado['Base'] = (float)$concepto['Importe'] - (float)$concepto['Descuento'];
                        $traslado['Impuesto'] = "{$Traslado['Impuesto']}";
                        $traslado['TipoFactor'] = "{$Traslado['TipoFactor']}";
                        $traslado['TasaOCuota'] = "{$Traslado['TasaOCuota']}";
                        if($traslado['Impuesto'] === "002" ){
                            $TrasladoIVA = $TrasladoIVA + $traslado['Importe'];   
                        }
                        if($traslado['Impuesto'] === "003" ){
                            $TrasladoIEPS = $TrasladoIEPS + $traslado['Importe'];
                            $TotalBaseIEPS = TotalBaseIEPS + $traslado['Base'];
                        }
                        array_push($impuestos, $traslado);
                    }
                    // Bucle para obtener Retenciones del producto
                    foreach ($con->xpath('/Concepto/Impuestos/Retenciones/Retencion') as $Retencion){
                        $retencion = null; 
                        $retencion['Importe'] = "{$Retencion['Importe']}";
                        $retencion['Base'] = (float)$concepto['Importe'] - (float)$concepto['Descuento'];
                        $retencion['Impuesto'] = "{$Retencion['Impuesto']}";
                        $retencion['TipoFactor'] = "{$Retencion['TipoFactor']}";
                        $retencion['TasaOCuota'] = "{$Retencion['TasaOCuota']}";
                        if( $retencion['Impuesto'] === "001" ){
                            $RetencionISR = $RetencionISR + $retencion['Importe'];
                        }
                        if( $retencion['Impuesto'] === "002" ){
                            $RetencionIVA = $RetencionIVA + $retencion['Importe'];
                        }
                        if( $retencion['Impuesto'] === "003" ){
                            $RetencionIEPS = $RetencionIEPS + $retencion['Importe'];
                        }
                        array_push($impuestos, $retencion);
                    }
                    // Bucle para impuestos a Terceros
                    foreach ($con->xpath('/Concepto/ComplementoConcepto/PorCuentadeTerceros/Impuestos/Traslados/Traslado') as $TerceroTraslado){
                        $tasa = (float)"{$TerceroTraslado['tasa']}";
                        if($tasa === 0.0){
                            $TotalBase0 = $TotalBase0 + (float)$concepto['Importe'] - (float)$concepto['Descuento'];
                        }
                        if($tasa === 0.16){
                            $TotalBase16 = $TotalBase16 + (float)$concepto['Importe'] - (float)$concepto['Descuento'];
                        }
                    }
                    // Fin de bucles
                    $concepto['Impuestos'] = $impuestos;
                    array_push($Conceptos,$concepto);
                }
                // Guardamos la informacion de los impuestos en la seccion de Impuestos
                $CFDI->Impuestos->TotalTrasladoIVA = $TrasladoIVA;
                $CFDI->Impuestos->TotalTrasladoIEPS = $TrasladoIEPS;
                $CFDI->Impuestos->TotalRetencionISR = $RetencionISR;
                $CFDI->Impuestos->TotalRetencionIVA = $RetencionIVA;
                $CFDI->Impuestos->TotalRetencionIEPS = $RetencionIEPS;
                

                $CFDI->Conceptos = $Conceptos;
                $xml->registerXPathNamespace('t', $ns['tfd']);
                foreach ($xml->xpath('/cfdi:Comprobante/cfdi:Complemento/t:TimbreFiscalDigital') as $cfdiComplemento){
                    $CFDI->UUID = "{$cfdiComplemento['UUID']}";
                    $CFDI->SelloCFD = "{$cfdiComplemento['SelloCFD']}";
                    $CFDI->SelloSAT = "{$cfdiComplemento['SelloSAT']}";
                    $CFDI->NoCertificadoSAT = "{$cfdiComplemento['NoCertificadoSAT']}";
                    $CFDI->RfcProvCertif = "{$cfdiComplemento['RfcProvCertif']}";
                    $CFDI->FechaTimbrado = "{$cfdiComplemento['FechaTimbrado']}";
                }

                $CuotasImss = 0;
                for ($i=0; $i < count($CFDI->Conceptos) ; $i++) { 
                    if($CFDI->Conceptos[$i]['ClaveProdServ'] === "85101701"){
                        $CuotasImss = $CuotasImss + (float)$CFDI->Conceptos[$i]['Importe'] - (float)$CFDI->Conceptos[$i]['Descuento'];
                    }
                }
                $CFDI->Otros->CuotaImss = $CuotasImss;

                    // FIN DE OBTENCION DE VARIABLES GLOBALES EN LA FACTURA
                    
                if($CFDI->TipoDeComprobante === 'I' or $CFDI->TipoDeComprobante === 'E'){
                        // <-----------------------------Obtenemos manualmente el valor del ingreso por clientes --------------------------->
                        // Continuacion con las variables de abajo
                        // $TotalBase16 = 0.0;
                        // $TotalBase0 = 0.0;
                        for ($l=0; $l < count($CFDI->Conceptos) ; $l++) {
                            if(count($CFDI->Conceptos[$l]['Impuestos']) === 0){
                                $TotalBase0 = $TotalBase0 + (float)$CFDI->Conceptos[$l]['Importe'];
                            }
                            for ($x=0; $x < count($CFDI->Conceptos[$l]['Impuestos']) ; $x++) {
                                if( $CFDI->Conceptos[$l]['Impuestos'][$x]['Impuesto'] === "003" ){
                                    
                                }
                                if( $CFDI->Conceptos[$l]['Impuestos'][$x]['Impuesto'] === "002" ){
                                    $base16 = (float)$CFDI->Conceptos[$l]['Impuestos'][$x]['TasaOCuota'];
                                    if( $base16 === .16 ){
                                        $TotalBase16 = $TotalBase16 + (float)$CFDI->Conceptos[$l]['Impuestos'][$x]['Base'];
                                    }
                                    if( $CFDI->Conceptos[$l]['Impuestos'][$x]['TasaOCuota'] === "0.000000"){
                                        $TotalBase0 = $TotalBase0 + (float)$CFDI->Conceptos[$l]['Impuestos'][$x]['Base'];
                                    }
                                    if( $CFDI->Conceptos[$l]['Impuestos'][$x]['TasaOCuota'] === ""){
                                        $TotalBase0 = $TotalBase0 + (float)$CFDI->Conceptos[$l]['Impuestos'][$x]['Base'];
                                    }
                                }
                            }
                        }
                        $CFDI->TotalGravado = $TotalBase16;
                        $CFDI->TotalExento = $TotalBase0;
                        $CFDI->TotalIEPS = $TotalBaseIEPS;
                    // <------------------------------------------- IMPUESTOS Y OTROS ------------------------------------------------------>
                        foreach ($xml->xpath('/cfdi:Comprobante/cfdi:Impuestos') as $Imp){
                            $CFDI->TotalImpuestosRetenidos = "{$Imp['TotalImpuestosRetenidos']}";
                            $CFDI->TotalImpuestosTrasladados = "{$Imp['TotalImpuestosTrasladados']}";
                        }
                        
                    // <------------------------------------------- FIN IMPUESTOS Y OTROS ------------------------------------------------------>
                    // IMPUESTOS LOCALES
                    $TotaldeTraslados = 0;
                    $TotaldeRetenciones = 0;
                    error_reporting(0);
                    foreach ($xml->xpath('/cfdi:Comprobante/cfdi:Complemento/implocal:ImpuestosLocales') as $loc){
                        $TotaldeTraslados = "{$loc['TotaldeTraslados']}";
                        $TotaldeRetenciones = "{$loc['TotaldeRetenciones']}";
                    }
                    $CFDI->Impuestos->TotaldeTrasladosLoc = $TotaldeTraslados;
                    $CFDI->Impuestos->TotaldeRetencionesLoc = $TotaldeRetenciones;
                    
                }
                if($CFDI->TipoDeComprobante === 'N'){
                    $nomina = (object)[];
                    $nomina->receptor = (object)[];
                    $nomina->emisor = (object)[];
                    
                    $xml->registerXPathNamespace('c', $ns['cfdi']);
                    $xml->registerXPathNamespace('n', $ns['nomina12']);
                    foreach ($xml->xpath('/c:Comprobante/c:Complemento/n:Nomina') as $n){
                        $nomina->FechaInicialPago = "{$n['FechaInicialPago']}";
                        $nomina->FechaFinalPago = "{$n['FechaFinalPago']}";
                        $nomina->FechaPago = "{$n['FechaPago']}";
                        $nomina->NumDiasPagados = "{$n['NumDiasPagados']}";
                    }
                    foreach ($xml->xpath('/c:Comprobante/c:Complemento/n:Nomina/n:Receptor') as $nomina_receptor){
                        $nomina->receptor->Curp = "{$nomina_receptor['Curp']}";
                        $nomina->receptor->NumSeguridadSocial = "{$nomina_receptor['NumSeguridadSocial']}";
                        $nomina->receptor->FechaInicioRelLaboral = "{$nomina_receptor['FechaInicioRelLaboral']}";
                        $nomina->receptor->Antiguedad = "{$nomina_receptor['Antigüedad']}";
                        $nomina->receptor->TipoContrato = "{$nomina_receptor['TipoContrato']}";
                        $nomina->receptor->Sindicalizado = "{$nomina_receptor['Sindicalizado']}";
                        $nomina->receptor->TipoJornada = "{$nomina_receptor['TipoJornada']}";
                        $nomina->receptor->TipoRegimen = "{$nomina_receptor['TipoRegimen']}";
                        $nomina->receptor->NumEmpleado = "{$nomina_receptor['NumEmpleado']}";
                        $nomina->receptor->Departamento = "{$nomina_receptor['Departamento']}";
                        $nomina->receptor->Puesto = "{$nomina_receptor['Puesto']}";
                        $nomina->receptor->RiesgoPuesto = "{$nomina_receptor['RiesgoPuesto']}";
                        $nomina->receptor->PeriodicidadPago = "{$nomina_receptor['PeriodicidadPago']}";
                        $nomina->receptor->Banco = "{$nomina_receptor['Banco']}";
                        $nomina->receptor->CuentaBancaria = "{$nomina_receptor['CuentaBancaria']}";
                        $nomina->receptor->SalarioBaseCotApor = "{$nomina_receptor['SalarioBaseCotApor']}";
                        $nomina->receptor->SalarioDiarioIntegrado = "{$nomina_receptor['SalarioDiarioIntegrado']}";
                        $nomina->receptor->ClaveEntFed = "{$nomina_receptor['ClaveEntFed']}";
                    }
                    foreach ($xml->xpath('/c:Comprobante/c:Complemento/n:Nomina/n:Emisor') as $e){
                        $nomina->emisor->Curp = "{$e['Curp']}";
                        $nomina->emisor->RegistroPatronal = "{$e['RegistroPatronal']}";
                    }
                    $nomina_conceptos = [];
                    // Obtenemos las variables de deducciones, percepciones otros
                    $TotalSueldos = 0;
                    $TotalSeparacionIndemizacion = 0;
                    $TotalJubilacionPensionRetiro = 0;
                    $TotalGravado = 0;
                    $TotalExento = 0;
                    foreach ($xml->xpath('/c:Comprobante/c:Complemento/n:Nomina/n:Percepciones') as $t){
                        $TotalSueldos = $TotalSueldos + $t['TotalSueldos'];
                        $TotalSeparacionIndemizacion = $TotalSeparacionIndemizacion + $t['TotalSeparacionIndemizacion'];
                        $TotalJubilacionPensionRetiro = $TotalJubilacionPensionRetiro + $t['TotalJubilacionPensionRetiro'];
                        $TotalGravado = $TotalGravado + $t['TotalGravado'];
                        $TotalExento = $TotalExento + $t['TotalExento'];
                    }
                    $nomina->TotalSueldos = $TotalSueldos;
                    $nomina->TotalSeparacionIndemizacion = $TotalSeparacionIndemizacion;
                    $nomina->TotalJubilacionPensionRetiro = $TotalJubilacionPensionRetiro;
                    $nomina->TotalGravado = $TotalGravado;
                    $nomina->TotalExento = $TotalExento;
                    // FIN
                    foreach ($xml->xpath('/c:Comprobante/c:Complemento/n:Nomina/n:Percepciones/n:Percepcion') as $Concepto){
                        $concepto = null;
                        $concepto['tipo'] = "{$Concepto['TipoPercepcion']}";
                        $concepto['Clave'] = "{$Concepto['Clave']}";
                        $concepto['Concepto'] = "{$Concepto['Concepto']}";
                        $concepto['ImporteGravado'] = "{$Concepto['ImporteGravado']}";
                        $concepto['ImporteExento'] = "{$Concepto['ImporteExento']}";
                        $concepto['info'] = "percepcion";
                        array_push($nomina_conceptos,$concepto);
                    }
                    $TotalOtrasDeducciones = 0;
                    $TotalImpuestosRetenidos = 0;
                    foreach ($xml->xpath('/c:Comprobante/c:Complemento/n:Nomina/n:Deducciones') as $c){
                        $TotalOtrasDeducciones = $TotalOtrasDeducciones + $c['TotalOtrasDeducciones'];
                        $TotalImpuestosRetenidos = $TotalImpuestosRetenidos + $c['TotalImpuestosRetenidos'];
                    }
                    $nomina->TotalOtrasDeducciones = $TotalOtrasDeducciones;
                    $nomina->TotalImpuestosRetenidos = $TotalImpuestosRetenidos;
                    foreach ($xml->xpath('/c:Comprobante/c:Complemento/n:Nomina/n:Deducciones/n:Deduccion') as $Concepto){
                        $concepto = null;
                        $concepto['tipo'] = "{$Concepto['TipoDeduccion']}";
                        $concepto['Clave'] = "{$Concepto['Clave']}";
                        $concepto['Concepto'] = "{$Concepto['Concepto']}";
                        $concepto['Importe'] = "{$Concepto['Importe']}";
                        $concepto['info'] = "deduccion";
                        array_push($nomina_conceptos,$concepto);
                    }
                    $SubsidioCausado = 0;
                    $SubsidioImporte = 0;
                    foreach ($xml->xpath('/c:Comprobante/c:Complemento/n:Nomina/n:OtrosPagos/n:OtroPago') as $Concepto){
                        $concepto = null;
                        $concepto['tipo'] = "{$Concepto['TipoOtroPago']}";
                        $concepto['Clave'] = "{$Concepto['Clave']}";
                        $concepto['Concepto'] = "{$Concepto['Concepto']}";
                        $concepto['Importe'] = "{$Concepto['Importe']}";
                        $concepto['info'] = "otro";
                        if($concepto['tipo'] === "002"){
                            foreach ($xml->xpath('/c:Comprobante/c:Complemento/n:Nomina/n:OtrosPagos/n:OtroPago/n:SubsidioAlEmpleo') as $sub){
                                $SubsidioCausado = "{$sub['SubsidioCausado']}";
                            }
                            $SubsidioImporte = "{$Concepto['Importe']}";
                        }
                        array_push($nomina_conceptos,$concepto);
                    }
                    $nomina->SubsidioCausado = $SubsidioCausado;           
                    $nomina->SubsidioImporte = $SubsidioImporte;           
                    $nomina->conceptos = $nomina_conceptos;
                    $CFDI->Complementos->Nomina = $nomina;

                }
                $TotalPagos = 0;
                if($CFDI->TipoDeComprobante === "P"){
                    // Variables para pago
                    $xml_pagos = (object)[];
                    $pagos = [];
                    error_reporting(0);
                    $xml->registerXPathNamespace('c', $ns['cfdi']);
                    $xml->registerXPathNamespace('p', $ns['pago10']);
                    $p="";
                    $pagos_separados = [];
                    foreach ($xml->xpath('/c:Comprobante/c:Complemento/p:Pagos/p:Pago') as $p){
                        $pago = (object)[];
                        $pago->FechaPago = "{$p['FechaPago']}";
                        $pago->FormaDePagoP = "{$p['FormaDePagoP']}";
                        $pago->MonedaP = "{$p['MonedaP']}";
                        $pago->TipoCambioP = "{$p['TipoCambioP']}";
                        $pago->Monto = "{$p['Monto']}";
                        $pago->NumOperacion = "{$p['NumOperacion']}";
                        $pago->RfcEmisorCtaOrd = "{$p['RfcEmisorCtaOrd']}";
                        $pago->NomBancoOrdExt = "{$p['NomBancoOrdExt']}";
                        $pago->CtaOrdenante = "{$p['CtaOrdenante']}";
                        $pago->RfcEmisorCtaBen = "{$p['RfcEmisorCtaBen']}";
                        $pago->CtaBeneficiario = "{$p['CtaBeneficiario']}";
                        $pago->TipoCadPago = "{$p['TipoCadPago']}";
                        $pago->CertPago = "{$p['CertPago']}";
                        $pago->CadPago = "{$p['CadPago']}";
                        $pago->SelloPago = "{$p['SelloPago']}";
                        $pago->documentos = [];
                        array_push($pagos,$pago);
                        array_push($pagos_separados, $p->asXML() );
                        $TotalPagos = $TotalPagos + $pago->Monto;
                    }
                    if($TotalPagos === 0){
                        foreach ($xml->xpath('/c:Comprobante/c:Complemento') as $p){
                            $pago = (object)[];
                            $pago->FechaPago = "{$p->Pagos->Pago['FechaPago']}";
                            $pago->FormaDePagoP = "{$p->Pagos->Pago['FormaDePagoP']}";
                            $pago->MonedaP = "{$p->Pagos->Pago['MonedaP']}";
                            $pago->TipoCambioP = "{$p->Pagos->Pago['TipoCambioP']}";
                            $pago->Monto = "{$p->Pagos->Pago['Monto']}";
                            $pago->NumOperacion = "{$p->Pagos->Pago['NumOperacion']}";
                            $pago->RfcEmisorCtaOrd = "{$p->Pagos->Pago['RfcEmisorCtaOrd']}";
                            $pago->NomBancoOrdExt = "{$p->Pagos->Pago['NomBancoOrdExt']}";
                            $pago->CtaOrdenante = "{$p->Pagos->Pago['CtaOrdenante']}";
                            $pago->RfcEmisorCtaBen = "{$p->Pagos->Pago['RfcEmisorCtaBen']}";
                            $pago->CtaBeneficiario = "{$p->Pagos->Pago['CtaBeneficiario']}";
                            $pago->TipoCadPago = "{$p->Pagos->Pago['TipoCadPago']}";
                            $pago->CertPago = "{$p->Pagos->Pago['CertPago']}";
                            $pago->CadPago = "{$p->Pagos->Pago['CadPago']}";
                            $pago->SelloPago = "{$p->Pagos->Pago['SelloPago']}";
                            $pago->documentos = [];
                            array_push($pagos,$pago);
                            array_push($pagos_separados, $p->asXML() );
                            $TotalPagos = $TotalPagos + $pago->Monto;
                        }
                    }
                    for ($i=0; $i < count($pagos_separados) ; $i++) {
                        
                        $pagos_separados[$i] = str_replace("pago10:","",$pagos_separados[$i]);
                        $pago_int = simplexml_load_string($pagos_separados[$i]);
                        $documentos = [];
                        foreach ($pago_int->xpath('/Pago/DoctoRelacionado') as $docto){
                            $doc = (object)[];
                            $doc->IdDocumento = "{$docto['IdDocumento']}";
                            $doc->Serie = "{$docto['Serie']}";
                            $doc->Folio = "{$docto['Folio']}";
                            $doc->MonedaDR = "{$docto['MonedaDR']}";
                            $doc->TipoCambioDR = "{$docto['TipoCambioDR']}";
                            $doc->MetodoDePagoDR = "{$docto['MetodoDePagoDR']}";
                            $doc->NumParcialidad = "{$docto['NumParcialidad']}";
                            $doc->ImpSaldoAnt = "{$docto['ImpSaldoAnt']}";
                            $doc->ImpPagado = "{$docto['ImpPagado']}";
                            $doc->ImpSaldoInsoluto = "{$docto['ImpSaldoInsoluto']}";
                            array_push($documentos, $doc);  
                        }
                        $pagos[$i]->documentos = $documentos;
                        $CFDI->Complementos->Pagos = $pagos;
                    }
                    
                    if ( $CFDI->Complementos == new stdClass() ){
                        $xml->registerXPathNamespace('p', $ns['ns2']);
                        $p="";
                        $pagos_separados = [];
                        foreach ($xml->xpath('/c:Comprobante/c:Complemento/p:Pagos/p:Pago') as $p){
                            $pago = (object)[];
                            $pago->FechaPago = "{$p['FechaPago']}";
                            $pago->FormaDePagoP = "{$p['FormaDePagoP']}";
                            $pago->MonedaP = "{$p['MonedaP']}";
                            $pago->TipoCambioP = "{$p['TipoCambioP']}";
                            $pago->Monto = "{$p['Monto']}";
                            $pago->NumOperacion = "{$p['NumOperacion']}";
                            $pago->RfcEmisorCtaOrd = "{$p['RfcEmisorCtaOrd']}";
                            $pago->NomBancoOrdExt = "{$p['NomBancoOrdExt']}";
                            $pago->CtaOrdenante = "{$p['CtaOrdenante']}";
                            $pago->RfcEmisorCtaBen = "{$p['RfcEmisorCtaBen']}";
                            $pago->CtaBeneficiario = "{$p['CtaBeneficiario']}";
                            $pago->TipoCadPago = "{$p['TipoCadPago']}";
                            $pago->CertPago = "{$p['CertPago']}";
                            $pago->CadPago = "{$p['CadPago']}";
                            $pago->SelloPago = "{$p['SelloPago']}";
                            $pago->documentos = [];
                            array_push($pagos,$pago);
                            array_push($pagos_separados, $p->asXML() );
                            $TotalPagos = $TotalPagos + $pago->Monto;
                        }
                        for ($i=0; $i < count($pagos_separados) ; $i++) {
                            
                            $pagos_separados[$i] = str_replace("ns2:","",$pagos_separados[$i]);
                            $pago_int = simplexml_load_string($pagos_separados[$i]);
                            $documentos = [];
                            foreach ($pago_int->xpath('/Pago/DoctoRelacionado') as $docto){
                                $doc = (object)[];
                                $doc->IdDocumento = "{$docto['IdDocumento']}";
                                $doc->Serie = "{$docto['Serie']}";
                                $doc->Folio = "{$docto['Folio']}";
                                $doc->MonedaDR = "{$docto['MonedaDR']}";
                                $doc->TipoCambioDR = "{$docto['TipoCambioDR']}";
                                $doc->MetodoDePagoDR = "{$docto['MetodoDePagoDR']}";
                                $doc->NumParcialidad = "{$docto['NumParcialidad']}";
                                $doc->ImpSaldoAnt = "{$docto['ImpSaldoAnt']}";
                                $doc->ImpPagado = "{$docto['ImpPagado']}";
                                $doc->ImpSaldoInsoluto = "{$docto['ImpSaldoInsoluto']}";
                                array_push($documentos, $doc);  
                            }
                            $pagos[$i]->documentos = $documentos;
                            $CFDI->Complementos->Pagos = $pagos;
                        }
                    }
                    $CFDI->Complementos->Pagos->TotalPagos = $TotalPagos;
                }
            }
            if($CFDI->Version2 === "3.2"){
                //EMPIEZO A LEER LA INFORMACION DEL CFDI
                foreach ($xml->xpath('//cfdi:Comprobante') as $cfdiComprobante){ 
                    $CFDI->Version = "{$cfdiComprobante['version']}";  
                    $CFDI->Fecha = "{$cfdiComprobante['fecha']}";  
                    $CFDI->Sello = "{$cfdiComprobante['sello']}";  
                    $CFDI->Total = "{$cfdiComprobante['total']}";  
                    $CFDI->SubTotal = "{$cfdiComprobante['subTotal']}";  
                    $CFDI->Certificado = "{$cfdiComprobante['certificado']}";  
                    $CFDI->FormaPago = "{$cfdiComprobante['formaDePago']}";  
                    $CFDI->NoCertificado = "{$cfdiComprobante['noCertificado']}";  
                    $CFDI->TipoDeComprobante = "{$cfdiComprobante['tipoDeComprobante']}";  
                    $CFDI->LugarExpedicion = "{$cfdiComprobante['LugarExpedicion']}";  
                    $CFDI->Serie = "{$cfdiComprobante['serie']}";  
                    $CFDI->Folio = "{$cfdiComprobante['folio']}";  
                    $CFDI->MetodoPago = "{$cfdiComprobante['metodoDePago']}";  
                    $CFDI->Moneda = "{$cfdiComprobante['Moneda']}";  
                    $CFDI->Descuento = "{$cfdiComprobante['descuento']}";  
                }
                $CFDI->Fecha = str_replace("T"," ",$CFDI->Fecha);
                foreach ($xml->xpath('/cfdi:Comprobante/cfdi:CfdiRelacionados') as $cfdiTipoRelacion){ 
                    $CFDI->UUID_tipo_relacion = "{$cfdiTipoRelacion['TipoRelacion']}";
                }
                foreach ($xml->xpath('/cfdi:Comprobante/cfdi:CfdiRelacionados/cfdi:CfdiRelacionado') as $cfdiRelacionado){
                    array_push($UUID_vinculados, "{$cfdiRelacionado['UUID']}");
                }
                $CFDI->UUIDS_relacionados = $UUID_vinculados;

                    // OBTENCION GENERAL DE VARIABLES GLOBALES DENTRO DE LA FACTURA

                foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Emisor') as $Emisor){ 
                    $CFDI->Emisor->Rfc = "{$Emisor['rfc']}";              
                    $CFDI->Emisor->Nombre = "{$Emisor['nombre']}";            
                } 
                foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Emisor//cfdi:RegimenFiscal') as $Emisor){
                    $CFDI->Emisor->RegimenFiscal = "{$Emisor['Regimen']}";              
                } 
                foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Receptor') as $Receptor){ 
                    $CFDI->Receptor->Rfc = "{$Receptor['rfc']}";              
                    $CFDI->Receptor->Nombre = "{$Receptor['nombre']}";              
                    $CFDI->Receptor->UsoCFDI = "{$Receptor['UsoCFDI']}";              
                }
                // Variables para los impuestos
                //Impuestos Base
                $TotalBase16 = 0.0;
                $TotalBase0 = 0.0;
                $TotalBaseIEPS = 0.0;
                $RetencionISR = 0;
                $RetencionIVA = 0;
                $RetencionIEPS = 0;
                $TrasladoIVA = 0;
                $TrasladoIEPS = 0;
                foreach ($xml->xpath('/cfdi:Comprobante/cfdi:Conceptos/cfdi:Concepto') as $Concepto){
                    $impuestos = [];      
                    $concepto = null;
                    $concepto['ClaveProdServ'] = "{$Concepto['ClaveProdServ']}";
                    $concepto['Descuento'] = "{$Concepto['descuento']}";
                    $concepto['Cantidad'] = "{$Concepto['cantidad']}";
                    $concepto['ClaveUnidad'] = "{$Concepto['ClaveUnidad']}";
                    $concepto['Unidad'] = "{$Concepto['unidad']}";
                    $concepto['Descripcion'] = "{$Concepto['descripcion']}";
                    $concepto['ValorUnitario'] = "{$Concepto['valorUnitario']}";
                    $concepto['Importe'] = "{$Concepto['importe']}";
                    // $con = $Concepto->asXML();
                    // $con = str_replace("cfdi:","",$con);
                    // $con = str_replace("terceros:","",$con);
                    // $con = simplexml_load_string($con);
                    // // Bucle para obtener los Traslados del producto
                    // foreach ($con->xpath('/Concepto/Impuestos/Traslados/Traslado') as $Traslado){
                    //     $traslado = null; 
                    //     $traslado['Importe'] = "{$Traslado['Importe']}";
                    //     $traslado['Base'] = (float)$concepto['Importe'] - (float)$concepto['Descuento'];
                    //     $traslado['Impuesto'] = "{$Traslado['Impuesto']}";
                    //     $traslado['TipoFactor'] = "{$Traslado['TipoFactor']}";
                    //     $traslado['TasaOCuota'] = "{$Traslado['TasaOCuota']}";
                    //     if($traslado['Impuesto'] === "002" ){
                    //         $TrasladoIVA = $TrasladoIVA + $traslado['Importe'];   
                    //     }
                    //     if($traslado['Impuesto'] === "003" ){
                    //         $TrasladoIEPS = $TrasladoIEPS + $traslado['Importe'];
                    //         $TotalBaseIEPS = TotalBaseIEPS + $traslado['Base'];
                    //     }
                    //     array_push($impuestos, $traslado);
                    // }
                    // // Bucle para obtener Retenciones del producto
                    // foreach ($con->xpath('/Concepto/Impuestos/Retenciones/Retencion') as $Retencion){
                    //     $retencion = null; 
                    //     $retencion['Importe'] = "{$Retencion['Importe']}";
                    //     $retencion['Base'] = (float)$concepto['Importe'] - (float)$concepto['Descuento'];
                    //     $retencion['Impuesto'] = "{$Retencion['Impuesto']}";
                    //     $retencion['TipoFactor'] = "{$Retencion['TipoFactor']}";
                    //     $retencion['TasaOCuota'] = "{$Retencion['TasaOCuota']}";
                    //     if( $retencion['Impuesto'] === "001" ){
                    //         $RetencionISR = $RetencionISR + $retencion['Importe'];
                    //     }
                    //     if( $retencion['Impuesto'] === "002" ){
                    //         $RetencionIVA = $RetencionIVA + $retencion['Importe'];
                    //     }
                    //     if( $retencion['Impuesto'] === "003" ){
                    //         $RetencionIEPS = $RetencionIEPS + $retencion['Importe'];
                    //     }
                    //     array_push($impuestos, $retencion);
                    // }
                    // Bucle para impuestos a Terceros
                    // foreach ($con->xpath('/Concepto/ComplementoConcepto/PorCuentadeTerceros/Impuestos/Traslados/Traslado') as $TerceroTraslado){
                    //     $tasa = (float)"{$TerceroTraslado['tasa']}";
                    //     if($tasa === 0.0){
                    //         $TotalBase0 = $TotalBase0 + (float)$concepto['Importe'] - (float)$concepto['Descuento'];
                    //     }
                    //     if($tasa === 0.16){
                    //         $TotalBase16 = $TotalBase16 + (float)$concepto['Importe'] - (float)$concepto['Descuento'];
                    //     }
                    // }
                    // // Fin de bucles
                    // $concepto['Impuestos'] = $impuestos;
                    array_push($Conceptos,$concepto);
                }
                // Guardamos la informacion de los impuestos en la seccion de Impuestos
                foreach ($xml->xpath('/cfdi:Comprobante/cfdi:Impuestos/cfdi:Traslados/cfdi:Traslado') as $traslado){
                    $impuesto = "{$traslado['impuesto']}";
                    if($impuesto === "IVA"){
                        $CFDI->Impuestos->TrasladoIVA = "{$traslado['importe']}";
                    }
                    if($impuesto === "IEPS"){
                        $CFDI->Impuestos->TrasladoIEPS = "{$traslado['importe']}";
                    }
                }
                foreach ($xml->xpath('/cfdi:Comprobante/cfdi:Impuestos/cfdi:Retenciones/cfdi:Retencion') as $retencion){
                    $impuesto = "{$retencion['impuesto']}";
                    if($impuesto === "ISR"){
                        $CFDI->Impuestos->RetencionISR = "{$retencion['importe']}";
                    }
                    if($impuesto === "IVA"){
                        $CFDI->Impuestos->RetencionIVA = "{$retencion['importe']}";
                    }
                    if($impuesto === "IEPS"){
                        $CFDI->Impuestos->RetencionIEPS = "{$retencion['importe']}";
                    }
                }
                
                $CFDI->Impuestos->TotalTrasladoIVA = $TrasladoIVA;
                $CFDI->Impuestos->TotalTrasladoIEPS = $TrasladoIEPS;
                $CFDI->Impuestos->TotalRetencionISR = $RetencionISR;
                $CFDI->Impuestos->TotalRetencionIVA = $RetencionIVA;
                $CFDI->Impuestos->TotalRetencionIEPS = $RetencionIEPS;
                
                $CFDI->Conceptos = $Conceptos;

                $xml->registerXPathNamespace('t', $ns['tfd']);
                foreach ($xml->xpath('/cfdi:Comprobante/cfdi:Complemento/t:TimbreFiscalDigital') as $cfdiComplemento){
                    $CFDI->UUID = "{$cfdiComplemento['UUID']}";
                    $CFDI->SelloCFD = "{$cfdiComplemento['selloCFD']}";
                    $CFDI->SelloSAT = "{$cfdiComplemento['selloSAT']}";
                    $CFDI->NoCertificadoSAT = "{$cfdiComplemento['noCertificadoSAT']}";
                    $CFDI->RfcProvCertif = "{$cfdiComplemento['RfcProvCertif']}";
                    $CFDI->FechaTimbrado = "{$cfdiComplemento['FechaTimbrado']}";
                }
                

                $CuotasImss = 0;
                for ($i=0; $i < count($CFDI->Conceptos) ; $i++) { 
                    if($CFDI->Conceptos[$i]['ClaveProdServ'] === "85101701"){
                        $CuotasImss = $CuotasImss + (float)$CFDI->Conceptos[$i]['Importe'] - (float)$CFDI->Conceptos[$i]['Descuento'];
                    }
                }
                $CFDI->Otros->CuotaImss = $CuotasImss;

                    // FIN DE OBTENCION DE VARIABLES GLOBALES EN LA FACTURA
                    
                if($CFDI->TipoDeComprobante === 'I' or $CFDI->TipoDeComprobante === 'E'){
                        // <-----------------------------Obtenemos manualmente el valor del ingreso por clientes --------------------------->
                        // Continuacion con las variables de abajo
                        // $TotalBase16 = 0.0;
                        // $TotalBase0 = 0.0;
                        for ($l=0; $l < count($CFDI->Conceptos) ; $l++) {
                            if(count($CFDI->Conceptos[$l]['Impuestos']) === 0){
                                $TotalBase0 = $TotalBase0 + (float)$CFDI->Conceptos[$l]['Importe'];
                            }
                            for ($x=0; $x < count($CFDI->Conceptos[$l]['Impuestos']) ; $x++) {
                                if( $CFDI->Conceptos[$l]['Impuestos'][$x]['Impuesto'] === "003" ){
                                    
                                }
                                if( $CFDI->Conceptos[$l]['Impuestos'][$x]['Impuesto'] === "002" ){
                                    $base16 = (float)$CFDI->Conceptos[$l]['Impuestos'][$x]['TasaOCuota'];
                                    if( $base16 === .16 ){
                                        $TotalBase16 = $TotalBase16 + (float)$CFDI->Conceptos[$l]['Impuestos'][$x]['Base'];
                                    }
                                    if( $CFDI->Conceptos[$l]['Impuestos'][$x]['TasaOCuota'] === "0.000000"){
                                        $TotalBase0 = $TotalBase0 + (float)$CFDI->Conceptos[$l]['Impuestos'][$x]['Base'];
                                    }
                                    if( $CFDI->Conceptos[$l]['Impuestos'][$x]['TasaOCuota'] === ""){
                                        $TotalBase0 = $TotalBase0 + (float)$CFDI->Conceptos[$l]['Impuestos'][$x]['Base'];
                                    }
                                }
                            }
                        }
                        $CFDI->TotalGravado = $TotalBase16;
                        $CFDI->TotalExento = $TotalBase0;
                        $CFDI->TotalIEPS = $TotalBaseIEPS;
                    // <------------------------------------------- IMPUESTOS Y OTROS ------------------------------------------------------>
                        foreach ($xml->xpath('/cfdi:Comprobante/cfdi:Impuestos') as $Imp){
                            $CFDI->TotalImpuestosRetenidos = "{$Imp['totalImpuestosRetenidos']}";
                            $CFDI->TotalImpuestosTrasladados = "{$Imp['totalImpuestosTrasladados']}";
                        }
                        
                    // <------------------------------------------- FIN IMPUESTOS Y OTROS ------------------------------------------------------>
                    // IMPUESTOS LOCALES
                    $TotaldeTraslados = 0;
                    $TotaldeRetenciones = 0;
                    error_reporting(0);
                    foreach ($xml->xpath('/cfdi:Comprobante/cfdi:Complemento/implocal:ImpuestosLocales') as $loc){
                        $TotaldeTraslados = "{$loc['TotaldeTraslados']}";
                        $TotaldeRetenciones = "{$loc['TotaldeRetenciones']}";
                    }
                    $CFDI->Impuestos->TotaldeTrasladosLoc = $TotaldeTraslados;
                    $CFDI->Impuestos->TotaldeRetencionesLoc = $TotaldeRetenciones;
                    
                }
                if($CFDI->TipoDeComprobante === 'N'){
                    $nomina = (object)[];
                    $nomina->receptor = (object)[];
                    $nomina->emisor = (object)[];
                    
                    $xml->registerXPathNamespace('c', $ns['cfdi']);
                    $xml->registerXPathNamespace('n', $ns['nomina12']);
                    foreach ($xml->xpath('/c:Comprobante/c:Complemento/n:Nomina') as $n){
                        $nomina->FechaInicialPago = "{$n['FechaInicialPago']}";
                        $nomina->FechaFinalPago = "{$n['FechaFinalPago']}";
                        $nomina->FechaPago = "{$n['FechaPago']}";
                        $nomina->NumDiasPagados = "{$n['NumDiasPagados']}";
                    }
                    foreach ($xml->xpath('/c:Comprobante/c:Complemento/n:Nomina/n:Receptor') as $nomina_receptor){
                        $nomina->receptor->Curp = "{$nomina_receptor['Curp']}";
                        $nomina->receptor->NumSeguridadSocial = "{$nomina_receptor['NumSeguridadSocial']}";
                        $nomina->receptor->FechaInicioRelLaboral = "{$nomina_receptor['FechaInicioRelLaboral']}";
                        $nomina->receptor->Antiguedad = "{$nomina_receptor['Antigüedad']}";
                        $nomina->receptor->TipoContrato = "{$nomina_receptor['TipoContrato']}";
                        $nomina->receptor->Sindicalizado = "{$nomina_receptor['Sindicalizado']}";
                        $nomina->receptor->TipoJornada = "{$nomina_receptor['TipoJornada']}";
                        $nomina->receptor->TipoRegimen = "{$nomina_receptor['TipoRegimen']}";
                        $nomina->receptor->NumEmpleado = "{$nomina_receptor['NumEmpleado']}";
                        $nomina->receptor->Departamento = "{$nomina_receptor['Departamento']}";
                        $nomina->receptor->Puesto = "{$nomina_receptor['Puesto']}";
                        $nomina->receptor->RiesgoPuesto = "{$nomina_receptor['RiesgoPuesto']}";
                        $nomina->receptor->PeriodicidadPago = "{$nomina_receptor['PeriodicidadPago']}";
                        $nomina->receptor->Banco = "{$nomina_receptor['Banco']}";
                        $nomina->receptor->CuentaBancaria = "{$nomina_receptor['CuentaBancaria']}";
                        $nomina->receptor->SalarioBaseCotApor = "{$nomina_receptor['SalarioBaseCotApor']}";
                        $nomina->receptor->SalarioDiarioIntegrado = "{$nomina_receptor['SalarioDiarioIntegrado']}";
                        $nomina->receptor->ClaveEntFed = "{$nomina_receptor['ClaveEntFed']}";
                    }
                    foreach ($xml->xpath('/c:Comprobante/c:Complemento/n:Nomina/n:Emisor') as $e){
                        $nomina->emisor->Curp = "{$e['Curp']}";
                        $nomina->emisor->RegistroPatronal = "{$e['RegistroPatronal']}";
                    }
                    $nomina_conceptos = [];
                    // Obtenemos las variables de deducciones, percepciones otros
                    $TotalSueldos = 0;
                    $TotalSeparacionIndemizacion = 0;
                    $TotalJubilacionPensionRetiro = 0;
                    $TotalGravado = 0;
                    $TotalExento = 0;
                    foreach ($xml->xpath('/c:Comprobante/c:Complemento/n:Nomina/n:Percepciones') as $t){
                        $TotalSueldos = $TotalSueldos + $t['TotalSueldos'];
                        $TotalSeparacionIndemizacion = $TotalSeparacionIndemizacion + $t['TotalSeparacionIndemizacion'];
                        $TotalJubilacionPensionRetiro = $TotalJubilacionPensionRetiro + $t['TotalJubilacionPensionRetiro'];
                        $TotalGravado = $TotalGravado + $t['TotalGravado'];
                        $TotalExento = $TotalExento + $t['TotalExento'];
                    }
                    $nomina->TotalSueldos = $TotalSueldos;
                    $nomina->TotalSeparacionIndemizacion = $TotalSeparacionIndemizacion;
                    $nomina->TotalJubilacionPensionRetiro = $TotalJubilacionPensionRetiro;
                    $nomina->TotalGravado = $TotalGravado;
                    $nomina->TotalExento = $TotalExento;
                    // FIN
                    foreach ($xml->xpath('/c:Comprobante/c:Complemento/n:Nomina/n:Percepciones/n:Percepcion') as $Concepto){
                        $concepto = null;
                        $concepto['tipo'] = "{$Concepto['TipoPercepcion']}";
                        $concepto['Clave'] = "{$Concepto['Clave']}";
                        $concepto['Concepto'] = "{$Concepto['Concepto']}";
                        $concepto['ImporteGravado'] = "{$Concepto['ImporteGravado']}";
                        $concepto['ImporteExento'] = "{$Concepto['ImporteExento']}";
                        $concepto['info'] = "percepcion";
                        array_push($nomina_conceptos,$concepto);
                    }
                    $TotalOtrasDeducciones = 0;
                    $TotalImpuestosRetenidos = 0;
                    foreach ($xml->xpath('/c:Comprobante/c:Complemento/n:Nomina/n:Deducciones') as $c){
                        $TotalOtrasDeducciones = $TotalOtrasDeducciones + $c['TotalOtrasDeducciones'];
                        $TotalImpuestosRetenidos = $TotalImpuestosRetenidos + $c['TotalImpuestosRetenidos'];
                    }
                    $nomina->TotalOtrasDeducciones = $TotalOtrasDeducciones;
                    $nomina->TotalImpuestosRetenidos = $TotalImpuestosRetenidos;
                    foreach ($xml->xpath('/c:Comprobante/c:Complemento/n:Nomina/n:Deducciones/n:Deduccion') as $Concepto){
                        $concepto = null;
                        $concepto['tipo'] = "{$Concepto['TipoDeduccion']}";
                        $concepto['Clave'] = "{$Concepto['Clave']}";
                        $concepto['Concepto'] = "{$Concepto['Concepto']}";
                        $concepto['Importe'] = "{$Concepto['Importe']}";
                        $concepto['info'] = "deduccion";
                        array_push($nomina_conceptos,$concepto);
                    }
                    $SubsidioCausado = 0;
                    $SubsidioImporte = 0;
                    foreach ($xml->xpath('/c:Comprobante/c:Complemento/n:Nomina/n:OtrosPagos/n:OtroPago') as $Concepto){
                        $concepto = null;
                        $concepto['tipo'] = "{$Concepto['TipoOtroPago']}";
                        $concepto['Clave'] = "{$Concepto['Clave']}";
                        $concepto['Concepto'] = "{$Concepto['Concepto']}";
                        $concepto['Importe'] = "{$Concepto['Importe']}";
                        $concepto['info'] = "otro";
                        if($concepto['tipo'] === "002"){
                            foreach ($xml->xpath('/c:Comprobante/c:Complemento/n:Nomina/n:OtrosPagos/n:OtroPago/n:SubsidioAlEmpleo') as $sub){
                                $SubsidioCausado = "{$sub['SubsidioCausado']}";
                            }
                            $SubsidioImporte = "{$Concepto['Importe']}";
                        }
                        array_push($nomina_conceptos,$concepto);
                    }
                    $nomina->SubsidioCausado = $SubsidioCausado;           
                    $nomina->SubsidioImporte = $SubsidioImporte;           
                    $nomina->conceptos = $nomina_conceptos;
                    $CFDI->Complementos->Nomina = $nomina;

                }
                $TotalPagos = 0;
                if($CFDI->TipoDeComprobante === "P"){
                    // Variables para pago
                    $xml_pagos = (object)[];
                    $pagos = [];
                    error_reporting(0);
                    $xml->registerXPathNamespace('c', $ns['cfdi']);
                    $xml->registerXPathNamespace('p', $ns['pago10']);
                    $p="";
                    $pagos_separados = [];
                    foreach ($xml->xpath('/c:Comprobante/c:Complemento/p:Pagos/p:Pago') as $p){
                        $pago = (object)[];
                        $pago->FechaPago = "{$p['FechaPago']}";
                        $pago->FormaDePagoP = "{$p['FormaDePagoP']}";
                        $pago->MonedaP = "{$p['MonedaP']}";
                        $pago->TipoCambioP = "{$p['TipoCambioP']}";
                        $pago->Monto = "{$p['Monto']}";
                        $pago->NumOperacion = "{$p['NumOperacion']}";
                        $pago->RfcEmisorCtaOrd = "{$p['RfcEmisorCtaOrd']}";
                        $pago->NomBancoOrdExt = "{$p['NomBancoOrdExt']}";
                        $pago->CtaOrdenante = "{$p['CtaOrdenante']}";
                        $pago->RfcEmisorCtaBen = "{$p['RfcEmisorCtaBen']}";
                        $pago->CtaBeneficiario = "{$p['CtaBeneficiario']}";
                        $pago->TipoCadPago = "{$p['TipoCadPago']}";
                        $pago->CertPago = "{$p['CertPago']}";
                        $pago->CadPago = "{$p['CadPago']}";
                        $pago->SelloPago = "{$p['SelloPago']}";
                        $pago->documentos = [];
                        array_push($pagos,$pago);
                        array_push($pagos_separados, $p->asXML() );
                        $TotalPagos = $TotalPagos + $pago->Monto;
                    }
                    for ($i=0; $i < count($pagos_separados) ; $i++) {
                        
                        $pagos_separados[$i] = str_replace("pago10:","",$pagos_separados[$i]);
                        $pago_int = simplexml_load_string($pagos_separados[$i]);
                        $documentos = [];
                        foreach ($pago_int->xpath('/Pago/DoctoRelacionado') as $docto){
                            $doc = (object)[];
                            $doc->IdDocumento = "{$docto['IdDocumento']}";
                            $doc->Serie = "{$docto['Serie']}";
                            $doc->Folio = "{$docto['Folio']}";
                            $doc->MonedaDR = "{$docto['MonedaDR']}";
                            $doc->TipoCambioDR = "{$docto['TipoCambioDR']}";
                            $doc->MetodoDePagoDR = "{$docto['MetodoDePagoDR']}";
                            $doc->NumParcialidad = "{$docto['NumParcialidad']}";
                            $doc->ImpSaldoAnt = "{$docto['ImpSaldoAnt']}";
                            $doc->ImpPagado = "{$docto['ImpPagado']}";
                            $doc->ImpSaldoInsoluto = "{$docto['ImpSaldoInsoluto']}";
                            array_push($documentos, $doc);  
                        }
                        $pagos[$i]->documentos = $documentos;
                        $CFDI->Complementos->Pagos = $pagos;
                    }
                    
                    if ( $CFDI->Complementos == new stdClass() ){
                        $xml->registerXPathNamespace('p', $ns['ns2']);
                        $p="";
                        $pagos_separados = [];
                        foreach ($xml->xpath('/c:Comprobante/c:Complemento/p:Pagos/p:Pago') as $p){
                            $pago = (object)[];
                            $pago->FechaPago = "{$p['FechaPago']}";
                            $pago->FormaDePagoP = "{$p['FormaDePagoP']}";
                            $pago->MonedaP = "{$p['MonedaP']}";
                            $pago->TipoCambioP = "{$p['TipoCambioP']}";
                            $pago->Monto = "{$p['Monto']}";
                            $pago->NumOperacion = "{$p['NumOperacion']}";
                            $pago->RfcEmisorCtaOrd = "{$p['RfcEmisorCtaOrd']}";
                            $pago->NomBancoOrdExt = "{$p['NomBancoOrdExt']}";
                            $pago->CtaOrdenante = "{$p['CtaOrdenante']}";
                            $pago->RfcEmisorCtaBen = "{$p['RfcEmisorCtaBen']}";
                            $pago->CtaBeneficiario = "{$p['CtaBeneficiario']}";
                            $pago->TipoCadPago = "{$p['TipoCadPago']}";
                            $pago->CertPago = "{$p['CertPago']}";
                            $pago->CadPago = "{$p['CadPago']}";
                            $pago->SelloPago = "{$p['SelloPago']}";
                            $pago->documentos = [];
                            array_push($pagos,$pago);
                            array_push($pagos_separados, $p->asXML() );
                            $TotalPagos = $TotalPagos + $pago->Monto;
                        }
                        for ($i=0; $i < count($pagos_separados) ; $i++) {
                            
                            $pagos_separados[$i] = str_replace("ns2:","",$pagos_separados[$i]);
                            $pago_int = simplexml_load_string($pagos_separados[$i]);
                            $documentos = [];
                            foreach ($pago_int->xpath('/Pago/DoctoRelacionado') as $docto){
                                $doc = (object)[];
                                $doc->IdDocumento = "{$docto['IdDocumento']}";
                                $doc->Serie = "{$docto['Serie']}";
                                $doc->Folio = "{$docto['Folio']}";
                                $doc->MonedaDR = "{$docto['MonedaDR']}";
                                $doc->TipoCambioDR = "{$docto['TipoCambioDR']}";
                                $doc->MetodoDePagoDR = "{$docto['MetodoDePagoDR']}";
                                $doc->NumParcialidad = "{$docto['NumParcialidad']}";
                                $doc->ImpSaldoAnt = "{$docto['ImpSaldoAnt']}";
                                $doc->ImpPagado = "{$docto['ImpPagado']}";
                                $doc->ImpSaldoInsoluto = "{$docto['ImpSaldoInsoluto']}";
                                array_push($documentos, $doc);  
                            }
                            $pagos[$i]->documentos = $documentos;
                            $CFDI->Complementos->Pagos = $pagos;
                        }
                    }
                    $CFDI->Complementos->Pagos->TotalPagos = $TotalPagos;
                }
            }
            return $CFDI;
        }
    }