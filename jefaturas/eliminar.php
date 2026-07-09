<?php
// jefaturas/eliminar.php
session_start();
if (!isset($_SESSION['usuario'])) { header("Location: ../auth/login.php"); exit; }
require_once '../includes/conexion.php';

$id_jefatura = $_GET['id'] ?? null;

if ($id_jefatura && is_numeric($id_jefatura)) {
    try {
        $stmt = $pdo->prepare("DELETE FROM jefaturas WHERE id_jefatura = ?");
        $stmt->execute([$id_jefatura]);
        
        header("Location: index.php?ok=1");
        exit;
    } catch (PDOException $e) {
        // Por si está enlazado a otras tablas (ej. órdenes de movilización)
        die("No se puede eliminar esta jefatura porque tiene registros históricos asociados. Pruebe cambiándole el estado a INACTIVO desde el botón editar.");
    }
} else {
    die("ID inválido.");
}