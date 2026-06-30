<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . '/../includes/conexion.php';

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    die("ID no válido");
}

try {

    /* =========================
       VERIFICAR SI EXISTE
    ========================= */
    $stmt = $pdo->prepare("
        SELECT * 
        FROM direcciones 
        WHERE id_direccion = ?
    ");

    $stmt->execute([$id]);

    $direccion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$direccion) {
        die("Dirección no encontrada");
    }

    /* =========================
       VALIDAR RELACIONES (OPCIONAL)
       Evita borrar si está en uso
    ========================= */

    $check = $pdo->prepare("
        SELECT COUNT(*) 
        FROM directores 
        WHERE id_direccion = ?
    ");

    $check->execute([$id]);

    $tieneDirectores = $check->fetchColumn();

    if ($tieneDirectores > 0) {
        die("❌ No se puede eliminar: la dirección tiene directores asignados.");
    }

    /* =========================
       ELIMINAR
    ========================= */

    $delete = $pdo->prepare("
        DELETE FROM direcciones 
        WHERE id_direccion = ?
    ");

    $delete->execute([$id]);

    header("Location: index.php?delete=1");
    exit;

} catch (PDOException $e) {

    die("Error al eliminar dirección: " . $e->getMessage());
}
?>