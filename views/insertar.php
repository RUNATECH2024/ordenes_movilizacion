<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: nueva_orden.php");
    exit;
}

require_once '../includes/conexion.php';

// Capturar y limpiar los valores del formulario.
$numero_orden        = trim($_POST['numero_orden'] ?? '');
$fecha_emision       = !empty($_POST['fecha_emision']) ? $_POST['fecha_emision'] : null;
$id_chofer           = !empty($_POST['id_chofer']) ? (int)$_POST['id_chofer'] : null;
$id_vehiculo         = !empty($_POST['id_vehiculo']) ? (int)$_POST['id_vehiculo'] : null;
$id_ubicacion        = !empty($_POST['id_ubicacion']) ? (int)$_POST['id_ubicacion'] : null;
$objeto_movilizacion = trim($_POST['objeto_movilizacion'] ?? '');
$dias_movilizacion   = !empty($_POST['dias_movilizacion']) ? (int)$_POST['dias_movilizacion'] : null;
$id_direccion        = !empty($_POST['id_direccion']) ? (int)$_POST['id_direccion'] : null;
$id_director         = !empty($_POST['id_director']) ? (int)$_POST['id_director'] : null;
$detalle_dias        = trim($_POST['detalle_dias'] ?? '');

if (
    empty($numero_orden) ||
    !$fecha_emision ||
    !$id_chofer ||
    !$id_vehiculo ||
    !$id_ubicacion ||
    empty($objeto_movilizacion) ||
    !$dias_movilizacion ||
    !$id_direccion ||
    !$id_director
) {
    die("❌ Error: Faltan datos obligatorios para procesar la orden.");
}

try {
    // Seguridad de negocio: el vehículo enviado debe ser el que está
    // asignado al chofer seleccionado en la tabla vehiculos.
    $validarVehiculo = $pdo->prepare("
        SELECT 1
        FROM vehiculos v
        INNER JOIN choferes c ON c.id_chofer = v.id_chofer
        WHERE v.id_vehiculo = :id_vehiculo
          AND v.id_chofer = :id_chofer
          AND UPPER(COALESCE(c.estado, 'ACTIVO')) = 'ACTIVO'
        LIMIT 1
    ");

    $validarVehiculo->execute([
        ':id_vehiculo' => $id_vehiculo,
        ':id_chofer' => $id_chofer
    ]);

    if (!$validarVehiculo->fetchColumn()) {
        die("❌ Error: El vehículo no corresponde al chofer seleccionado o el chofer no está activo.");
    }

    // Seguridad de negocio: comprobar que el director enviado realmente
    // pertenece a la Secretaría/Dirección seleccionada y está activo.
    $validarDirector = $pdo->prepare("
        SELECT 1
        FROM directores d
        INNER JOIN empleados e ON e.id_empleado = d.id_empleado
        WHERE d.id_director = :id_director
          AND d.id_direccion = :id_direccion
          AND UPPER(COALESCE(d.estado, 'ACTIVO')) = 'ACTIVO'
          AND UPPER(COALESCE(e.estado, 'ACTIVO')) = 'ACTIVO'
        LIMIT 1
    ");

    $validarDirector->execute([
        ':id_director' => $id_director,
        ':id_direccion' => $id_direccion
    ]);

    if (!$validarDirector->fetchColumn()) {
        die("❌ Error: El director seleccionado no corresponde a la Secretaría o Dirección indicada.");
    }

    $sql = "INSERT INTO ordenes_movilizacion (
                numero_orden,
                fecha_emision,
                id_chofer,
                id_vehiculo,
                id_ubicacion,
                objeto_movilizacion,
                dias_movilizacion,
                id_director,
                detalle_dias
            ) VALUES (
                :numero_orden,
                :fecha_emision,
                :id_chofer,
                :id_vehiculo,
                :id_ubicacion,
                :objeto_movilizacion,
                :dias_movilizacion,
                :id_director,
                :detalle_dias
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':numero_orden'        => $numero_orden,
        ':fecha_emision'       => $fecha_emision,
        ':id_chofer'           => $id_chofer,
        ':id_vehiculo'         => $id_vehiculo,
        ':id_ubicacion'        => $id_ubicacion,
        ':objeto_movilizacion' => $objeto_movilizacion,
        ':dias_movilizacion'   => $dias_movilizacion,
        ':id_director'         => $id_director,
        ':detalle_dias'        => $detalle_dias
    ]);

    header("Location: index.php?mensaje=guardado");
    exit;

} catch (PDOException $e) {
    die("❌ Error al guardar la orden de movilización: " . $e->getMessage());
}
