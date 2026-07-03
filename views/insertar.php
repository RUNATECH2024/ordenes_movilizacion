<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../includes/conexion.php';

    // Capturar y limpiar los valores del formulario
    $numero_orden = trim($_POST['numero_orden'] ?? '');
    $fecha_emision = !empty($_POST['fecha_emision']) ? $_POST['fecha_emision'] : null;
    $id_chofer = !empty($_POST['id_chofer']) ? intval($_POST['id_chofer']) : null;
    $id_vehiculo = !empty($_POST['id_vehiculo']) ? intval($_POST['id_vehiculo']) : null;
    $id_ubicacion = !empty($_POST['id_ubicacion']) ? intval($_POST['id_ubicacion']) : null;
    $objeto_movilizacion = trim($_POST['objeto_movilizacion'] ?? '');
    $dias_movilizacion = !empty($_POST['dias_movilizacion']) ? intval($_POST['dias_movilizacion']) : null;
    $id_director = !empty($_POST['id_director']) ? intval($_POST['id_director']) : null;
    $detalle_dias = trim($_POST['detalle_dias'] ?? '');

    // Validación rápida de campos obligatorios
    if (empty($numero_orden) || !$fecha_emision || !$id_chofer || !$id_vehiculo || !$id_ubicacion || empty($objeto_movilizacion) || !$dias_movilizacion || !$id_director) {
        die("❌ Error: Faltan datos obligatorios para procesar la orden.");
    }

    try {
        $sql = "INSERT INTO ordenes_movilizacion (
                    numero_orden, fecha_emision, id_chofer, id_vehiculo, 
                    id_ubicacion, objeto_movilizacion, dias_movilizacion, 
                    id_director, detalle_dias
                ) VALUES (
                    :numero_orden, :fecha_emision, :id_chofer, :id_vehiculo, 
                    :id_ubicacion, :objeto_movilizacion, :dias_movilizacion, 
                    :id_director, :detalle_dias
                )";

        $stmt = $pdo->prepare($sql);
        
        // Ejecución pasando los marcadores de posición con sus respectivos dos puntos (:)
        $stmt->execute([
            ':numero_orden' => $numero_orden,
            ':fecha_emision' => $fecha_emision,
            ':id_chofer' => $id_chofer,
            ':id_vehiculo' => $id_vehiculo,
            ':id_ubicacion' => $id_ubicacion,
            ':objeto_movilizacion' => $objeto_movilizacion,
            ':dias_movilizacion' => $dias_movilizacion,
            ':id_director' => $id_director,
            ':detalle_dias' => $detalle_dias
        ]);

        header("Location: index.php?mensaje=guardado");
        exit;
    } catch (PDOException $e) {
        die("❌ Error al guardar la orden de movilización: " . $e->getMessage());
    }
} else {
    header("Location: nueva_orden.php");
    exit;
}
?>