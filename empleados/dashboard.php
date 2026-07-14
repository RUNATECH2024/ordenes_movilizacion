<?php
// empleados/dashboard.php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . '/../includes/conexion.php';

try {
    // 1. CONTEO GENERAL (Optimizado para evitar duplicidad de registros y calcular LOSEP/Código de Trabajo)
    $sql_conteos = "SELECT 
                        -- Total de empleados físicos únicos en la base de datos
                        COUNT(DISTINCT e.id_empleado) AS total,
                        
                        -- Empleados únicos que tienen actualmente un contrato activo (true)
                        COUNT(DISTINCT CASE WHEN hl.activo = true THEN e.id_empleado END) AS activos,
                        
                        -- Empleados inactivos reales (Total de empleados menos los que están activos)
                        (COUNT(DISTINCT e.id_empleado) - COUNT(DISTINCT CASE WHEN hl.activo = true THEN e.id_empleado END)) AS inactivos,
                        
                        -- Conteo de hombres únicos
                        COUNT(DISTINCT CASE WHEN e.id_genero = 1 OR UPPER(e.id_genero::text) LIKE '%MASCULINO%' OR UPPER(e.id_genero::text) = 'H' THEN e.id_empleado END) AS hombres,
                        
                        -- Conteo de mujeres únicas
                        COUNT(DISTINCT CASE WHEN e.id_genero = 2 OR UPPER(e.id_genero::text) LIKE '%FEMENINO%' OR UPPER(e.id_genero::text) = 'M' THEN e.id_empleado END) AS mujeres,
                        
                        -- Conteo de discapacidad único
                        (SELECT COUNT(DISTINCT id_empleado) FROM empleado_discapacidad) AS con_discapacidad,
                        
                        -- LOSEP activos únicos
                        COUNT(DISTINCT CASE WHEN hl.id_tipo_nombramiento = 3 AND hl.activo = true THEN e.id_empleado END) AS losep,
                        
                        -- Código de Trabajo activos únicos
                        COUNT(DISTINCT CASE WHEN hl.id_tipo_nombramiento = 1 AND hl.activo = true THEN e.id_empleado END) AS codigo_trabajo
                    FROM empleados e
                    LEFT JOIN historial_laboral hl ON e.id_empleado = hl.id_empleado";
    $conteos = $pdo->query($sql_conteos)->fetch(PDO::FETCH_ASSOC);

    // 2. OBTENER LAS DIRECCIONES Y EL CONTEO REAL DE EMPLEADOS ASOCIADOS
    $sql_direcciones = "SELECT 
                            d.id_direccion,
                            COALESCE(d.nombre, 'SIN DIRECCIÓN ASIGNADA') AS direccion_nombre,
                            COUNT(hl.id_empleado) AS total_empleados
                        FROM direcciones d
                        LEFT JOIN jefaturas j ON d.id_direccion = j.id_direccion
                        LEFT JOIN cargos c ON j.id_jefatura = c.id_jefatura
                        LEFT JOIN historial_laboral hl ON c.id_cargo = hl.id_cargo AND hl.activo = TRUE
                        WHERE d.estado = 'ACTIVO' OR d.id_direccion IS NULL
                        GROUP BY d.id_direccion, d.nombre
                        ORDER BY d.nombre ASC";
    
    $direcciones_resumen = $pdo->query($sql_direcciones)->fetchAll(PDO::FETCH_ASSOC);

    // 3. OBTENER EL DETALLE COMPLETO DE EMPLEADOS POR DIRECCIÓN
    $sql_detalles = "SELECT 
                        e.id_empleado,
                        e.cedula,
                        e.foto,
                        CONCAT(e.primer_apellido, ' ', COALESCE(e.segundo_apellido, ''), ' ', e.primer_nombre, ' ', COALESCE(e.segundo_nombre, '')) AS nombre_completo,
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

<div class="container dashboard-wrapper">
    
    <div class="dashboard-header-flex">
        <div>
            <h2 class="dashboard-main-title">Panel de Control General (Dashboard)</h2>
            <p class="dashboard-subtitle">Resumen estructural en tiempo real del talento humano asignado</p>
            <a href="../panel_administracion.php" class="btn btn-primary">← Panel</a>
        </div>
        <a href="index.php" class="btn btn-primary btn-dashboard-manage">⚙️ Gestionar Empleados</a>
    </div>

    <!-- Grid de tarjetas de conteo -->
    <div class="pro-dashboard-grid">
        <div class="pro-card total">
            <div class="pro-card-info">
                <h3>Total Empleados</h3>
                <div class="numero"><?= $conteos['total'] ?? 0 ?></div>
            </div>
            <div class="pro-card-icon icon-total">👥</div>
        </div>
        
        <div class="pro-card activos">
            <div class="pro-card-info">
                <h3>Personal Activo</h3>
                <div class="numero"><?= $conteos['activos'] ?? 0 ?></div>
            </div>
            <div class="pro-card-icon icon-activos">✅</div>
        </div>
        
        <div class="pro-card inactivos">
            <div class="pro-card-info">
                <h3>Personal Inactivo</h3>
                <div class="numero"><?= $conteos['inactivos'] ?? 0 ?></div>
            </div>
            <div class="pro-card-icon icon-inactivos">❌</div>
        </div>

        <div class="pro-card losep" style="border-left: 5px solid #3b82f6;">
            <div class="pro-card-info">
                <h3>Personal LOSEP</h3>
                <div class="numero"><?= $conteos['losep'] ?? 0 ?></div>
            </div>
            <div class="pro-card-icon icon-losep">📄</div>
        </div>

        <div class="pro-card codigo-trabajo" style="border-left: 5px solid #10b981;">
            <div class="pro-card-info">
                <h3>Código de Trabajo</h3>
                <div class="numero"><?= $conteos['codigo_trabajo'] ?? 0 ?></div>
            </div>
            <div class="pro-card-icon icon-codigo">🛠️</div>
        </div>

        <div class="pro-card hombres">
            <div class="pro-card-info">
                <h3>Hombres</h3>
                <div class="numero"><?= $conteos['hombres'] ?? 0 ?></div>
            </div>
            <div class="pro-card-icon icon-hombres">👨</div>
        </div>

        <div class="pro-card mujeres">
            <div class="pro-card-info">
                <h3>Mujeres</h3>
                <div class="numero"><?= $conteos['mujeres'] ?? 0 ?></div>
            </div>
            <div class="pro-card-icon icon-mujeres">👩</div>
        </div>

        <div class="pro-card discapacidad">
            <div class="pro-card-info">
                <h3>Con Discapacidad</h3>
                <div class="numero"><?= $conteos['con_discapacidad'] ?? 0 ?></div>
            </div>
            <div class="pro-card-icon icon-discapacidad">♿</div>
        </div>
    </div>

    <!-- Buscador interactivo -->
    <div class="search-box-container">
        <input type="text" id="dashboardSearch" class="search-box" onkeyup="filtrarDirecciones()" placeholder="🔍 Buscar dirección o departamento macro por nombre...">
    </div>

    <div class="section-title-area-dashboard">
        <h3 class="section-title-text">Estructura de Personal por Direcciones</h3>
        <p class="section-help-text">💡 Haz clic sobre cualquier fila estructural para inspeccionar o contraer su nómina de funcionarios.</p>
    </div>

    <!-- Contenedor del Acordeón de Direcciones -->
    <div id="direccionesContainer">
        <?php foreach ($direcciones_resumen as $dir): 
            $id_dir = $dir['id_direccion'];
            $personal_area = $empleados_por_direccion[$id_dir] ?? [];
        ?>
            <div class="dir-row" data-nombre="<?= strtolower(htmlspecialchars($dir['direccion_nombre'])) ?>">
                <div class="dir-trigger" onclick="toggleDireccion('dir_<?= $id_dir ?>')">
                    <div class="dir-title">
                        <span class="icon">🏢</span> 
                        <span><?= htmlspecialchars($dir['direccion_nombre']) ?></span>
                    </div>
                    <span class="badge-count">
                        <?= $dir['total_empleados'] ?> <?= $dir['total_empleados'] == 1 ? 'Empleado' : 'Empleados' ?>
                    </span>
                </div>

                <div id="dir_<?= $id_dir ?>" class="dir-contenido" style="display: none;">
                    <?php if (count($personal_area) > 0): ?>
                        <div class="dashboard-table-responsive">
                            <table class="dashboard-table">
                                <thead>
                                    <tr>
                                        <th class="th-photo">Foto</th>
                                        <th>Cédula</th>
                                        <th>Funcionario</th>
                                        <th>Cargo Desempeñado</th>
                                        <th>Estado</th>
                                        <th class="th-action">Ficha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($personal_area as $p): ?>
                                        <tr>
                                            <td class="td-photo">
                                                <?php if (!empty($p['foto']) && file_exists('../uploads/' . $p['foto'])): ?>
                                                    <img src="../uploads/<?= htmlspecialchars($p['foto']) ?>" class="avatar-table" alt="Foto">
                                                <?php else: ?>
                                                    <img src="../assets/img/default-avatar.png" class="avatar-table" alt="Sin foto" onerror="this.src='https://via.placeholder.com/150'">
                                                <?php endif; ?>
                                            </td>
                                            <td class="td-cedula"><?= htmlspecialchars($p['cedula']) ?></td>
                                            <td><strong><?= htmlspecialchars($p['nombre_completo']) ?></strong></td>
                                            <td class="td-cargo"><?= htmlspecialchars($p['cargo_actual']) ?></td>
                                            <td>
                                                <?php $badge_clase = strtoupper($p['estado']) == 'ACTIVO' ? 'badge-success' : 'badge-danger'; ?>
                                                <span class="badge-status <?= $badge_clase ?>"><?= htmlspecialchars($p['estado']) ?></span>
                                            </td>
                                            <td class="td-action">
                                                <a class="btn-view-pill" href="ver.php?id=<?= $p['id_empleado'] ?>">👁️ Ver</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="no-data-text">⚠️ No hay personal operativo o administrativo asignado activamente a esta dirección macro.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
// Manejo fluido de colapsables/acordeón
function toggleDireccion(id) {
    var el = document.getElementById(id);
    if (el.style.display === "block") {
        el.style.display = "none";
    } else {
        el.style.display = "block";
    }
}

// Filtro/Buscador interactivo en tiempo real por Nombre de Dirección
function filtrarDirecciones() {
    var input = document.getElementById('dashboardSearch');
    var filter = input.value.toLowerCase();
    var rows = document.getElementsByClassName('dir-row');

    for (var i = 0; i < rows.length; i++) {
        var nombreDir = rows[i].getAttribute('data-nombre');
        if (nombreDir.includes(filter)) {
            rows[i].style.display = "";
        } else {
            rows[i].style.display = "none";
        }
    }
}
</script>

</body>
</html>