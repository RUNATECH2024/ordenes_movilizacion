<?php
require_once '../includes/conexion.php';
require_once '../vendor/autoload.php'; // Asegúrate de tener Dompdf instalado por Composer

use Dompdf\Dompdf;
use Dompdf\Options;

// Configuración de DomPDF
$options = new Options();
$options->set('defaultFont', 'Helvetica');
$options->set('isHtml5ParserEnabled', true); // Mejor soporte para HTML5
$options->set('isRemoteEnabled', true); // Permite usar imágenes externas si las agregas

$dompdf = new Dompdf($options);

// Consulta de órdenes
try {
    $query = $pdo->query("
        SELECT o.numero_orden, o.fecha_emision, 
               c.nombres || ' ' || c.apellidos AS chofer,
               v.placa, r.nombre AS recinto, p.nombre AS parroquia,
               o.objeto_movilizacion, o.dias_movilizacion,
               d.nombres || ' ' || d.apellidos AS director
        FROM ordenes_movilizacion o
        JOIN choferes c ON o.id_chofer = c.id_chofer
        JOIN vehiculos v ON o.id_vehiculo = v.id_vehiculo
        JOIN ubicaciones u ON o.id_ubicacion = u.id_ubicacion
        JOIN recintos r ON u.id_recinto = r.id_recinto
        JOIN parroquias p ON r.id_parroquia = p.id_parroquia
        JOIN directores d ON o.id_director = d.id_director
        ORDER BY o.fecha_emision DESC
    ");

    $ordenes = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al consultar las órdenes: " . $e->getMessage());
}

// Estilos y estructura HTML
$html = '
<style>
    body { font-family: Helvetica, sans-serif; font-size: 10px; }
    h2 { text-align: center; margin-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #999; padding: 5px; text-align: center; }
    thead { background-color: #f0f0f0; font-weight: bold; }
</style>

<h2>📄 Órdenes de Movilización</h2>
<table>
    <thead>
        <tr>
            <th># Orden</th>
            <th>Fecha</th>
            <th>Chofer</th>
            <th>Vehículo</th>
            <th>Recinto / Parroquia</th>
            <th>Objeto</th>
            <th>Días</th>
            <th>Director</th>
        </tr>
    </thead>
    <tbody>';

foreach ($ordenes as $o) {
    $html .= '<tr>
        <td>' . htmlspecialchars($o['numero_orden']) . '</td>
        <td>' . date("d/m/Y", strtotime($o['fecha_emision'])) . '</td>
        <td>' . htmlspecialchars($o['chofer']) . '</td>
        <td>' . htmlspecialchars($o['placa']) . '</td>
        <td>' . htmlspecialchars($o['recinto']) . ' / ' . htmlspecialchars($o['parroquia']) . '</td>
        <td>' . htmlspecialchars($o['objeto_movilizacion']) . '</td>
        <td>' . htmlspecialchars($o['dias_movilizacion']) . '</td>
        <td>' . htmlspecialchars($o['director']) . '</td>
    </tr>';
}

$html .= '</tbody></table>';

// Cargar HTML al PDF
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// Mostrar en navegador sin forzar descarga
$dompdf->stream("ordenes_movilizacion.pdf", ["Attachment" => false]);
exit;
