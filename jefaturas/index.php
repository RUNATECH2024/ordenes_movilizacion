<?php
// jefaturas/index.php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../includes/conexion.php';

try {
    // CORRECCIÓN RELACIONAL: Buscamos al jefe activo desde la tabla historial_jefaturas
    $sql = "SELECT 
                j.id_jefatura,
                j.codigo,
                j.nombre AS jefatura_nombre,
                d.nombre AS direccion_nombre,
                CONCAT(e.primer_apellido, ' ', e.primer_nombre) AS jefe_nombre,
                j.estado
            FROM jefaturas j
            INNER JOIN direcciones d ON j.id_direccion = d.id_direccion
            LEFT JOIN historial_jefaturas hj ON j.id_jefatura = hj.id_jefatura AND hj.estado = 'ACTIVO'
            LEFT JOIN empleados e ON hj.id_empleado_jefe = e.id_empleado
            ORDER BY j.nombre ASC";

    $stmt = $pdo->query($sql);
    $jefaturas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al cargar jefaturas: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SGA - Lista de Jefaturas</title>
    <link rel="stylesheet" href="../assets/estilos.css">
</head>
<body>
<div class="container">
    <h2>📋 Jefaturas Departamentales</h2>
    
    <div style="margin-bottom: 20px;">
        <a href="../panel_administracion.php" class="btn btn-primary">← Volver</a>
        <a href="nuevo.php" class="btn btn-success">➕ Nueva Jefatura</a>
    </div>

    <?php if (isset($_GET['ok'])): ?>
        <div style="background-color: #c6f6d5; color: #22543d; padding: 12px; margin-bottom: 20px; border-radius: 4px;">
            🚀 Operación procesada correctamente.
        </div>
    <?php endif; ?>

    <table class="table" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background-color: #f7fafc; border-bottom: 2px solid #e2e8f0;">
                <th style="padding: 12px; text-align: left;">Código</th>
                <th style="padding: 12px; text-align: left;">Jefatura</th>
                <th style="padding: 12px; text-align: left;">Dirección Macro</th>
                <th style="padding: 12px; text-align: left;">Jefe Responsable</th>
                <th style="padding: 12px; text-align: center;">Estado</th>
                <th style="padding: 12px; text-align: center;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($jefaturas as $jef): ?>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 10px;"><?= htmlspecialchars($jef['codigo']) ?></td>
                    <td style="padding: 10px; font-weight: bold;"><?= htmlspecialchars($jef['jefatura_nombre']) ?></td>
                    <td style="padding: 10px; color: #4a5568;"><?= htmlspecialchars($jef['direccion_nombre']) ?></td>
                    <td style="padding: 10px;">
                        <?php if (!empty($jef['jefe_nombre'])): ?>
                            👤 <?= htmlspecialchars($jef['jefe_nombre']) ?>
                        <?php else: ?>
                            <span style="background-color: #feebc8; color: #c05621; padding: 2px 6px; border-radius: 4px; font-size: 12px; font-weight: bold;">⚠️ VACANTE</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 10px; text-align: center;">
                        <span class="<?= $jef['estado'] === 'ACTIVO' ? 'badge-activo' : 'badge-inactivo' ?>"><?= $jef['estado'] ?></span>
                    </td>
                    <td style="padding: 10px; text-align: center;">
                        <a href="editar.php?id=<?= $jef['id_jefatura'] ?>" class="btn btn-warning" style="padding: 4px 8px; font-size: 12px; text-decoration: none;">✏️ Editar</a>
                        <a href="eliminar.php?id=<?= $jef['id_jefatura'] ?>" class="btn btn-danger" style="padding: 4px 8px; font-size: 12px; text-decoration: none;" onclick="return confirm('¿Eliminar esta jefatura?');">❌ Borrar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>