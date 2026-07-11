<?php
// permisos/index.php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . '/../includes/conexion.php'; 

$mensaje = "";

try {
    // 1. CONSULTA DEL KÁRDEX DIARIO: Lectura directa de los saldos digitados de los papeles físicos
    $sql_saldos = "SELECT 
                        e.id_empleado,
                        e.cedula,
                        TRIM(CONCAT(e.primer_apellido, ' ', e.segundo_apellido, ' ', e.primer_nombre, ' ', e.segundo_nombre)) AS empleado_nombre,
                        CASE 
                            WHEN hl.id_tipo_nombramiento = 1 THEN 'NOMB. PERMANENTE (CÓD. TRABAJO)'
                            WHEN hl.id_tipo_nombramiento = 2 THEN 'NOMB. PROVISIONAL (LOSEP)'
                            WHEN hl.id_tipo_nombramiento = 3 THEN 'CONTRATO OCASIONAL (LOSEP)'
                            ELSE 'CÓDIGO DE TRABAJO OBRERO'
                        END AS relacion_laboral,
                        COALESCE(vs.dias_totales_ganados, 0.00) AS dias_ganados,
                        COALESCE(vs.dias_consumidos, 0.00) AS dias_descontados,
                        -- Como es columna generada, la traemos directo de la base de datos
                        COALESCE(vs.dias_disponibles, 0.00) AS saldo_disponible
                   FROM empleados e
                   JOIN historial_laboral hl ON e.id_empleado = hl.id_empleado AND hl.activo = TRUE
                   LEFT JOIN vacaciones_saldo vs ON e.id_empleado = vs.id_empleado
                   WHERE e.estado = 'ACTIVO'
                   ORDER BY e.primer_apellido ASC, e.segundo_apellido ASC";
    
    $saldos = $pdo->query($sql_saldos)->fetchAll(PDO::FETCH_ASSOC);

    // 2. CONSULTA DE RESUMEN: Últimas 5 papeletas
    $sql_recientes = "SELECT p.id_permiso, p.numero_permiso, p.fecha_permiso, p.total_horas, p.estado_legalizacion,
                             TRIM(CONCAT(e.primer_apellido, ' ', e.segundo_apellido, ' ', e.primer_nombre, ' ', e.segundo_nombre)) AS empleado,
                             cc.nombre AS condicion
                      FROM permisos_ocasionales p
                      JOIN empleados e ON p.id_empleado = e.id_empleado
                      JOIN condiciones_concesion cc ON p.id_condicion = cc.id_condicion
                      ORDER BY p.id_permiso DESC 
                      LIMIT 5";
    
    $recientes = $pdo->query($sql_recientes)->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $mensaje = "<div class='alert error'>Error al sincronizar el kárdex diario: " . $e->getMessage() . "</div>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGA - Control de Vacaciones y Permisos</title>
    <link rel="stylesheet" href="../assets/estilos.css?v=<?= time(); ?>">
    <style>
        .resumen-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 20px; }
        .card-kardex { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .table-saldos { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 14px; }
        .table-saldos th, .table-saldos td { border: 1px solid #e2e8f0; padding: 10px; text-align: left; }
        .table-saldos th { background: #f8fafc; color: #334155; font-weight: bold; }
        .table-saldos tr:hover { background: #f1f5f9; }
        .saldo-positivo { color: #16a34a; font-weight: bold; }
        .saldo-alerta { color: #dc2626; font-weight: bold; }
        .badge-mini { padding: 3px 6px; border-radius: 4px; font-size: 10px; font-weight: bold; text-transform: uppercase; display: inline-block; }
        .badge-mini-aprobado { background: #dcfce7; color: #15803d; }
        .badge-mini-pendiente { background: #fef3c7; color: #d97706; }
        .badge-mini-rechazado { background: #fee2e2; color: #b91c1c; }
        .btn-view-ticket { display: block; text-align: center; background: #e2e8f0; color: #334155; text-decoration: none; padding: 6px; margin-top: 8px; border-radius: 4px; font-weight: bold; font-size: 12px; transition: 0.2s ease; }
        .btn-view-ticket:hover { background: #cbd5e1; color: #0f172a; }
    </style>
</head>
<body>

<div class="container">
    <div class="main-header">
        <div>
            <h2>PREFECTURA DE BOLÍVAR</h2>
            <p style="color: #64748b; margin: 0;">Módulo de Control de Asistencia e Incidentes de Personal</p>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <a href="revisar.php" class="btn btn-secondary">📥 Bandeja de Firmas</a>
            <a href="crear.php" class="btn btn-primary">➕ Registrar Permiso Físico</a>
        </div>
    </div>

    <?php echo $mensaje; ?>

    <div class="resumen-grid">
        
        <div class="card-kardex">
            <h3 style="margin-top: 0; color: #1e293b; border-bottom: 2px solid #0056b3; padding-bottom: 8px;">📊 Balance y Disponibilidad de Vacaciones al Día</h3>
            <table class="table-saldos">
                <thead>
                    <tr>
                        <th>Cédula</th>
                        <th>Servidor / Empleado</th>
                        <th>Relación Laboral</th>
                        <th style="text-align: center;">Ganados (Histórico)</th>
                        <th style="text-align: center;">Descontados (Sistema)</th>
                        <th style="text-align: center;">Saldo Disponible</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($saldos)): ?>
                        <tr><td colspan="6" style="text-align: center; color: gray; padding: 20px;">No hay personal activo con contratos configurados.</td></tr>
                    <?php else: ?>
                        <?php foreach($saldos as $s): ?>
                            <tr>
                                <td><?= htmlspecialchars($s['cedula']) ?></td>
                                <td><strong><?= htmlspecialchars($s['empleado_nombre']) ?></strong></td>
                                <td style="font-size: 12px; color: #475569;"><?= htmlspecialchars($s['relacion_laboral']) ?></td>
                                <td style="text-align: center; color: #475569;"><?= number_format($s['dias_ganados'], 2) ?> d</td>
                                <td style="text-align: center; color: #b91c1c;">-<?= number_format($s['dias_descontados'], 2) ?> d</td>
                                <td style="text-align: center;" class="<?= $s['saldo_disponible'] >= 1 ? 'saldo-positivo' : 'saldo-alerta' ?>">
                                    <?= number_format($s['saldo_disponible'], 2) ?> días
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="card-kardex" style="background: #f8fafc;">
            <h3 style="margin-top: 0; color: #1e293b; border-bottom: 2px solid #475569; padding-bottom: 8px;">🕒 Últimas Papeletas</h3>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <?php if(empty($recientes)): ?>
                    <li style="color: gray; font-size: 14px; text-align: center; padding: 20px;">No se registran papeletas recientes.</li>
                <?php else: ?>
                    <?php foreach($recientes as $r): ?>
                        <li style="background: #fff; padding: 12px; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 10px; font-size: 13px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                                <strong style="color: #b91c1c;">Nº <?= htmlspecialchars($r['numero_permiso']) ?></strong>
                                <span class="badge-mini badge-mini-<?= strtolower(htmlspecialchars($r['estado_legalizacion'] ?? 'pendiente')) ?>">
                                    <?= htmlspecialchars($r['estado_legalizacion'] ?? 'PENDIENTE') ?>
                                </span>
                            </div>
                            <p style="margin: 2px 0;"><strong>Funcionario:</strong> <?= htmlspecialchars($r['empleado']) ?></p>
                            <p style="margin: 2px 0; color: #64748b;">
                                Fecha: <?= date('d/m/Y', strtotime($r['fecha_permiso'])) ?> | Horas: <strong><?= number_format($r['total_horas'], 2) ?> hs</strong>
                            </p>
                            <p style="margin: 2px 0; color: #475569; font-size: 12px;">
                                Resol: <strong><?= htmlspecialchars($r['condicion']) ?></strong>
                            </p>
                            <a href="ver.php?id=<?= $r['id_permiso'] ?>" class="btn-view-ticket">🔍 Ver Boleta Completa</a>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>

    </div>
</div>

</body>
</html>