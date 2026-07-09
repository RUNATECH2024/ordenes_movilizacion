<?php
// empleados/dashboard.php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . '/../includes/conexion.php';

try {
    // 1. CONTEO GENERAL DE ESTADOS
    $sql_conteos = "SELECT 
                        COUNT(*) AS total,
                        COUNT(CASE WHEN UPPER(estado) = 'ACTIVO' THEN 1 END) AS activos,
                        COUNT(CASE WHEN UPPER(estado) = 'INACTIVO' THEN 1 END) AS inactivos
                    FROM empleados";
    $conteos = $pdo->query($sql_conteos)->fetch(PDO::FETCH_ASSOC);

    // 2. OBTENER LAS DIRECCIONES Y LOS EMPLEADOS ASOCIADOS
    $sql_direcciones = "SELECT 
                            d.id_direccion,
                            COALESCE(d.nombre, 'SIN DIRECCIÓN ASIGNADA') AS direccion_nombre,
                            COUNT(e.id_empleado) AS total_empleados
                        FROM direcciones d
                        LEFT JOIN jefaturas j ON d.id_direccion = j.id_direccion
                        LEFT JOIN cargos c ON j.id_jefatura = c.id_jefatura
                        LEFT JOIN historial_laboral hl ON c.id_cargo = hl.id_cargo AND hl.activo = TRUE
                        LEFT JOIN empleados e ON hl.id_empleado = e.id_empleado
                        WHERE d.estado = 'ACTIVO' OR d.id_direccion IS NULL
                        GROUP BY d.id_direccion, d.nombre
                        ORDER BY d.nombre ASC";
    
    $direcciones_resumen = $pdo->query($sql_direcciones)->fetchAll(PDO::FETCH_ASSOC);

    // 3. OBTENER EL DETALLE COMPLETO DE EMPLEADOS POR DIRECCIÓN
    $sql_detalles = "SELECT 
                        e.id_empleado,
                        e.cedula,
                        e.foto,
                        CONCAT(e.primer_apellido, ' ', e.segundo_apellido, ' ', e.primer_nombre, ' ', e.segundo_nombre) AS nombre_completo,
                        c.nombre AS cargo_actual,
                        e.estado,
                        d.id_direccion
                    FROM empleados e
                    INNER JOIN historial_laboral hl ON e.id_empleado = hl.id_empleado AND hl.activo = TRUE
                    INNER JOIN cargos c ON hl.id_cargo = c.id_cargo
                    INNER JOIN jefaturas j ON c.id_jefatura = j.id_jefatura
                    INNER JOIN direcciones d ON j.id_direccion = d.id_direccion
                    ORDER BY d.id_direccion, e.primer_apellido, e.primer_nombre";
    
    $stmt_detalles = $pdo->query($sql_detalles);
    
    $empleados_por_direccion = [];
    while ($row = $stmt_detalles->fetch(PDO::FETCH_ASSOC)) {
        $empleados_por_direccion[$row['id_direccion']][] = $row;
    }

} catch (Exception $e) {
    die("Error al cargar el Dashboard: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGA - Dashboard de Personal</title>
    <link rel="stylesheet" href="../assets/estilos.css?v=<?= time(); ?>">
</head>
<body>

<div class="container">
    
    <div class="main-header dashboard-header">
        <div>
            <h2>Panel de Control General (Dashboard)</h2>
            <p class="subtitle">Resumen en tiempo real del talento humano por departamentos</p>
        </div>
        <a href="index.php" class="btn btn-primary">⚙️ Gestionar Empleados</a>
    </div>

    <div class="pro-dashboard-grid">
        <div class="pro-card total">
            <div class="pro-card-info">
                <h3>Total Empleados</h3>
                <div class="numero"><?= $conteos['total'] ?? 0 ?></div>
            </div>
            <div class="pro-card-icon">👥</div>
        </div>
        
        <div class="pro-card activos">
            <div class="pro-card-info">
                <h3>Personal Activo</h3>
                <div class="numero"><?= $conteos['activos'] ?? 0 ?></div>
            </div>
            <div class="pro-card-icon">✅</div>
        </div>
        
        <div class="pro-card inactivos">
            <div class="pro-card-info">
                <h3>Personal Inactivo</h3>
                <div class="numero"><?= $conteos['inactivos'] ?? 0 ?></div>
            </div>
            <div class="pro-card-icon">❌</div>
        </div>
    </div>

    <div class="section-title-area">
        <h3>Estructura de Personal por Direcciones</h3>
        <p class="help-text">💡 Haz clic sobre cualquier dirección de la lista para inspeccionar la nómina de funcionarios asignados.</p>
    </div>

    <?php foreach ($direcciones_resumen as $dir): 
        $id_dir = $dir['id_direccion'];
        $personal_area = $empleados_por_direccion[$id_dir] ?? [];
    ?>
        <div class="dir-row">
            <div class="dir-trigger" onclick="toggleDireccion('dir_<?= $id_dir ?>')">
                <div class="dir-title">
                    <span class="icon">🏢</span> 
                    <span><?= htmlspecialchars($dir['direccion_nombre']) ?></span>
                </div>
                <span class="badge badge-count">
                    <?= $dir['total_empleados'] ?> Empleados
                </span>
            </div>

            <div id="dir_<?= $id_dir ?>" class="dir-contenido">
                <?php if (count($personal_area) > 0): ?>
                    <div class="table-responsive">
                        <table class="table dashboard-table">
                            <thead>
                                <tr>
                                    <th class="text-center">Foto</th>
                                    <th>Cédula</th>
                                    <th>Funcionario</th>
                                    <th>Cargo Desempeñado</th>
                                    <th>Estado</th>
                                    <th class="text-center">Ficha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($personal_area as $p): ?>
                                    <tr>
                                        <td class="text-center cell-middle">
                                            <?php if (!empty($p['foto']) && file_exists('../uploads/' . $p['foto'])): ?>
                                                <img src="../uploads/<?= htmlspecialchars($p['foto']) ?>" class="avatar-table" alt="Foto">
                                            <?php else: ?>
                                                <img src="../assets/img/default-avatar.png" class="avatar-table" alt="Sin foto">
                                            <?php endif; ?>
                                        </td>
                                        <td class="cell-middle"><?= htmlspecialchars($p['cedula']) ?></td>
                                        <td class="cell-middle"><strong><?= htmlspecialchars($p['nombre_completo']) ?></strong></td>
                                        <td class="cell-middle text-muted"><?= htmlspecialchars($p['cargo_actual']) ?></td>
                                        <td class="cell-middle">
                                            <?php $badge_clase = strtoupper($p['estado']) == 'ACTIVO' ? 'badge-success' : 'badge-danger'; ?>
                                            <span class="badge <?= $badge_clase ?>"><?= htmlspecialchars($p['estado']) ?></span>
                                        </td>
                                        <td class="text-center cell-middle">
                                            <a class="btn btn-info btn-sm-view" href="ver.php?id=<?= $p['id_empleado'] ?>" title="Ver Ficha Completa">👁️</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="no-data-text">No hay personal operativo o administrativo asignado activamente a esta dirección.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
function toggleDireccion(id) {
    var el = document.getElementById(id);
    if (el.style.display === "block") {
        el.style.display = "none";
    } else {
        el.style.display = "block";
    }
}
</script>

</body>
</html>