<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" media="screen" href="plantilla.css">
    <link href="https://fonts.googleapis.com/css?family=PT+Serif" rel="stylesheet">
</head>

<body>
    <div class="div_carta" style="width: 100%; height: 50%;; padding: 0mm">
        <div class="cabeza" style="width: 100%; ">
            <div style=" float:left; width: 15%; padding-top: 8px ">
                <img style="width: 90px " src="../src/plantillas/sanmarig/empresa.png " alt=" ">
            </div>
            <div style="width: 15%; float: right; text-align: right;padding-top: 8px">
                <img src="../src/plantillas/sanmarig/logo2.png" style="width: 85px" alt="">
            </div>
            <div style="width: 69%;">
                <pre style="text-align: center"><span class="titulo_empresa">DESPACHO CONTABLE Y ADMINISTRATIVO</span>
<span class="nombre_empresa">S A N M A R I G</span>
</pre>
                <div style="padding-top: -20px; width: 100%">
                    <pre style="float: left; width: 40%" class="texto_info">AV. LAZARO CARDENAS No. 84 INTERIOR 3
COL. PRIMERA SECCION
SAN PEDRO POCHUTLA, OAX
TEL. 58 4 0154
</pre>
                    <pre class="texto_info" style="width: 35%; float: right; text-align: right; padding-top: -9px">ELVA BENITEZ MAYO
R.F.C BEME691220UF8
C.U.R.P BEME691220MGRNYL29
sanmarig@hotmail.com
</pre>
                    <pre style="width: 20%" class="texto_info" style="text-align: center">CED. PROF: 2106142</pre>
                </div>
            </div>
        </div>
        <div class="separador">
            <img src="../src/plantillas/sanmarig/barra.png" alt="">
        </div>
        <div style=" padding-left: 30px; padding-right:30px;overflow: auto; margin-top: 5px; margin-bottom: 15px">
            <div style=" width: 50% " class="folio ">FOLIO: <?php echo "{$folios->serie} {$folios->folio}"?></div>
            <div style="width: 50% " class="bueno_por ">BUENO POR $ <?php echo number_format($cobro->importe,2,".",","); ?></div>
        </div>
        <div class="cuerpo ">
            RECIBI DE <?php $str = strtoupper($cobro->cliente->nombre); echo $str ?> LA CANTIDAD DE ( <?php $numero_en_letras = new conversorNumero();$numero_en_letras = $numero_en_letras->conversor($cobro->importe); echo $numero_en_letras; ?> ) POR CONCEPTO DE HONORARIOS POR <?php echo strtoupper($cobro->descripcion) ?> CORRESPONDIENTES DEL <?php echo "{$cobro->inicio_servicio} AL {$cobro->fecha_pendiente}" ?>
        </div>
        <div class="fecha">
            POCHUTLA, OAX. A <?php echo $hoy ?>
        </div>
        <div>
            <div class="firma_contador">
                C.P IGNACIO SANCHEZ MARTINEZ
            </div>
        </div>
    </div>
    
</body>

</html>