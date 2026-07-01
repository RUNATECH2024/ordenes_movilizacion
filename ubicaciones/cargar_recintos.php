<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once '../includes/conexion.php';
    $id_parroquia = $_GET['id_parroquia'] ?? '';

    if (!empty($id_parroquia)) {
        // NOTA: Asegúrate de que las columnas se llamen id_recinto, nombre e id_parroquia en tu tabla 'recintos'
        $stmt = $pdo->prepare("SELECT id_recinto, nombre FROM recintos WHERE id_parroquia = ? ORDER BY nombre");
        $stmt->execute([$id_parroquia]);
        $recintos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($recintos, JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => true, 'mensaje' => $e->getMessage()]);
}
exit;