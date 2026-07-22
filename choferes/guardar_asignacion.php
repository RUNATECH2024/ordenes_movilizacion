<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$idChofer = filter_input(INPUT_POST, 'id_chofer', FILTER_VALIDATE_INT);
$idVehiculo = filter_input(INPUT_POST, 'id_vehiculo', FILTER_VALIDATE_INT);

// Cuando el select llega vacío, FILTER_VALIDATE_INT devuelve false.
$idVehiculo = $idVehiculo ?: null;

if (!$idChofer) {
    header('Location: index.php?error=chofer_invalido');
    exit;
}

try {
    $pdo->beginTransaction();

    // Verificar que el chofer proviene de Empleados y mantiene el cargo activo.
    $stmtChofer = $pdo->prepare("
        SELECT ch.id_chofer, ch.id_empleado
        FROM choferes ch
        JOIN empleados e ON e.id_empleado = ch.id_empleado
        WHERE ch.id_chofer = ?
          AND e.estado = 'ACTIVO'
          AND UPPER(COALESCE(ch.estado, 'ACTIVO')) = 'ACTIVO'
          AND EXISTS (
              SELECT 1
              FROM historial_laboral hl
              JOIN cargos c ON c.id_cargo = hl.id_cargo
              WHERE hl.id_empleado = e.id_empleado
                AND hl.activo = TRUE
                AND (
                    UPPER(c.nombre) LIKE '%CHOFER%'
                    OR UPPER(c.nombre) LIKE '%CONDUCTOR%'
                )
          )
        FOR UPDATE OF ch
    ");
    $stmtChofer->execute([$idChofer]);
    $chofer = $stmtChofer->fetch(PDO::FETCH_ASSOC);

    if (!$chofer) {
        $pdo->rollBack();
        header('Location: index.php?error=chofer_invalido');
        exit;
    }

    // Tomar y bloquear cualquier vehículo que actualmente tenga el chofer.
    $stmtActuales = $pdo->prepare("
        SELECT id_vehiculo
        FROM vehiculos
        WHERE id_chofer = ?
        ORDER BY id_vehiculo
        FOR UPDATE
    ");
    $stmtActuales->execute([$idChofer]);
    $vehiculosActuales = array_map('intval', $stmtActuales->fetchAll(PDO::FETCH_COLUMN));
    $idVehiculoAnterior = $vehiculosActuales[0] ?? null;

    if ($idVehiculo !== null) {
        // Bloquear el vehículo seleccionado y comprobar que esté libre o ya sea de este chofer.
        $stmtVehiculo = $pdo->prepare("
            SELECT id_vehiculo, id_chofer
            FROM vehiculos
            WHERE id_vehiculo = ?
            FOR UPDATE
        ");
        $stmtVehiculo->execute([$idVehiculo]);
        $vehiculo = $stmtVehiculo->fetch(PDO::FETCH_ASSOC);

        if (!$vehiculo || ($vehiculo['id_chofer'] !== null && (int)$vehiculo['id_chofer'] !== $idChofer)) {
            $pdo->rollBack();
            header('Location: index.php?error=vehiculo_ocupado');
            exit;
        }
    }

    $sinCambios = count($vehiculosActuales) === 1
        && $idVehiculoAnterior === $idVehiculo;

    if ($sinCambios) {
        $pdo->commit();
        header('Location: index.php?mensaje=sin_cambios');
        exit;
    }

    // Cerrar el historial activo anterior del chofer.
    $stmtCerrarHistorial = $pdo->prepare("
        UPDATE vehiculo_chofer
        SET fecha_fin = CURRENT_DATE
        WHERE id_chofer = ?
          AND fecha_fin IS NULL
    ");
    $stmtCerrarHistorial->execute([$idChofer]);

    // Liberar cualquier vehículo que estuviera asociado al chofer.
    $stmtLiberar = $pdo->prepare("UPDATE vehiculos SET id_chofer = NULL WHERE id_chofer = ?");
    $stmtLiberar->execute([$idChofer]);

    if ($idVehiculo !== null) {
        // Cerrar por seguridad una asignación histórica activa del vehículo seleccionado.
        $stmtCerrarVehiculo = $pdo->prepare("
            UPDATE vehiculo_chofer
            SET fecha_fin = CURRENT_DATE
            WHERE id_vehiculo = ?
              AND fecha_fin IS NULL
        ");
        $stmtCerrarVehiculo->execute([$idVehiculo]);

        $stmtAsignar = $pdo->prepare("
            UPDATE vehiculos
            SET id_chofer = ?
            WHERE id_vehiculo = ?
              AND id_chofer IS NULL
        ");
        $stmtAsignar->execute([$idChofer, $idVehiculo]);

        if ($stmtAsignar->rowCount() !== 1) {
            throw new RuntimeException('El vehículo ya no se encuentra disponible.');
        }

        $stmtHistorial = $pdo->prepare("
            INSERT INTO vehiculo_chofer (id_vehiculo, id_chofer, fecha_asignacion, fecha_fin)
            VALUES (?, ?, CURRENT_DATE, NULL)
        ");
        $stmtHistorial->execute([$idVehiculo, $idChofer]);
    }

    // La dirección del chofer no es fija: la define el vehículo asignado.
    $stmtLimpiarDireccion = $pdo->prepare("UPDATE choferes SET id_direccion = NULL WHERE id_chofer = ?");
    $stmtLimpiarDireccion->execute([$idChofer]);

    $pdo->commit();

    header('Location: index.php?mensaje=' . ($idVehiculo !== null ? 'asignado' : 'liberado'));
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Error al asignar vehículo a chofer: ' . $e->getMessage());
    header('Location: index.php?error=general');
    exit;
}
