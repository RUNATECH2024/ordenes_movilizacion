<?php
// directores/eliminar.php
session_start();
if (!isset($_SESSION['usuario'])) { 
    header("Location: ../auth/login.php"); 
    exit; 
}
require_once '../includes/conexion.php';

$id_director = $_GET['id'] ?? null;
$error = '';

if ($id_director && is_numeric($id_director)) {
    try {
        // Borramos el registro de asignación
        $stmt = $pdo->prepare("DELETE FROM directores WHERE id_director = ?");
        $stmt->execute([$id_director]);
        
        header("Location: index.php?ok=1");
        exit;
    } catch (PDOException $e) {
        // Captura si está enlazado por llave foránea a otras estructuras restrictivas o firmas históricas
        $error = "No se puede eliminar la asignación de este director porque tiene dependencias históricas activas en el sistema (por ejemplo, actas firmadas o resoluciones vinculadas). Para preservar la integridad del sistema, considere cambiar su estado a <strong>'INACTIVO'</strong> desde el panel de edición.";
    }
} else {
    $error = "El ID del cargo directivo no es válido o está ausente en la petición.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SGA - Error de Eliminación</title>
    <link rel="stylesheet" href="../assets/estilos.css">
</head>
<body>
<div class="container" style="max-width: 600px; margin-top: 50px;">
    <h2>⚠️ Restricción del Sistema</h2>
    <hr>
    
    <div style="background-color: #fed7d7; border-left: 5px solid #e53e3e; color: #9b2c2c; padding: 15px; margin-bottom: 25px; border-radius: 4px; font-weight: 500; line-height: 1.5;">
        <?= $error ?>
    </div>

    <div style="display: flex; gap: 10px;">
        <a href="index.php" class="btn btn-primary" style="flex: 1; text-align: center; padding: 10px; font-weight: bold; text-decoration: none; border-radius: 4px;">📊 Volver al Listado</a>
        <?php if ($id_director && is_numeric($id_director)): ?>
            <a href="editar.php?id=<?= (int)$id_director ?>" class="btn btn-warning" style="flex: 1; text-align: center; padding: 10px; font-weight: bold; text-decoration: none; border-radius: 4px;">✏️ Ir a Editar Estado</a>
        <?php endif; ?>
    </div>
</div>
</body>
</html>