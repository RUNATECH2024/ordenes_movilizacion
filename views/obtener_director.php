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

$idDireccion = filter_input(INPUT_GET, 'id_direccion', FILTER_VALIDATE_INT);

if (!$idDireccion) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'La Secretaría o Dirección seleccionada no es válida.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $sql = "
        SELECT
            d.id_director,
            CONCAT_WS(
                ' ',
                NULLIF(BTRIM(e.primer_nombre), ''),
                NULLIF(BTRIM(e.segundo_nombre), ''),
                NULLIF(BTRIM(e.primer_apellido), ''),
                NULLIF(BTRIM(e.segundo_apellido), '')
            ) AS nombre
        FROM directores d
        INNER JOIN empleados e ON e.id_empleado = d.id_empleado
        WHERE d.id_direccion = :id_direccion
          AND UPPER(COALESCE(d.estado, 'ACTIVO')) = 'ACTIVO'
          AND UPPER(COALESCE(e.estado, 'ACTIVO')) = 'ACTIVO'
        ORDER BY d.fecha_inicio DESC NULLS LAST, d.id_director DESC
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_direccion' => $idDireccion]);
    $director = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$director) {
        echo json_encode([
            'success' => false,
            'message' => 'Esta Secretaría o Dirección no tiene un director activo asignado.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'success' => true,
        'director' => [
            'id_director' => (int)$director['id_director'],
            'nombre' => trim($director['nombre'])
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Ocurrió un error al consultar el director responsable.'
    ], JSON_UNESCAPED_UNICODE);
}
