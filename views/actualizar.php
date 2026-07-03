<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}
require_once '../includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Acceso no permitido");
}

// Capturar datos y forzar tipado numérico donde corresponde
$id = !empty($_POST['id_orden']) ? intval($_POST['id_orden']) : null;
$numero_orden = trim($_POST['numero_orden'] ?? '');
$fecha = !empty($_POST['fecha_emision']) ? $_POST['fecha_emision'] : null;
$chofer = !empty($_POST['id_chofer']) ? intval($_POST['id_chofer']) : null;
$vehiculo = !empty($_POST['id_vehiculo']) ? intval($_POST['id_vehiculo']) : null;
$ubicacion = !empty($_POST['id_ubicacion']) ? intval($_POST['id_ubicacion']) : null;
$objeto = trim($_POST['objeto_movilizacion'] ?? '');
$dias = !empty($_POST['dias_movilizacion']) ? intval($_POST['dias_movilizacion']) : null;
$director = !empty($_POST['id_director']) ? intval($_POST['id_director']) : null;

// Validación estricta de todos los campos obligatorios
if (!$id || empty($numero_orden) || !$fecha || !$chofer || !$vehiculo || !$ubicacion || empty($objeto) || !$dias || !$director) {
    die("❌ Error: Faltan datos obligatorios para actualizar la orden.");
}

// Generar automáticamente el detalle de los días en español
$diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
$detalleDias = [];
$fechaBase = new DateTime($fecha);

for ($i = 0; $i < $dias; $i++) {
    $fechaTemp = clone $fechaBase;
    if ($i > 0) {
        $fechaTemp->modify("+$i day");
    }
    $nombreDia = $diasSemana[$fechaTemp->format('w')];
    $detalleDias[] = $nombreDia . " " . $fechaTemp->format('d');
}
$detalle_dias = implode(", ", $detalleDias);

try {
    // Consulta UPDATE incluyendo la columna numero_orden
    $query = $pdo->prepare("
        UPDATE ordenes_movilizacion SET
            numero_orden = :numero_orden,
            fecha_emision = :fecha,
            id_chofer = :chofer,
            id_vehiculo = :vehiculo,
            id_ubicacion = :ubicacion,
            objeto_movilizacion = :objeto,
            dias_movilizacion = :dias,
            detalle_dias = :detalle_dias,
            id_director = :director
        WHERE id_orden = :id
    ");

    $query->execute([
        ':numero_orden' => $numero_orden,
        ':fecha' => $fecha,
        ':chofer' => $chofer,
        ':vehiculo' => $vehiculo,
        ':ubicacion' => $ubicacion,
        ':objeto' => $objeto,
        ':dias' => $dias,
        ':detalle_dias' => $detalle_dias,
        ':director' => $director,
        ':id' => $id
    ]);

    header("Location: index.php?mensaje=actualizado");
    exit;
} catch (PDOException $e) {
    die("❌ Error al actualizar la orden de movilización: " . $e->getMessage());
}
?>