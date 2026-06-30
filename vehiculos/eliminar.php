<?php
require_once '../includes/conexion.php';

$id = $_GET['id'] ?? null;

if ($id && is_numeric($id)) {
    $stmt = $pdo->prepare("DELETE FROM vehiculos WHERE id_vehiculo = ?");
    $stmt->execute([$id]);
}

header('Location: index.php');
exit;
?>
