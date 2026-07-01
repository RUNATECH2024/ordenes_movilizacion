<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once '../includes/conexion.php';
    $id_ciudad = $_GET['id_ciudad'] ?? '';

    if (!empty($id_ciudad)) {
        // NOTA: Cambia 'tipo' si tu tabla de parroquias no cuenta con esa columna
        $stmt = $pdo->prepare("SELECT id_parroquia, nombre, tipo FROM parroquias WHERE id_ciudad = ? ORDER BY nombre");
        $stmt->execute([$id_ciudad]);
        $parroquias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($parroquias, JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => true, 'mensaje' => $e->getMessage()]);
}
exit;