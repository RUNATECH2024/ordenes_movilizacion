<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'La sesión ha expirado.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once '../includes/conexion.php';

$idChofer = filter_input(INPUT_GET, 'id_chofer', FILTER_VALIDATE_INT);

if (!$idChofer) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'El chofer seleccionado no es válido.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // La asignación actual se encuentra en vehiculos.id_chofer.
    $sql = "
        SELECT
            v.id_vehiculo,
            v.placa,
            v.marca,
            v.modelo,
            CONCAT_WS(
                ' - ',
                NULLIF(BTRIM(v.placa), ''),
                NULLIF(BTRIM(CONCAT_WS(' ', v.marca, v.modelo)), '')
            ) AS descripcion
        FROM vehiculos v
        INNER JOIN choferes c ON c.id_chofer = v.id_chofer
        WHERE v.id_chofer = :id_chofer
          AND UPPER(COALESCE(c.estado, 'ACTIVO')) = 'ACTIVO'
        ORDER BY v.id_vehiculo DESC
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_chofer' => $idChofer]);
    $vehiculo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vehiculo) {
        echo json_encode([
            'success' => false,
            'message' => 'Este chofer no tiene un vehículo asignado.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'success' => true,
        'vehiculo' => [
            'id_vehiculo' => (int)$vehiculo['id_vehiculo'],
            'placa' => $vehiculo['placa'],
            'marca' => $vehiculo['marca'],
            'modelo' => $vehiculo['modelo'],
            'descripcion' => $vehiculo['descripcion']
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Ocurrió un error al consultar el vehículo asignado.'
    ], JSON_UNESCAPED_UNICODE);
}
