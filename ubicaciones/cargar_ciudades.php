<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once '../includes/conexion.php';
    $id_provincia = $_GET['id_provincia'] ?? '';

    if (!empty($id_provincia)) {
        // NOTA: Asegúrate de que las columnas se llamen id_ciudad, nombre e id_provincia en tu base de datos
        $stmt = $pdo->prepare("SELECT id_ciudad, nombre FROM ciudades WHERE id_provincia = ? ORDER BY nombre");
        $stmt->execute([$id_provincia]);
        $ciudades = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($ciudades, JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => true, 'mensaje' => $e->getMessage()]);
}
exit;