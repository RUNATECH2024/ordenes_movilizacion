<?php
// permisos/get_datos_dinamicos.php
session_start();
if (!isset($_SESSION['usuario'])) { exit; }

require_once __DIR__ . '/../includes/conexion.php';

$action = $_GET['action'] ?? '';

if ($action === 'get_jefaturas') {
    $id_direccion = (int)($_GET['id_direccion'] ?? 0);
    $stmt = $pdo->prepare("SELECT id_jefatura, nombre FROM jefaturas WHERE id_direccion = :id AND estado = 'ACTIVO' ORDER BY nombre ASC");
    $stmt->execute([':id' => $id_direccion]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

if ($action === 'get_empleados') {
    $id_jefatura = (int)($_GET['id_jefatura'] ?? 0);
    // Busca los empleados asignados al cargo que pertenece a la jefatura seleccionada
    $stmt = $pdo->prepare("
        SELECT e.id_empleado, e.cedula, e.primer_apellido, e.primer_nombre 
        FROM empleados e
        JOIN historial_laboral hl ON e.id_empleado = hl.id_empleado
        JOIN cargos c ON hl.id_cargo = c.id_cargo
        WHERE c.id_jefatura = :id_jefatura AND hl.activo = true AND e.estado = 'ACTIVO'
        ORDER BY e.primer_apellido ASC
    ");
    $stmt->execute([':id_jefatura' => $id_jefatura]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}