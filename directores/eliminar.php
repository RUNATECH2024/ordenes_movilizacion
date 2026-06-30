<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../includes/conexion.php';

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    header("Location: index.php?error=id");
    exit;
}

try {

    // Verificar si existe
    $verificar = $pdo->prepare("
        SELECT id_director
        FROM directores
        WHERE id_director = ?
    ");

    $verificar->execute([$id]);

    if (!$verificar->fetch()) {
        header("Location: index.php?error=noexiste");
        exit;
    }

    // Eliminar
    $stmt = $pdo->prepare("
        DELETE FROM directores
        WHERE id_director = ?
    ");

    $stmt->execute([$id]);

    header("Location: index.php?eliminado=1");
    exit;

} catch (PDOException $e) {

    // PostgreSQL Foreign Key
    if ($e->getCode() == '23503') {

        header("Location: index.php?referenciado=1");
        exit;

    } else {

        die("Error al eliminar director: " . $e->getMessage());

    }
}
?>