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

try {
    // CORRECCIÓN: Se agrega el JOIN a empleados y se concatenan adecuadamente sus campos de nombres
    $query = $pdo->query("
        SELECT o.numero_orden, o.fecha_emision, 
               c.nombres || ' ' || c.apellidos AS chofer,
               v.placa, r.nombre AS recinto, p.nombre AS parroquia,
               o.objeto_movilizacion, o.dias_movilizacion,
               e.primer_nombre || ' ' || COALESCE(e.segundo_nombre, '') || ' ' || e.primer_apellido || ' ' || COALESCE(e.segundo_apellido, '') AS director
        FROM ordenes_movilizacion o
        JOIN choferes c ON o.id_chofer = c.id_chofer
        JOIN vehiculos v ON o.id_vehiculo = v.id_vehiculo
        JOIN ubicaciones u ON o.id_ubicacion = u.id_ubicacion
        JOIN recintos r ON u.id_recinto = r.id_recinto
        JOIN parroquias p ON r.id_parroquia = p.id_parroquia
        JOIN directores d ON o.id_director = d.id_director
        JOIN empleados e ON d.id_empleado = e.id_empleado
        ORDER BY o.fecha_emision DESC
    ");
    $ordenes = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error en la base de datos: " . $e->getMessage());
}

// Insertar datos desde la fila 2
$fila = 2;
foreach ($ordenes as $orden) {
    // Formatear la fecha para que se vea más limpia en Excel (DD/MM/AAAA)
    $fechaOk = date("d/m/Y", strtotime($orden['fecha_emision']));

    $sheet->setCellValue("A$fila", $orden['numero_orden']);
    $sheet->setCellValue("B$fila", $fechaOk);
    $sheet->setCellValue("C$fila", $orden['chofer']);
    $sheet->setCellValue("D$fila", $orden['placa']);
    $sheet->setCellValue("E$fila", $orden['recinto']);
    $sheet->setCellValue("F$fila", $orden['parroquia']);
    $sheet->setCellValue("G$fila", $orden['objeto_movilizacion']);
    $sheet->setCellValue("H$fila", $orden['dias_movilizacion']);
    // Reemplaza los múltiples espacios que COALESCE pueda dejar si faltaba el segundo nombre
    $sheet->setCellValue("I$fila", preg_replace('/\s+/', ' ', trim($orden['director'])));
    $fila++;
}

// Descargar como archivo Excel
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="ordenes_movilizacion.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
