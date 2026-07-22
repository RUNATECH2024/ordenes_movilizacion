<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../includes/conexion.php';

$idChofer = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idChofer) {
    header('Location: index.php?mensaje=gestion_empleados');
    exit;
}

$stmt = $pdo->prepare('SELECT id_empleado FROM choferes WHERE id_chofer = ?');
$stmt->execute([$idChofer]);
$idEmpleado = $stmt->fetchColumn();

if ($idEmpleado) {
    header('Location: ../empleados/editar.php?id=' . (int)$idEmpleado);
} else {
    header('Location: index.php?mensaje=gestion_empleados');
}
exit;
