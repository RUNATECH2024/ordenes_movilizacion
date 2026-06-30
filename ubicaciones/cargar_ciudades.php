<?php
require_once '../includes/conexion.php';

header('Content-Type: application/json');

$id_provincia = $_GET['id_provincia'] ?? null;

if (!$id_provincia) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id_ciudad, nombre FROM ciudades WHERE id_provincia = :id_provincia ORDER BY nombre");
    $stmt->execute(['id_provincia' => $id_provincia]);
    $ciudades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($ciudades);
} catch (PDOException $e) {
    echo json_encode([]);
}
