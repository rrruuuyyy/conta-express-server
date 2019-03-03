<?php

    class infoXML{
        public static function obtener($archivo_xml){
            $CFDI = (object)[];
            $CFDI->Receptor = (object)[];
            $CFDI->Emisor = (object)[];
            $CFDI->Impuestos = (object)[];
            $CFDI->Complementos = (object)[];
            $Conceptos = [];
            $UUID_vinculados = [];
            $xml = simplexml_load_file( $archivo_xml );
            // if( $xml === false ){
            //     return false;
            // }           
            $ns = $xml->getNamespaces(true);
            $xml->registerXPathNamespace('c', $ns['cfdi']);
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
            foreach ($xml->xpath('/cfdi:Comprobante/cfdi:Conceptos/cfdi:Concepto') as $Concepto){
                $concepto = null;
                $concepto['ClaveProdServ'] = "{$Concepto['ClaveProdServ']}";
                $concepto['Cantidad'] = "{$Concepto['Cantidad']}";
                $concepto['ClaveUnidad'] = "{$Concepto['ClaveUnidad']}";
                $concepto['Unidad'] = "{$Concepto['Unidad']}";
                $concepto['Descripcion'] = "{$Concepto['Descripcion']}";
                $concepto['ValorUnitario'] = "{$Concepto['ValorUnitario']}";
                $concepto['Importe'] = "{$Concepto['Importe']}";
                array_push($Conceptos,$concepto);
            }
            

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

                // FIN DE OBTENCION DE VARIABLES GLOBALES EN LA FACTURA
                $RetencionISR = 0;
                $RetencionIVA = 0;
                $RetencionIEPS = 0;
                $TrasladoIVA = 0;
                $TrasladoIEPS = 0;
            if($CFDI->TipoDeComprobante === 'I' or $CFDI->TipoDeComprobante === 'E'){
                    // <-----------------------------Obtenemos manualmente el valor del ingreso por clientes --------------------------->
                    $TotalBase16 = 0.0;
                    $TotalBase0 = 0.0;
                    foreach ($xml->xpath('/cfdi:Comprobante/cfdi:Conceptos/cfdi:Concepto/cfdi:Impuestos/cfdi:Traslados/cfdi:Traslado') as $base){
                        if( "{$base['TasaOCuota']}" === "0.160000" ){
                            $TotalBase16 = $TotalBase16 + "{$base['Base']}";
                        }
                        if( "{$base['TipoFactor']}" === "Exento" OR "{$base['TasaOCuota']}" === "0.000000"){
                            $TotalBase0 = $TotalBase0 + "{$base['Base']}";
                        }
                    }
                    $CFDI->TotalGravado = $TotalBase16;
                    $CFDI->TotalExento = $TotalBase0;
                    // <-----------------------------Obtenemos manualmente el total de los impuestos Retenidos --------------------------->
                    // $TotalImpuestosRetenidosISR = 0.0;
                    // $TotalImpuestosRetenidosIVA = 0.0;
                    // foreach ($xml->xpath('/cfdi:Comprobante/cfdi:Conceptos/cfdi:Concepto/cfdi:Impuestos/cfdi:Retenciones/cfdi:Retencion') as $base){
                    //     if( "{$base['Impuesto']}" === "001" ){
                    //         $TotalImpuestosRetenidosISR = $TotalImpuestosRetenidosISR + "{$base['Importe']}";
                    //     }
                    //     if( "{$base['Impuesto']}" === "002" ){
                    //         $TotalImpuestosRetenidosIVA = $TotalImpuestosRetenidosIVA + "{$base['Importe']}";
                    //     }
                    // }
                    // $CFDI->TotalImpuestosRetenidosISR = $TotalImpuestosRetenidosISR;
                    // $CFDI->TotalImpuestosRetenidosIVA = $TotalImpuestosRetenidosIVA;
                    // <------------------------- FIN -------------------------->
                    // Fin separacion
                $Traslados = [];
                $Retenciones = [];
                foreach ($xml->xpath('/cfdi:Comprobante/cfdi:Impuestos/cfdi:Traslados/cfdi:Traslado') as $Traslado){
                    $traslado = null; 
                    $traslado['Importe'] = "{$Traslado['Importe']}";
                    $traslado['Base'] = "{$Traslado['Base']}";
                    $traslado['Impuesto'] = "{$Traslado['Impuesto']}";
                    $traslado['TipoFactor'] = "{$Traslado['TipoFactor']}";
                    $traslado['TasaOCuota'] = "{$Traslado['TasaOCuota']}";
                    array_push($Traslados, $traslado);
                    if($traslado['Impuesto'] === "002" ){
                        $TrasladoIVA = $TrasladoIVA + $traslado['Importe'];
                    }
                    if($traslado['Impuesto'] === "003" ){
                        $TrasladoIEPS = $TrasladoIEPS + $traslado['Importe'];
                    }
                }
                $CFDI->Impuestos->Traslados = $Traslados;
                $CFDI->Impuestos->TotalTrasladoIVA = $TrasladoIVA;
                $CFDI->Impuestos->TotalTrasladoIEPS = $TrasladoIEPS;
                foreach ($xml->xpath('/cfdi:Comprobante/cfdi:Impuestos/cfdi:Retenciones/cfdi:Retencion') as $Retencion){
                    $retencion = null; 
                    $retencion['Importe'] = "{$Retencion['Importe']}";
                    $retencion['Base'] = "{$Retencion['Base']}";
                    $retencion['Impuesto'] = "{$Retencion['Impuesto']}";
                    $retencion['TipoFactor'] = "{$Retencion['TipoFactor']}";
                    $retencion['TasaOCuota'] = "{$Retencion['TasaOCuota']}";
                    array_push($Retenciones, $retencion);
                    if( $retencion['Impuesto'] === "001" ){
                        $RetencionISR = $RetencionISR + $retencion['Importe'];
                    }
                    if( $retencion['Impuesto'] === "002" ){
                        $RetencionIVA = $RetencionIVA + $retencion['Importe'];
                    }
                    if( $retencion['Impuesto'] === "003" ){
                        $RetencionIEPS = $RetencionIEPS + $retencion['Importe'];
                    }
                }
                $CFDI->Impuestos->Retenciones = $Retenciones;
                $CFDI->Impuestos->TotalRetencionISR = $RetencionISR;
                $CFDI->Impuestos->TotalRetencionIVA = $RetencionIVA;
                $CFDI->Impuestos->TotalRetencionIEPS = $RetencionIEPS;
                foreach ($xml->xpath('/cfdi:Comprobante/cfdi:Impuestos') as $Imp){
                    $CFDI->TotalImpuestosRetenidos = "{$Imp['TotalImpuestosRetenidos']}";
                    $CFDI->TotalImpuestosTrasladados = "{$Imp['TotalImpuestosTrasladados']}";
                }
                
            }
            // if($CFDI->TipoDeComprobante === 'N'){
            //     $nomina = (object)[];
            //     $nomina->receptor = (object)[];
            //     $nomina->emisor = (object)[];
                
            //     $xml->registerXPathNamespace('c', $ns['cfdi']);
            //     $xml->registerXPathNamespace('n', $ns['nomina12']);
            //     foreach ($xml->xpath('/c:Comprobante/c:Complemento/n:Nomina') as $n){
            //         $nomina->FechaInicialPago = "{$n['FechaInicialPago']}";
            //         $nomina->FechaFinalPago = "{$n['FechaFinalPago']}";
            //         $nomina->FechaPago = "{$n['FechaPago']}";
            //         $nomina->NumDiasPagados = "{$n['NumDiasPagados']}";
            //     }
            //     foreach ($xml->xpath('/c:Comprobante/c:Complemento/n:Nomina/n:Receptor') as $nomina_receptor){
            //         $nomina->receptor->Curp = "{$nomina_receptor['Curp']}";
            //         $nomina->receptor->NumSeguridadSocial = "{$nomina_receptor['NumSeguridadSocial']}";
            //         $nomina->receptor->FechaInicioRelLaboral = "{$nomina_receptor['FechaInicioRelLaboral']}";
            //         $nomina->receptor->Antiguedad = "{$nomina_receptor['Antigüedad']}";
            //         $nomina->receptor->TipoContrato = "{$nomina_receptor['TipoContrato']}";
            //         $nomina->receptor->Sindicalizado = "{$nomina_receptor['Sindicalizado']}";
            //         $nomina->receptor->TipoJornada = "{$nomina_receptor['TipoJornada']}";
            //         $nomina->receptor->TipoRegimen = "{$nomina_receptor['TipoRegimen']}";
            //         $nomina->receptor->NumEmpleado = "{$nomina_receptor['NumEmpleado']}";
            //         $nomina->receptor->Departamento = "{$nomina_receptor['Departamento']}";
            //         $nomina->receptor->Puesto = "{$nomina_receptor['Puesto']}";
            //         $nomina->receptor->RiesgoPuesto = "{$nomina_receptor['RiesgoPuesto']}";
            //         $nomina->receptor->PeriodicidadPago = "{$nomina_receptor['PeriodicidadPago']}";
            //         $nomina->receptor->Banco = "{$nomina_receptor['Banco']}";
            //         $nomina->receptor->CuentaBancaria = "{$nomina_receptor['CuentaBancaria']}";
            //         $nomina->receptor->SalarioBaseCotApor = "{$nomina_receptor['SalarioBaseCotApor']}";
            //         $nomina->receptor->SalarioDiarioIntegrado = "{$nomina_receptor['SalarioDiarioIntegrado']}";
            //         $nomina->receptor->ClaveEntFed = "{$nomina_receptor['ClaveEntFed']}";
            //     }
            //     foreach ($xml->xpath('/c:Comprobante/c:Complemento/n:Nomina/n:Emisor') as $e){
            //         $nomina->emisor->Curp = "{$e['Curp']}";
            //         $nomina->emisor->RegistroPatronal = "{$e['RegistroPatronal']}";
            //     }
            //     $nomina_conceptos = [];
            //     foreach ($xml->xpath('/c:Comprobante/c:Complemento/n:Nomina/n:Percepciones/n:Percepcion') as $Concepto){
            //         $concepto = null;
            //         $concepto['tipo'] = "{$Concepto['TipoPercepcion']}";
            //         $concepto['Clave'] = "{$Concepto['Clave']}";
            //         $concepto['Concepto'] = "{$Concepto['Concepto']}";
            //         $concepto['ImporteGravado'] = "{$Concepto['ImporteGravado']}";
            //         $concepto['ImporteExento'] = "{$Concepto['ImporteExento']}";
            //         $concepto['info'] = "percepcion";
            //         array_push($nomina_conceptos,$concepto);
            //     }
            //     foreach ($xml->xpath('/c:Comprobante/c:Complemento/n:Nomina/n:Deducciones/n:Deduccion') as $Concepto){
            //         $concepto = null;
            //         $concepto['tipo'] = "{$Concepto['TipoDeduccion']}";
            //         $concepto['Clave'] = "{$Concepto['Clave']}";
            //         $concepto['Concepto'] = "{$Concepto['Concepto']}";
            //         $concepto['Importe'] = "{$Concepto['Importe']}";
            //         $concepto['info'] = "deduccion";
            //         array_push($nomina_conceptos,$concepto);
            //     }
            //     foreach ($xml->xpath('/c:Comprobante/c:Complemento/n:Nomina/n:OtrosPagos/n:OtroPago') as $Concepto){
            //         $concepto = null;
            //         $concepto['tipo'] = "{$Concepto['TipoOtroPago']}";
            //         $concepto['Clave'] = "{$Concepto['Clave']}";
            //         $concepto['Concepto'] = "{$Concepto['Concepto']}";
            //         $concepto['Importe'] = "{$Concepto['Importe']}";
            //         $concepto['info'] = "otro";
            //         array_push($nomina_conceptos,$concepto);
            //     }                
            //     $nomina->conceptos = $nomina_conceptos;
            //     $CFDI->Complementos->Nomina = $nomina;

            // }
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
            }
            return $CFDI;
        }
    }