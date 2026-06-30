<?php
require_once '../includes/conexion.php';

$id = $_GET['id'] ?? null;

if ($id && is_numeric($id)) {
    $stmt = $pdo->prepare("DELETE FROM choferes WHERE id_chofer = ?");
    $stmt->execute([$id]);
}

header('Location: index.php');
exit;
?>
