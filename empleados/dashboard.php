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
    <style>
        /* Estilos Modernos y Fluidos para el Dashboard */
        .dashboard-wrapper { font-family: 'Segoe UI', system-ui, sans-serif; color: #2d3748; }
        .dashboard-header-flex { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 25px; }
        
        /* Grilla de Métricas */
        .pro-dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .pro-card { display: flex; justify-content: space-between; align-items: center; padding: 20px; border-radius: 12px; background: #fff; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border-left: 5px solid #cbd5e0; transition: transform 0.2s; }
        .pro-card:hover { transform: translateY(-3px); }
        .pro-card.total { border-left-color: #3182ce; }
        .pro-card.activos { border-left-color: #38a169; }
        .pro-card.inactivos { border-left-color: #e53e3e; }
        .pro-card h3 { font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; color: #718096; margin: 0 0 5px 0; }
        .pro-card .numero { font-size: 28px; font-weight: 700; color: #1a202c; }
        .pro-card-icon { font-size: 32px; opacity: 0.8; }

        /* Barra de búsqueda interactiva */
        .search-box-container { margin-bottom: 20px; position: relative; }
        .search-box { width: 100%; padding: 12px 15px; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 15px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02); outline: none; transition: border-color 0.2s; }
        .search-box:focus { border-color: #3182ce; box-shadow: 0 0 0 3px rgba(49,130,206,0.15); }

        /* Acordeones/Colapsables Estilizados */
        .dir-row { background: #fff; border-radius: 8px; margin-bottom: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; overflow: hidden; }
        .dir-trigger { display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; background: #fff; cursor: pointer; user-select: none; transition: background 0.2s; }
        .dir-trigger:hover { background: #f7fafc; }
        .dir-title { display: flex; align-items: center; gap: 10px; font-weight: 600; font-size: 16px; color: #2d3748; }
        .badge-count { background: #ebf8ff; color: #2b6cb0; padding: 4px 10px; border-radius: 20px; font-size: 13px; font-weight: 600; }
        
        .dir-contenido { display: none; padding: 20px; background: #fcfdfd; border-top: 1px solid #edf2f7; }
        
        /* Tablas internas */
        .dashboard-table { width: 100%; border-collapse: collapse; margin: 0; }
        .dashboard-table th { background: #f7fafc; color: #4a5568; font-weight: 600; padding: 12px; text-align: left; font-size: 13px; border-bottom: 2px solid #edf2f7; }
        .dashboard-table td { padding: 12px; border-bottom: 1px solid #edf2f7; font-size: 14px; vertical-align: middle; }
        .avatar-table { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        
        /* Badges de Estado */
        .badge-status { padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .badge-success { background-color: #c6f6d5; color: #22543d; }
        .badge-danger { background-color: #fed7d7; color: #9b2c2c; }
        
        .btn-view-pill { background: #edf2f7; padding: 6px 12px; border-radius: 6px; text-decoration: none; color: #4a5568; font-size: 13px; font-weight: 500; transition: background 0.2s; }
        .btn-view-pill:hover { background: #e2e8f0; color: #1a202c; }
        .no-data-text { color: #a0aec0; text-align: center; margin: 10px 0; font-style: italic; }
    </style>
</head>
<body>

<div class="container dashboard-wrapper">
    
    <div class="dashboard-header-flex">
        <div>
            <h2 style="margin: 0; font-size: 26px; font-weight: 700; color: #1a202c;">Panel de Control General (Dashboard)</h2>
            <p style="margin: 4px 0 0 0; color: #718096; font-size: 14px;">Resumen estructural en tiempo real del talento humano asignado</p>
        </div>
        <a href="index.php" class="btn btn-primary" style="box-shadow: 0 2px 4px rgba(49,130,206,0.2);">⚙️ Gestionar Empleados</a>
    </div>

    <div class="pro-dashboard-grid">
        <div class="pro-card total">
            <div class="pro-card-info">
                <h3>Total Empleados</h3>
                <div class="numero"><?= $conteos['total'] ?? 0 ?></div>
            </div>
            <div class="pro-card-icon" style="color: #3182ce;">👥</div>
        </div>
        
        <div class="pro-card activos">
            <div class="pro-card-info">
                <h3>Personal Activo</h3>
                <div class="numero"><?= $conteos['activos'] ?? 0 ?></div>
            </div>
            <div class="pro-card-icon" style="color: #38a169;">✅</div>
        </div>
        
        <div class="pro-card inactivos">
            <div class="pro-card-info">
                <h3>Personal Inactivo</h3>
                <div class="numero"><?= $conteos['inactivos'] ?? 0 ?></div>
            </div>
            <div class="pro-card-icon" style="color: #e53e3e;">❌</div>
        </div>
    </div>

    <div class="search-box-container">
        <input type="text" id="dashboardSearch" class="search-box" onkeyup="filtrarDirecciones()" placeholder="🔍 Buscar dirección o departamento macro por nombre...">
    </div>

    <div class="section-title-area" style="margin-bottom: 15px;">
        <h3 style="margin: 0 0 5px 0; font-size: 18px; font-weight: 600;">Estructura de Personal por Direcciones</h3>
        <p style="margin: 0; font-size: 13px; color: #718096;">💡 Haz clic sobre cualquier fila estructural para inspeccionar o contraer su nómina de funcionarios.</p>
    </div>

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

                <div id="dir_<?= $id_dir ?>" class="dir-contenido">
                    <?php if (count($personal_area) > 0): ?>
                        <div style="overflow-x: auto;">
                            <table class="dashboard-table">
                                <thead>
                                    <tr>
                                        <th style="width: 60px; text-align: center;">Foto</th>
                                        <th>Cédula</th>
                                        <th>Funcionario</th>
                                        <th>Cargo Desempeñado</th>
                                        <th>Estado</th>
                                        <th style="width: 80px; text-align: center;">Ficha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($personal_area as $p): ?>
                                        <tr>
                                            <td style="text-align: center;">
                                                <?php if (!empty($p['foto']) && file_exists('../uploads/' . $p['foto'])): ?>
                                                    <img src="../uploads/<?= htmlspecialchars($p['foto']) ?>" class="avatar-table" alt="Foto">
                                                <?php else: ?>
                                                    <img src="../assets/img/default-avatar.png" class="avatar-table" alt="Sin foto" onerror="this.src='https://via.placeholder.com/150'">
                                                <?php endif; ?>
                                            </td>
                                            <td style="font-family: monospace; font-size: 14px; font-weight: 600; color: #4a5568;"><?= htmlspecialchars($p['cedula']) ?></td>
                                            <td><strong><?= htmlspecialchars($p['nombre_completo']) ?></strong></td>
                                            <td style="color: #4a5568; font-weight: 500;"><?= htmlspecialchars($p['cargo_actual']) ?></td>
                                            <td>
                                                <?php $badge_clase = strtoupper($p['estado']) == 'ACTIVO' ? 'badge-success' : 'badge-danger'; ?>
                                                <span class="badge-status <?= $badge_clase ?>"><?= htmlspecialchars($p['estado']) ?></span>
                                            </td>
                                            <td style="text-align: center;">
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

// Filtro/Buscador interactivo en tiempo real
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