<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../includes/conexion.php';

try {
    // Consulta optimizada para PostgreSQL / MySQL
    $stmt = $pdo->query("
        SELECT
            c.id_chofer,
            c.foto,
            c.nombres,
            c.apellidos,
            c.cedula,
            c.telefono,
            c.tipo_licencia,
            c.numero_licencia,
            c.cargo,
            c.fecha_caducidad_licencia,
            c.estado,
            c.id_direccion, -- Traemos el ID puro para verificar si existe o es NULL
            d.nombre AS direccion_nombre,
            d.codigo AS direccion_codigo,
            d.descripcion AS direccion_descripcion
        FROM choferes c
        LEFT JOIN direcciones d
            ON c.id_direccion = d.id_direccion
        ORDER BY c.id_chofer DESC
    ");
    $choferes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener choferes: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de Choferes</title>
    <link rel="stylesheet" href="../assets/estilos.css">
</head>
<body>
    <div class="container">
        <h2>🧑‍✈️ Listado de Choferes</h2>
        <div class="menu">
            <div>
                <a href="../panel_administracion.php" class="btn btn-primary">← Panel</a>
                <a href="nuevo.php" class="btn btn-success">➕ Nuevo Chofer</a>
                <a href="../reportes/choferes_pdf.php" target="_blank" class="btn btn-info">📄 Reporte PDF</a>
            </div>
            <div>
                <a href="../auth/logout.php" class="btn btn-danger">🚪 Cerrar sesión</a>
            </div>
        </div>

        <hr>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Foto</th>
                        <th>Nombre Completo</th>
                        <th>Cédula</th>
                        <th>Teléfono</th>
                        <th>Tipo Licencia</th>
                        <th>N° Licencia</th>
                        <th>Cargo</th>
                        <th>Dirección</th>
                        <th>Caducidad</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($choferes) > 0): ?>
                        <?php foreach ($choferes as $c): ?>
                            <tr>
                                <td><?= $c['id_chofer'] ?></td>
                                <td>
                                    <?php if (!empty($c['foto']) && file_exists("../uploads/choferes/" . $c['foto'])): ?>
                                        <img src="../uploads/choferes/<?= htmlspecialchars($c['foto']) ?>" width="70" height="70" style="border-radius:8px; object-fit:cover;">
                                    <?php else: ?>
                                        <img src="../assets/img/sin_foto.png" width="70" height="70" style="border-radius:8px; object-fit:cover;">
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars(($c['nombres'] ?? '') . ' ' . ($c['apellidos'] ?? '')) ?></td>
                                <td><?= htmlspecialchars($c['cedula'] ?? '') ?></td>
                                <td><?= htmlspecialchars($c['telefono'] ?? '') ?></td>
                                <td><?= htmlspecialchars($c['tipo_licencia'] ?? '') ?></td>
                                <td><?= htmlspecialchars($c['numero_licencia'] ?? '') ?></td>
                                <td><?= htmlspecialchars($c['cargo'] ?? '') ?></td>
                                <td><?= htmlspecialchars($c['direccion_nombre'] ?? 'No asignada') ?></td>
                                
                                <td>
                                    <?php 
                                    if (!empty($c['fecha_caducidad_licencia'])) {
                                        echo date("d/m/Y", strtotime($c['fecha_caducidad_licencia']));
                                    } else {
                                        echo "-";
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if (($c['estado'] ?? '') == "ACTIVO"): ?>
                                        <span class='estado-activo'>ACTIVO</span>
                                    <?php else: ?>
                                        <span class='estado-inactivo'><?= htmlspecialchars($c['estado'] ?? 'INACTIVO') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a class="btn btn-warning" href="editar.php?id=<?= $c['id_chofer'] ?>">✏️</a>
                                    <a class="btn btn-danger" href="eliminar.php?id=<?= $c['id_chofer'] ?>" onclick="return confirm('¿Desea eliminar este chofer?')">🗑️</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="15" style="text-align: center;">No existen registros.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>