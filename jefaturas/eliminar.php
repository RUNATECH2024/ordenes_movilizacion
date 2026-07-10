<?php
// jefaturas/eliminar.php
session_start();
if (!isset($_SESSION['usuario'])) { header("Location: ../auth/login.php"); exit; }
require_once '../includes/conexion.php';

$id_jefatura = $_GET['id'] ?? null;

if ($id_jefatura && is_numeric($id_jefatura)) {
    try {
        $pdo->beginTransaction(); // Iniciamos transacción de seguridad

        // 1. Limpiamos primero las asignaciones históricas en cascada manual para evitar errores de clave foránea
        $stmt_hist = $pdo->prepare("DELETE FROM historial_jefaturas WHERE id_jefatura = ?");
        $stmt_hist->execute([$id_jefatura]);

        // 2. Ahora sí, eliminamos de forma segura la jefatura base
        $stmt = $pdo->prepare("DELETE FROM jefaturas WHERE id_jefatura = ?");
        $stmt->execute([$id_jefatura]);
        
        $pdo->commit(); // Confirmamos los cambios si todo salió bien
        header("Location: index.php?ok=1");
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack(); // Si falla por tablas externas (como cargos o empleados asociados), revertimos todo
        
        // Mensaje amigable e informativo para el usuario
        die("No se puede eliminar esta jefatura porque tiene cargos operativos o registros históricos de personal asociados de manera profunda. Pruebe cambiándole el estado a 'INACTIVO' desde el módulo de edición.");
    }
} else {
    die("ID inválido.");
}