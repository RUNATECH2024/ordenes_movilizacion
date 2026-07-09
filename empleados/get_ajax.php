<?php
require_once __DIR__ . '/../includes/conexion.php';

$tipo = $_GET['tipo'] ?? '';
$id = intval($_GET['id'] ?? 0);
$response = [];

if ($tipo == 'jefaturas' && $id > 0) {
    $stmt = $pdo->prepare("SELECT id_jefatura, nombre FROM jefaturas WHERE id_direccion = ? AND estado = 'ACTIVO' ORDER BY nombre");
    $stmt->execute([$id]);
    $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($tipo == 'cargos' && $id > 0) {
    $stmt = $pdo->prepare("SELECT id_cargo, nombre FROM cargos WHERE id_jefatura = ? AND estado = 'ACTIVO' ORDER BY nombre");
    $stmt->execute([$id]);
    $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

header('Content-Type: application/json');
echo json_encode($response);