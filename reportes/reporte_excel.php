<?php
require_once '../includes/conexion.php';
require_once '../vendor/autoload.php'; // Autoload Composer

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Crear hoja de cálculo
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Órdenes de Movilización');

// Encabezados
$sheet->fromArray([
    'N° Orden', 'Fecha', 'Chofer', 'Vehículo', 'Recinto', 'Parroquia',
    'Objeto', 'Días', 'Director'
], null, 'A1');

// Consultar datos
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

// Insertar datos desde la fila 2
$fila = 2;
foreach ($ordenes as $orden) {
    $sheet->setCellValue("A$fila", $orden['numero_orden']);
    $sheet->setCellValue("B$fila", $orden['fecha_emision']);
    $sheet->setCellValue("C$fila", $orden['chofer']);
    $sheet->setCellValue("D$fila", $orden['placa']);
    $sheet->setCellValue("E$fila", $orden['recinto']);
    $sheet->setCellValue("F$fila", $orden['parroquia']);
    $sheet->setCellValue("G$fila", $orden['objeto_movilizacion']);
    $sheet->setCellValue("H$fila", $orden['dias_movilizacion']);
    $sheet->setCellValue("I$fila", $orden['director']);
    $fila++;
}

// Descargar como archivo Excel
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="ordenes_movilizacion.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
