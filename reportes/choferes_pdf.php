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

$stmt = $pdo->query("
    SELECT *
    FROM choferes
    ORDER BY apellidos, nombres
");

$choferes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$html = '
<div style="text-align:center;">
    <h2>GOBIERNO AUTÓNOMO DESCENTRALIZADO DE LA PROVINICIA BOLIVAR</h2>
    <h3>REPORTE GENERAL DE CHOFERES</h3>
</div>

<table border="1" cellspacing="0" cellpadding="4" width="100%" style="font-size:10px;">
<thead>
<tr style="background-color:#d9d9d9;">
    <th>ID</th>
    <th>Nombres</th>
    <th>Apellidos</th>
    <th>Cédula</th>
    <th>F. Nacimiento</th>
    <th>Dirección</th>
    <th>Teléfono</th>
    <th>Correo</th>
    <th>Tipo Lic.</th>
    <th>N° Licencia</th>
    <th>F. Emisión</th>
    <th>F. Caducidad</th>
    <th>Cargo</th>
    <th>Departamento</th>
    <th>Grupo Sang.</th>
    <th>Contacto Emerg.</th>
    <th>Tel. Emerg.</th>
    <th>Código Emp.</th>
    <th>F. Ingreso</th>
    <th>Estado</th>
    <th>Observaciones</th>
</tr>
</thead>
<tbody>
';

foreach ($choferes as $c) {

    $html .= '
    <tr>
        <td>'.$c['id_chofer'].'</td>
        <td>'.$c['nombres'].'</td>
        <td>'.$c['apellidos'].'</td>
        <td>'.$c['cedula'].'</td>
        <td>'.$c['fecha_nacimiento'].'</td>
        <td>'.$c['direccion'].'</td>
        <td>'.$c['telefono'].'</td>
        <td>'.$c['correo'].'</td>
        <td>'.$c['tipo_licencia'].'</td>
        <td>'.$c['numero_licencia'].'</td>
        <td>'.$c['fecha_emision_licencia'].'</td>
        <td>'.$c['fecha_caducidad_licencia'].'</td>
        <td>'.$c['cargo'].'</td>
        <td>'.$c['departamento'].'</td>
        <td>'.$c['grupo_sanguineo'].'</td>
        <td>'.$c['contacto_emergencia'].'</td>
        <td>'.$c['telefono_emergencia'].'</td>
        <td>'.$c['codigo_empleado'].'</td>
        <td>'.$c['fecha_ingreso'].'</td>
        <td>'.$c['estado'].'</td>
        <td>'.$c['observaciones'].'</td>
    </tr>';
}

$html .= '
</tbody>
</table>

<br>
<p><strong>Fecha de emisión:</strong> '.date('d/m/Y H:i:s').'</p>
';

$options = new Options();
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);

$dompdf->setPaper('legal', 'landscape');

$dompdf->render();

$dompdf->stream(
    "Reporte_Choferes.pdf",
    ["Attachment" => false]
);