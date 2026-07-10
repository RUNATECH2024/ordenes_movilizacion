<?php
// permisos/revisar.php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . '/../includes/conexion.php'; 

$mensaje = "";

if (isset($_GET['success'])) {
    $mensaje = "<div class='alert success'>¡Firma procesada y registrada en el kárdex correctamente!</div>";
}

// Cargar todos los permisos pendientes o procesados para la visualización del panel
try {
    $sql_permisos = "SELECT p.*, 
                            e.primer_apellido || ' ' || e.primer_nombre AS empleado_nombre, e.cedula,
                            cp.nombre AS clase_nombre, 
                            cc.nombre AS condicion_nombre
                     FROM permisos_ocasionales p
                     JOIN empleados e ON p.id_empleado = e.id_empleado
                     JOIN clases_permiso cp ON p.id_clase_permiso = cp.id_clase_permiso
                     JOIN condiciones_concesion cc ON p.id_condicion = cc.id_condicion
                     ORDER BY p.id_permiso DESC";
    $permisos = $pdo->query($sql_permisos)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $mensaje = "<div class='alert error'>Error al consultar permisos: " . $e->getMessage() . "</div>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGA - Bandeja de Firmas de Asistencia</title>
    <link rel="stylesheet" href="../assets/estilos.css?v=<?= time(); ?>">
    <style>
        .permiso-card { border: 1px solid #cdd7e1; border-radius: 8px; padding: 20px; margin-bottom: 25px; background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .badge-pendiente { background: #fef3c7; color: #d97706; }
        .badge-aprobado { background: #dcfce7; color: #15803d; }
        .badge-rechazado { background: #fee2e2; color: #b91c1c; }
        .firmas-row { display: flex; justify-content: space-between; gap: 15px; margin-top: 20px; }
        .firma-box { flex: 1; border: 1px dashed #adb5bd; border-radius: 6px; padding: 12px; text-align: center; background: #f8f9fa; }
    </style>
</head>
<body>

<div class="container">
    <div class="main-header">
        <h2>Bandeja Unificada de Control y Legalización</h2>
        <a href="crear.php" class="btn btn-primary">+ Nueva Papeleta</a>
        <!-- Se removió el botón global de aquí que causaba el error -->
    </div>

    <?php echo $mensaje; ?>

    <?php if (empty($permisos)): ?>
        <p>No existen registros de permisos guardados en el sistema.</p>
    <?php else: ?>
        <?php foreach($permisos as $reg): ?>
            <div class="permiso-card">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 10px;">
                    <strong>📄 Permiso Nº: <span style="color:#b91c1c;"><?= htmlspecialchars($reg['numero_permiso'] ?? '') ?></span></strong>
                    <div>
                        <span>Estado General: </span>
                        <span class="badge badge-<?= strtolower($reg['estado_legalizacion'] ?? 'pendiente') ?>"><?= htmlspecialchars($reg['estado_legalizacion'] ?? 'PENDIENTE') ?></span>
                    </div>
                </div>

                <div class="grid-2" style="font-size: 14px;">
                    <p><strong>Funcionario:</strong> <?= htmlspecialchars($reg['empleado_nombre'] ?? '') ?> (<?= htmlspecialchars($reg['cedula'] ?? '') ?>)</p>
                    <p><strong>Clase de Permiso:</strong> <?= htmlspecialchars($reg['clase_nombre'] ?? '') ?></p>
                    <p><strong>Fecha Ausencia:</strong> <?= htmlspecialchars($reg['fecha_permiso'] ?? '') ?> | <strong>Tiempo:</strong> <?= htmlspecialchars($reg['total_dias'] ?? '0') ?> Día(s) / <?= htmlspecialchars($reg['total_horas'] ?? '0') ?> Horas</p>
                    <p><strong>Condición (Jefe):</strong> <span style="color:#0056b3; font-weight:bold;"><?= htmlspecialchars($reg['condicion_nombre'] ?? '') ?></span></p>
                </div>
                
                <?php if(!empty($reg['observaciones'])): ?>
                    <p style="background: #fffbeb; padding: 8px; border-left:3px solid #f59e0b; font-size:13px; margin-top:10px;"><strong>Obs:</strong> <?= htmlspecialchars($reg['observaciones']) ?></p>
                <?php endif; ?>

                <div class="firmas-row">
                    <div class="firma-box">
                        <span style="font-size: 11px; color: #6c757d; display:block;">1. SOLICITANTE</span>
                        <strong style="color: green; font-size: 13px;">✍️ FIRMADO DIGITAL</strong>
                        <p style="font-size: 10px; color:gray; margin:0;"><?= htmlspecialchars($reg['fecha_registro'] ?? '') ?></p>
                        <span style="font-size: 12px; font-weight:bold; border-top: 1px solid #ccc; display:block; margin-top:5px; padding-top:2px;">Firma del Empleado</span>
                    </div>

                    <div class="firma-box">
                        <span style="font-size: 11px; color: #6c757d; display:block;">2. JEFE INMEDIATO</span>
                        <span class="badge badge-<?= strtolower($reg['firma_jefe_estado'] ?? 'pendiente') ?>"><?= htmlspecialchars($reg['firma_jefe_estado'] ?? 'PENDIENTE') ?></span>
                        <span style="font-size: 12px; font-weight:bold; border-top: 1px solid #ccc; display:block; margin-top:5px; padding-top:2px;">Jefe de Departamento</span>
                    </div>

                    <div class="firma-box">
                        <span style="font-size: 11px; color: #6c757d; display:block;">3. LEGALIZADO</span>
                        <span class="badge badge-<?= strtolower($reg['firma_director_estado'] ?? 'pendiente') ?>"><?= htmlspecialchars($reg['firma_director_estado'] ?? 'PENDIENTE') ?></span>
                        <span style="font-size: 12px; font-weight:bold; border-top: 1px solid #ccc; display:block; margin-top:5px; padding-top:2px;">Oficina de Personal</span>
                    </div>
                </div>

                <div style="margin-top: 15px; display: flex; justify-content: flex-end; gap: 10px;">
                    <!-- 🛠️ EL BOTÓN CORREGIDO AHORA ESTÁ AQUÍ ADENTRO Y USA LA VARIABLE DIRECTA $reg -->
                    <a href="imprimir_permiso.php?id=<?= $reg['id_permiso'] ?>" class="btn btn-primary" style="padding: 6px 12px; font-size:13px; background:#475569; text-decoration:none; color:white; border-radius:4px; display:inline-flex; align-items:center;" target="_blank">
                        🖨️ Imprimir
                    </a>

                    <form action="firmar.php" method="POST" style="display:inline;">
                        <input type="hidden" name="id_permiso" value="<?= $reg['id_permiso'] ?>">
                        <input type="hidden" name="accion" value="APROBAR">
                        <button type="submit" class="btn btn-primary" style="padding: 6px 12px; font-size:13px; background:#16a34a; border:none; border-radius:4px; color:white; cursor:pointer;">✍️ Estampar Mi Firma (Aprobar)</button>
                    </form>
                    
                    <form action="firmar.php" method="POST" style="display:inline;">
                        <input type="hidden" name="id_permiso" value="<?= $reg['id_permiso'] ?>">
                        <input type="hidden" name="accion" value="RECHAZAR">
                        <button type="submit" class="btn btn-secondary" style="padding: 6px 12px; font-size:13px; background:#dc2626; border:none; border-radius:4px; color:white; cursor:pointer;">❌ Rechazar</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>