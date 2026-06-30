<?php
require_once '../includes/conexion.php';

// Obtener ID
$id = $_GET['id'] ?? null;

// Validar ID
if (!$id || !is_numeric($id)) {
    die("ID no válido");
}

try {

    // Verificar si la orden existe
    $verificar = $pdo->prepare("
        SELECT id_orden
        FROM ordenes_movilizacion
        WHERE id_orden = :id
    ");

    $verificar->execute([
        ':id' => $id
    ]);

    if (!$verificar->fetch()) {
        die("La orden no existe.");
    }

    // Eliminar orden
    $query = $pdo->prepare("
        DELETE FROM ordenes_movilizacion
        WHERE id_orden = :id
    ");

    $query->execute([
        ':id' => $id
    ]);

    header("Location: index.php?mensaje=eliminado");
    exit;

} catch (PDOException $e) {

    die(
        "Error al eliminar: " .
        $e->getMessage()
    );

}
?>