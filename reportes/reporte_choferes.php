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

try {

    $stmt = $pdo->query("
        SELECT *
        FROM choferes
        ORDER BY apellidos, nombres
    ");

    $choferes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

date_default_timezone_set('America/Guayaquil');

$fecha = date('d/m/Y');
$hora  = date('H:i:s');

$html = '
<h2 style="text-align:center;">
REPORTE GENERAL DE CHOFERES
</h2>

<p>
<b>Fecha:</b> '.$fecha.'
<br>
<b>Hora:</b> '.$hora.'
</p>

<table border="1" cellspacing="0" cellpadding="4" width="100%" style="font-size:9px;">
<thead>
<tr>
<th>ID</th>
<th>Nombres</th>
<th>Apellidos</th>
<th>Cédula</th>
<th>Teléfono</th>
<th>Correo</th>
<th>Tipo Lic.</th>
<th>N° Licencia</th>
<th>Caducidad</th>
<th>Cargo</th>
<th>Departamento</th>
<th>Estado</th>
</tr>
</thead>
<tbody>';

foreach ($choferes as $c) {

    $html .= '
    <tr>
        <td>'.$c['id_chofer'].'</td>
        <td>'.$c['nombres'].'</td>
        <td>'.$c['apellidos'].'</td>
        <td>'.$c['cedula'].'</td>
        <td>'.($c['telefono'] ?? '').'</td>
        <td>'.($c['correo'] ?? '').'</td>
        <td>'.($c['tipo_licencia'] ?? '').'</td>
        <td>'.($c['numero_licencia'] ?? '').'</td>
        <td>'.($c['fecha_caducidad_licencia'] ?? '').'</td>
        <td>'.($c['cargo'] ?? '').'</td>
        <td>'.($c['departamento'] ?? '').'</td>
        <td>'.($c['estado'] ?? '').'</td>
    </tr>';
}

$html .= '
</tbody>
</table>';

$options = new Options();
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);

$dompdf->setPaper('A4', 'landscape');

$dompdf->render();

$dompdf->stream(
    "reporte_choferes.pdf",
    ["Attachment" => true]
);

exit;