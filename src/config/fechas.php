<?php
function dias_pasados($fecha_inicial, $fecha_final){
    $dias = (strtotime($fecha_inicial)-strtotime($fecha_final))/86400;
    $dias = abs($dias); $dias = floor($dias);
    return $dias;
}
function sumar_periodo($fecha, $periodo){
    switch ($periodo) {
        case 'semanal':
            return date("Y/m/d",strtotime($fecha."+1 week"));
            break;
        case 'mensual':
            return date("Y/m/d",strtotime($fecha."+1 month"));
            break;
        case 'bimestral':
            return date("Y/m/d",strtotime($fecha."+2 month"));
            break;
        case 'trimestral':
            return date("Y/m/d",strtotime($fecha."+3 month"));
            break;
        case 'anual':
            return date("Y/m/d",strtotime($fecha."+1 year"));
            break;
    }
}
function restar_periodo($fecha, $periodo){
    switch ($periodo) {
        case 'semanal':
            return date("Y/m/d",strtotime($fecha."-1 week"));
            break;
        case 'mensual':
            return date("Y/m/d",strtotime($fecha."-1 month"));
            break;
        case 'bimestral':
            return date("Y/m/d",strtotime($fecha."-2 month"));
            break;
        case 'trimestral':
            return date("Y/m/d",strtotime($fecha."-3 month"));
            break;
        case 'anual':
            return date("Y/m/d",strtotime($fecha."-1 year"));
            break;
    }
}
function obtenerPeriodo($fecha_inicial,$fecha_final,$periodo){
	$fechainicial = new DateTime($fecha_inicial);
    $fechafinal = new DateTime($fecha_final);
    $diferencia = $fechainicial->diff($fechafinal);
	switch ($periodo) {
        case 'semanal':
            $semanas = ($diferencia->y * 12) + ($diferencia->m * 7) + ($diferencia->d);
            $semanas = $semanas / 7;
            return $semanas;
            break;
        case 'mensual':
            $meses = ($diferencia->y * 12) + ($diferencia->m);
            return $meses;
            break;
        case 'bimestral':
            $meses = ($diferencia->y * 12) + ($diferencia->m);
            $meses = $meses / 2;
            return $meses;
            break;
        case 'trimestral':
            $meses = ($diferencia->y * 12) + ($diferencia->m);
            $meses = $meses / 3;
            return $meses;
            break;
        case 'anual':
            $years = $diferencia->y;
            return $years;
            break;
    }
}