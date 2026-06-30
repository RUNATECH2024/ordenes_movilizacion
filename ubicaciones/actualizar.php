<?php
require_once '../includes/conexion.php';

$id_ubicacion = $_POST['id_ubicacion'];
$id_recinto = $_POST['id_recinto'];
$referencia = $_POST['referencia'];

try {
    $stmt = $conexion->prepare("UPDATE ubicaciones SET id_recinto = ?, referencia = ? WHERE id_ubicacion = ?");
    $stmt->execute([$id_recinto, $referencia, $id_ubicacion]);
    header("Location: index.php");
} catch (PDOException $e) {
    echo "❌ Error al actualizar: " . $e->getMessage();
}
