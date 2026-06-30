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
    // Consulta con LEFT JOIN para traer los datos de la dirección institucional
    $stmt = $pdo->query("
        SELECT 
            c.*,
            d.nombre AS direccion_institucional,
            d.codigo AS direccion_codigo
        FROM choferes c
        LEFT JOIN direcciones d ON c.id_direccion = d.id_direccion
        ORDER BY c.apellidos, c.nombres
    ");
    $choferes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al generar el reporte: " . $e->getMessage());
}

// Función auxiliar para formatear fechas en el PDF
function formatearFecha($fecha) {
    return !empty($fecha) ? date('d/m/Y', strtotime($fecha)) : '-';
}

// Estructura HTML con estilos optimizados para orientación horizontal (Landscape)
$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; margin: 0; padding: 0; color: #333; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; font-size: 16px; color: #1a365d; }
        .header h3 { margin: 5px 0 0 0; font-size: 13px; color: #4a5568; }
        
        table { width: 100%; border-collapse: collapse; font-size: 8.5px; }
        th, td { border: 1px solid #cbd5e0; padding: 4px 3px; text-align: left; word-wrap: break-word; }
        th { background-color: #f2f4f8; color: #2d3748; font-weight: bold; text-transform: uppercase; font-size: 8px; }
        tr:nth-child(even) { background-color: #f7fafc; }
        
        .meta { font-size: 10px; margin-top: 15px; color: #718096; }
        .estado-activo { color: green; font-weight: bold; }
        .estado-inactivo { color: red; font-weight: bold; }
    </style>
</head>
<body>

<div class="header">
    <h2>GOBIERNO AUTÓNOMO DESCENTRALIZADO DE LA PROVINCIA DE BOLÍVAR</h2>
    <h3>REPORTE GENERAL DE CHOFERES</h3>
</div>

<table>
<thead>
    <tr>
        <th>ID</th>
        <th>Nombres Completos</th>
        <th>Cédula</th>
        <th>F. Nac.</th>
        <th>Dirección Domicilio</th>
        <th>Teléfono</th>
        <th>Correo</th>
        <th>Licencia</th>
        <th>N° Lic.</th>
        <th>F. Caducidad</th>
        <th>Cargo</th>
        <th>Dir. Institucional</th>
        <th>Grupo Sang.</th>
        <th>Contac. Emerg.</th>
        <th>Tel. Emerg.</th>
        <th>Cód. Emp.</th>
        <th>F. Ingreso</th>
        <th>Estado</th>
    </tr>
</thead>
<tbody>
';

foreach ($choferes as $c) {
    $nombreCompleto = htmlspecialchars(($c['apellidos'] ?? '') . ' ' . ($c['nombres'] ?? ''));
    $estadoClass = ($c['estado'] == 'ACTIVO') ? 'estado-activo' : 'estado-inactivo';
    
    // Concatenamos el código de la dirección si existe para mejor detalle en el reporte
    $dirInstitucional = !empty($c['direccion_institucional']) 
        ? htmlspecialchars($c['direccion_institucional'] . ' (' . $c['direccion_codigo'] . ')') 
        : '-';

    $html .= '
    <tr>
        <td>'.$c['id_chofer'].'</td>
        <td>'.$nombreCompleto.'</td>
        <td>'.htmlspecialchars($c['cedula'] ?? '').'</td>
        <td>'.formatearFecha($c['fecha_nacimiento']).'</td>
        <td>'.htmlspecialchars($c['direccion'] ?? '-').'</td>
        <td>'.htmlspecialchars($c['telefono'] ?? '-').'</td>
        <td>'.htmlspecialchars($c['correo'] ?? '-').'</td>
        <td>'.htmlspecialchars($c['tipo_licencia'] ?? '-').'</td>
        <td>'.htmlspecialchars($c['numero_licencia'] ?? '-').'</td>
        <td>'.formatearFecha($c['fecha_caducidad_licencia']).'</td>
        <td>'.htmlspecialchars($c['cargo'] ?? '-').'</td>
        <td>'.$dirInstitucional.'</td>
        <td>'.htmlspecialchars($c['grupo_sanguineo'] ?? '-').'</td>
        <td>'.htmlspecialchars($c['contacto_emergencia'] ?? '-').'</td>
        <td>'.htmlspecialchars($c['telefono_emergencia'] ?? '-').'</td>
        <td>'.htmlspecialchars($c['codigo_empleado'] ?? '-').'</td>
        <td>'.formatearFecha($c['fecha_ingreso']).'</td>
        <td class="'.$estadoClass.'">'.htmlspecialchars($c['estado'] ?? 'INACTIVO').'</td>
    </tr>';
}

$html .= '
</tbody>
</table>

<div class="meta">
    <p><strong>Fecha de emisión:</strong> '.date('d/m/Y H:i:s').'</p>
</div>

</body>
</html>
';

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Helvetica');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);

// Se mantiene tamaño Legal (Oficio) en formato Horizontal (Landscape) debido al volumen de columnas
$dompdf->setPaper('legal', 'landscape');
$dompdf->render();

$dompdf->stream(
    "Reporte_Choferes.pdf",
    ["Attachment" => false]
);