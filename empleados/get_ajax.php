<?php
// empleados/get_ajax.php
session_start();

// Validar que el usuario esté autenticado antes de responder datos del sistema
if (!isset($_SESSION['usuario'])) {
    header('Content-Type: application/json');
    echo json_encode(["error" => "No autorizado"]);
    exit;
}

require_once __DIR__ . '/../includes/conexion.php';

header('Content-Type: application/json; charset=utf-8');

$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    echo json_encode([]);
    exit;
}

try {
    switch ($tipo) {
        // 1. CARGAR CANTÓNES / CIUDADES SEGÚN LA PROVINCIA
        case 'ciudades':
            $stmt = $pdo->prepare("SELECT id_ciudad, nombre FROM ciudades WHERE id_provincia = ? ORDER BY nombre");
            $stmt->execute([$id]);
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($resultados);
            break;

        // 2. CARGAR PARROQUIAS SEGÚN EL CANTÓN / CIUDAD
        case 'parroquias':
            $stmt = $pdo->prepare("SELECT id_parroquia, nombre FROM parroquias WHERE id_ciudad = ? ORDER BY nombre");
            $stmt->execute([$id]);
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($resultados);
            break;

        // 3. CARGAR JEFATURAS / DEPARTAMENTOS SEGÚN LA DIRECCIÓN MACRO
        case 'jefaturas':
            $stmt = $pdo->prepare("SELECT id_jefatura, nombre FROM jefaturas WHERE id_direccion = ? AND estado = 'ACTIVO' ORDER BY nombre");
            $stmt->execute([$id]);
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($resultados);
            break;

        // 4. CARGAR CARGOS OPERATIVOS SEGÚN LA JEFATURA / DEPARTAMENTO
        case 'cargos':
            $stmt = $pdo->prepare("SELECT id_cargo, nombre FROM cargos WHERE id_jefatura = ? ORDER BY nombre");
            $stmt->execute([$id]);
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($resultados);
            break;

        default:
            echo json_encode([]);
            break;
    }
} catch (PDOException $e) {
    // Retornar un formato JSON limpio en caso de fallo interno en la base de datos
    echo json_encode(["error" => "Error al consultar los datos dinámicos"]);
}
exit;