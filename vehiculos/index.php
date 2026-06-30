<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . '/../includes/conexion.php';

if (!isset($pdo)) {
    die("Error de conexión");
}

try {
    // Agregamos el LEFT JOIN para traer el nombre y código de la dirección institucional
    $sql = "
    SELECT
        v.*,
        c.nombres,
        c.apellidos,
        d.nombre AS direccion_nombre,
        d.codigo AS direccion_codigo
    FROM vehiculos v
    LEFT JOIN choferes c ON v.id_chofer = c.id_chofer
    LEFT JOIN direcciones d ON v.id_direccion = d.id_direccion
    ORDER BY v.id_vehiculo DESC
    ";

    $stmt = $pdo->query($sql);
    $vehiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error al obtener vehículos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Vehículos</title>
    <link rel="stylesheet" href="../assets/estilos.css">
</head>
<body>
    <div class="container">
        <h2>🚗 Lista de Vehículos</h2>
        <div class="menu">
            <div>
                <a href="../panel_administracion.php" class="btn btn-primary">← Panel</a>
                <a href="nuevo.php" class="btn btn-success">➕ Nuevo Vehículo</a>
            </div>
            <div>
                <a href="../auth/logout.php" class="btn btn-danger">🚪 Cerrar sesión</a>
            </div>
        </div>

        <hr>

        <div class="table-container">
            <?php if (!empty($vehiculos)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Código</th>
                            <th>Unidad</th>
                            <th>Placa</th>
                            <th>Matrícula</th>
                            <th>Marca / Modelo</th>
                            <th>Tipo / Color</th>
                            <th>Año</th>
                            <th>Chasis / Motor</th>
                            <th>Dirección Institucional</th>
                            <th>Chofer Asignado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vehiculos as $v): ?>
                            <tr>
                                <td data-label="Foto">
                                    <?php 
                                    // Controlamos la ruta de la foto basándonos en tu estructura de uploads
                                    if(!empty($v['foto_vehiculo']) && file_exists("../uploads/vehiculos/" . $v['foto_vehiculo'])): 
                                    ?>
                                        <img src="../uploads/vehiculos/<?= htmlspecialchars($v['foto_vehiculo']) ?>" width="80" height="60" style="border-radius:6px; object-fit:cover;">
                                    <?php elseif(!empty($v['foto_vehiculo'])): ?>
                                        <img src="../<?= htmlspecialchars($v['foto_vehiculo']) ?>" width="80" height="60" style="border-radius:6px; object-fit:cover;">
                                    <?php else: ?>
                                        <span style="color: #666; font-style: italic; font-size: 11px;">Sin foto</span>
                                    <?php endif; ?>
                                </td>

                                <td data-label="Código">
                                    <strong><?= htmlspecialchars($v['codigo_institucional'] ?? '-') ?></strong>
                                </td>

                                <td data-label="Unidad">
                                    <?= htmlspecialchars($v['unidad'] ?? '-') ?>
                                </td>

                                <td data-label="Placa">
                                    <?= htmlspecialchars($v['placa'] ?? '-') ?>
                                </td>

                                <td data-label="Matrícula">
                                    <?= htmlspecialchars($v['matricula'] ?? '-') ?>
                                </td>

                                <td data-label="Marca / Modelo">
                                    <?= htmlspecialchars(($v['marca'] ?? '') . ' / ' . ($v['modelo'] ?? '')) ?>
                                </td>

                                <td data-label="Tipo / Color">
                                    <?= htmlspecialchars(($v['tipo'] ?? '') . ' / ' . ($v['color'] ?? '')) ?>
                                </td>

                                <td data-label="Año">
                                    <?= htmlspecialchars($v['anio'] ?? '-') ?>
                                </td>

                                <td data-label="Chasis / Motor">
                                    <span style="font-size: 11px; display: block;">C: <?= htmlspecialchars($v['chasis'] ?? '-') ?></span>
                                    <span style="font-size: 11px; display: block;">M: <?= htmlspecialchars($v['motor'] ?? '-') ?></span>
                                </td>

                                <td data-label="Dirección Institucional">
                                    <?php if(!empty($v['direccion_nombre'])): ?>
                                        <span><?= htmlspecialchars($v['direccion_nombre']) ?></span>
                                        <small style="display:block; color:#718096;">(<?= htmlspecialchars($v['direccion_codigo']) ?>)</small>
                                    <?php else: ?>
                                        <span style="color: #a0aec0; font-style: italic;">Sin dirección</span>
                                    <?php endif; ?>
                                </td>

                                <td data-label="Chofer">
                                    <?php if(!empty($v['nombres'])): ?>
                                        <?= htmlspecialchars($v['nombres'] . " " . $v['apellidos']) ?>
                                    <?php else: ?>
                                        <span class='estado-inactivo' style="color:red; font-weight:bold;">Sin asignar</span>
                                    <?php endif; ?>
                                </td>

                                <td data-label="Acciones">
                                    <div class="acciones">
                                        <a href="editar.php?id=<?= $v['id_vehiculo'] ?>" class="btn btn-warning" style="padding: 4px 8px; font-size: 12px; text-decoration: none; border-radius: 4px;">✏️ Editar</a>
                                        <a href="eliminar.php?id=<?= $v['id_vehiculo'] ?>" class="btn btn-danger" onclick="return confirm('¿Eliminar este vehículo?')" style="padding: 4px 8px; font-size: 12px; text-decoration: none; border-radius: 4px;">❌ Eliminar</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No hay vehículos registrados.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>