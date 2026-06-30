<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../includes/conexion.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

date_default_timezone_set('America/Guayaquil');

try {

    $sql = "
        SELECT
            v.*,
            CONCAT(c.nombres,' ',c.apellidos) AS chofer,
            d.nombre AS direccion
        FROM vehiculos v
        LEFT JOIN choferes c
            ON v.id_chofer = c.id_chofer
        LEFT JOIN direcciones d
            ON v.id_direccion = d.id_direccion
        ORDER BY v.placa
    ";

    $stmt = $pdo->query($sql);
    $vehiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: ".$e->getMessage());
}

$html='
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">

<style>

body{
    font-family: DejaVu Sans, sans-serif;
    font-size:9px;
}

h2{
    text-align:center;
    margin-bottom:5px;
}

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:#D9D9D9;
    border:1px solid #000;
    padding:5px;
    font-size:8px;
}

td{
    border:1px solid #000;
    padding:4px;
    font-size:8px;
}

.info{
    margin-bottom:10px;
}

</style>

</head>

<body>

<h2>REPORTE GENERAL DE VEHÍCULOS</h2>

<div class="info">
<b>Fecha:</b> '.date('d/m/Y').' &nbsp;&nbsp;&nbsp;
<b>Hora:</b> '.date('H:i:s').'
</div>

<table>

<tr>

<th>ID</th>
<th>Placa</th>
<th>Matrícula</th>
<th>Marca</th>
<th>Modelo</th>
<th>Tipo</th>
<th>Color</th>
<th>Año</th>
<th>Código</th>
<th>Chasis</th>
<th>Motor</th>
<th>Unidad</th>
<th>Descripción</th>
<th>Chofer</th>
<th>Dirección</th>

</tr>';

foreach($vehiculos as $v){

$html.='

<tr>

<td>'.htmlspecialchars($v['id_vehiculo']).'</td>

<td>'.htmlspecialchars($v['placa']).'</td>

<td>'.htmlspecialchars($v['matricula']).'</td>

<td>'.htmlspecialchars($v['marca']).'</td>

<td>'.htmlspecialchars($v['modelo']).'</td>

<td>'.htmlspecialchars($v['tipo']).'</td>

<td>'.htmlspecialchars($v['color']).'</td>

<td>'.htmlspecialchars($v['anio']).'</td>

<td>'.htmlspecialchars($v['codigo_institucional']).'</td>

<td>'.htmlspecialchars($v['chasis']).'</td>

<td>'.htmlspecialchars($v['motor']).'</td>

<td>'.htmlspecialchars($v['unidad']).'</td>

<td>'.htmlspecialchars($v['descripcion_vehiculo']).'</td>

<td>'.htmlspecialchars($v['chofer'] ?? '').'</td>

<td>'.htmlspecialchars($v['direccion'] ?? '').'</td>

</tr>';

}

$html.='

</table>

<br>

<b>Total de vehículos registrados: </b>'.count($vehiculos).'

</body>
</html>';

$options = new Options();
$options->set('isRemoteEnabled', true);

$pdf = new Dompdf($options);

$pdf->loadHtml($html);

$pdf->setPaper('A4', 'landscape');

$pdf->render();

$pdf->stream(
    "Reporte_Vehiculos.pdf",
    ["Attachment" => false]
);

exit;