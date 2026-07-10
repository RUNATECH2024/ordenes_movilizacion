<?php
// directores/index.php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../includes/conexion.php';

try {
    // Usamos LEFT JOIN para empleados, de este modo si queda vacante o pasa a inactivo, no se oculta la dirección de la lista
    $sql = "SELECT 
                dir.id_director,
                d.nombre AS direccion_nombre,
                d.codigo AS direccion_codigo,
                e.cedula,
                CONCAT(e.primer_apellido, ' ', COALESCE(e.segundo_apellido, ''), ' ', e.primer_nombre) AS director_nombre,
                e.foto,
                dir.estado
            FROM directores dir
            INNER JOIN direcciones d ON dir.id_direccion = d.id_direccion
            LEFT JOIN empleados e ON dir.id_empleado = e.id_empleado
            ORDER BY dir.estado ASC, e.primer_apellido ASC, e.primer_nombre ASC";

    $stmt = $pdo->query($sql);
    $directores = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error al cargar la lista de directores: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SGA - Listado de Directores</title>
    <link rel="stylesheet" href="../assets/estilos.css">
    <style>
        .avatar-lista { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid #cbd5e0; }
        .badge-activo { background-color: #48bb78; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .badge-inactivo { background-color: #e53e3e; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .table-responsive { margin-top: 20px; overflow-x: auto; }
    </style>
</head>
<body>

<div class="container">
    <h2>📊 Personal Directivo Asignado</h2>
    
    <div class="menu" style="margin-bottom: 20px; display: flex; gap: 10px;">
        <a href="../panel_administracion.php" class="btn btn-primary">← Panel Principal</a>
        <a href="nuevo.php" class="btn btn-success">➕ Designar Nuevo Director</a>
    </div>

    <?php if (isset($_GET['ok'])): ?>
        <div style="background-color: #c6f6d5; border-left: 5px solid #38a169; color: #22543d; padding: 12px; margin-bottom: 20px; border-radius: 4px; font-weight: 500;">
            🚀 Operación procesada y guardada correctamente en el sistema.
        </div>
    <?php endif; ?>

    <hr>

    <div class="table-responsive">
        <table class="table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background-color: #f7fafc; border-bottom: 2px solid #e2e8f0;">
                    <th style="width: 70px; text-align: center; padding: 12px;">Foto</th>
                    <th style="padding: 12px; text-align: left;">Director</th>
                    <th style="padding: 12px; text-align: left;">Cédula</th>
                    <th style="padding: 12px; text-align: left;">Dirección / Departamento a Cargo</th>
                    <th style="padding: 12px; text-align: center;">Estado</th>
                    <th style="padding: 12px; text-align: center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($directores)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 30px; color: #718096;">
                            No hay asignaciones de direcciones en el sistema.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($directores as $dir): ?>
                        <tr style="border-bottom: 1px solid #e2e8f0; <?= $dir['estado'] !== 'ACTIVO' ? 'background-color: #fcfcfc; opacity: 0.8;' : '' ?>">
                            <td style="text-align: center; padding: 10px; vertical-align: middle;">
                                <?php if (!empty($dir['foto']) && file_exists('../uploads/' . $dir['foto'])): ?>
                                    <img src="../uploads/<?= htmlspecialchars($dir['foto']) ?>" class="avatar-lista" alt="Foto">
                                <?php else: ?>
                                    <img src="../assets/img/default-avatar.png" class="avatar-lista" alt="Sin foto" onerror="this.src='https://via.placeholder.com/150'">
                                <?php endif; ?>
                            </td>
                            <td style="padding: 10px; vertical-align: middle; font-weight: bold; color: #2d3748;">
                                <?php if (!empty(trim($dir['director_nombre']))): ?>
                                    <?= htmlspecialchars($dir['director_nombre']) ?>
                                <?php else: ?>
                                    <span style="background-color: #feebc8; color: #c05621; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold;">⚠️ VACANTE</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 10px; vertical-align: middle; color: #4a5568;">
                                <?= htmlspecialchars($dir['cedula'] ?? 'N/A') ?>
                            </td>
                            <td style="padding: 10px; vertical-align: middle;">
                                <span style="color: #2b6cb0; font-weight: 600;"><?= htmlspecialchars($dir['direccion_nombre']) ?></span><br>
                                <small style="color: #a0aec0; font-weight: bold;">Módulo macro: <?= htmlspecialchars($dir['direccion_codigo']) ?></small>
                            </td>
                            <td style="padding: 10px; text-align: center; vertical-align: middle;">
                                <span class="<?= $dir['estado'] === 'ACTIVO' ? 'badge-activo' : 'badge-inactivo' ?>"><?= $dir['estado'] ?></span>
                            </td>
                            <td style="padding: 10px; text-align: center; vertical-align: middle;">
                                <a href="editar.php?id=<?= $dir['id_director'] ?>" class="btn btn-warning" style="padding: 6px 12px; font-size: 12px; text-decoration: none; border-radius: 4px; margin-right: 5px;">✏️ Editar</a>
                                <a href="eliminar.php?id=<?= $dir['id_director'] ?>" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px; text-decoration: none; border-radius: 4px;" onclick="return confirm('¿Está seguro de remover este cargo directivo de la lista?');">❌ Remover</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>