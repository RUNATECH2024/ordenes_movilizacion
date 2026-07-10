<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . '/../includes/conexion.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID de permiso no válido");
}

try {
    // Consulta para traer los datos del permiso, el empleado que lo solicita y los firmantes
    $query = $pdo->prepare("
        SELECT p.*,
               e.primer_nombre || ' ' || e.primer_apellido AS empleado_nombre,
               e.cedula AS empleado_cedula,
               -- Traemos el nombre del jefe que validó
               ej.primer_nombre || ' ' || ej.primer_apellido AS jefe_nombre,
               -- Traemos el nombre del director que legalizó
               ed.primer_nombre || ' ' || ed.primer_apellido AS director_nombre
        FROM permisos_ocasionales p
        JOIN empleados e ON p.id_empleado = e.id_empleado
        LEFT JOIN empleados ej ON p.id_jefe_valida = ej.id_empleado
        LEFT JOIN empleados ed ON p.id_director_legaliza = ed.id_empleado
        WHERE p.id_permiso = :id
    ");
    $query->execute([':id' => $id]);
    $permiso = $query->fetch(PDO::FETCH_ASSOC);

    if (!$permiso) {
        die("Permiso no encontrado");
    }

} catch (PDOException $e) {
    die("Error en la base de datos: " . $e->getMessage());
}

$logoPath = realpath(__DIR__ . "/../assets/img/logo.png");
$fechaSolicitud = date("d/m/Y", strtotime($permiso['fecha_solicitud'] ?? 'now'));

// Documento HTML para la papeleta
$html = "
<style>
    body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #333; margin: 15px; }
    .encabezado { width: 100%; border-bottom: 2px solid #000; margin-bottom: 20px; padding-bottom: 5px; }
    .titulo { text-align: center; font-size: 14px; font-weight: bold; }
    .tabla-datos { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .tabla-datos td { padding: 8px; border: 1px solid #ccc; }
    .seccion-titulo { background-color: #f2f2f2; font-weight: bold; }
    .firmas-tabla { width: 100%; margin-top: 50px; text-align: center; }
    .firmas-tabla td { width: 33%; vertical-align: bottom; }
    .linea { width: 80%; border-top: 1px solid #000; margin: 0 auto 5px auto; }
</style>

<table class='encabezado'>
    <tr>
        <td width='20%'>" . ($logoPath ? "<img src='file://{$logoPath}' style='width:60px;'>" : "") . "</td>
        <td class='titulo'>GOBIERNO AUTÓNOMO DESCENTRALIZADO PROVINCIAL DE BOLÍVAR<br><span style='font-size:12px;'>PAPELETA DE PERMISO OCASIONAL</span></td>
    </tr>
</table>

<p align='right'><strong>Fecha:</strong> $fechaSolicitud</p>

<table class='tabla-datos'>
    <tr>
        <td class='seccion-titulo' colspan='2'>DATOS DEL EMPLEADO</td>
    </tr>
    <tr>
        <td><strong>Servidor(a):</strong> " . htmlspecialchars($permiso['empleado_nombre']) . "</td>
        <td><strong>Cédula:</strong> " . htmlspecialchars($permiso['empleado_cedula']) . "</td>
    </tr>
    <tr>
        <td class='seccion-titulo' colspan='2'>DETALLE DEL PERMISO</td>
    </tr>
    <tr>
        <td colspan='2'><strong>Motivo / Objeto:</strong> " . htmlspecialchars($permiso['motivo'] ?? 'Permiso Institucional') . "</td>
    </tr>
    <tr>
        <td><strong>Horas/Días Solicitados:</strong> " . htmlspecialchars($permiso['tiempo_solicitado'] ?? '1 día') . "</td>
        <td><strong>Estado de Legalización:</strong> " . htmlspecialchars($permiso['estado_legalizacion']) . "</td>
    </tr>
</table>

<table class='firmas-tabla'>
    <tr>
        <td>
            <div class='linea'></div>
            <strong>" . htmlspecialchars($permiso['empleado_nombre']) . "</strong><br>
            Solicitante
        </td>
        <td>
            <div class='linea'></div>
            <strong>" . htmlspecialchars($permiso['jefe_nombre'] ?? 'S/F') . "</strong><br>
            Firma Jefe Inmediato<br>
            <small>(" . htmlspecialchars($permiso['firma_jefe_estado'] ?? 'PENDIENTE') . ")</small>
        </td>
        <td>
            <div class='linea'></div>
            <strong>" . htmlspecialchars($permiso['director_nombre'] ?? 'S/F') . "</strong><br>
            Firma Director / TTHH<br>
            <small>(" . htmlspecialchars($permiso['firma_director_estado'] ?? 'PENDIENTE') . ")</small>
        </td>
    </tr>
</table>
";

$options = new Options();
$options->set('defaultFont', 'Helvetica');
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$dompdf->stream("permiso_" . $id . ".pdf", ["Attachment" => false]);
exit;