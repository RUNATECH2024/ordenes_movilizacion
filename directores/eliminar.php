<?php
// directores/eliminar.php
session_start();
if (!isset($_SESSION['usuario'])) { header("Location: ../auth/login.php"); exit; }
require_once '../includes/conexion.php';

$id_director = $_GET['id'] ?? null;

if ($id_director && is_numeric($id_director)) {
    try {
        // Borramos el registro de asignación
        $stmt = $pdo->prepare("DELETE FROM directores WHERE id_director = ?");
        $stmt->execute([$id_director]);
        
        header("Location: index.php?ok=1");
        exit;
    } catch (PDOException $e) {
        // Captura si está enlazado por llave foránea a otras estructuras restrictivas
        die("No se puede eliminar la asignación de este director porque tiene dependencias históricas activas en el sistema. Considere cambiar su estado a 'INACTIVO' desde la opción editar.");
    }
} else {
    die("ID de director no válido o ausente.");
}