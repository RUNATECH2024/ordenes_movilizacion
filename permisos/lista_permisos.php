<?php
// permisos/lista_permisos.php
session_start();

// 1. VALIDACIÓN DE SESIÓN INTERNA
if (!isset($_SESSION['usuario']) || !isset($_SESSION['id_empleado'])) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../includes/conexion.php';

$id_usuario_sesion = (int)$_SESSION['id_empleado'];
$msg_success = isset($_GET['success']) ? "¡Operación realizada con éxito!" : "";

try {
    // 2. CONSULTA GENERAL DE PERMISOS OCASIONALES
    // Concatenamos los 4 campos de nombre de tu tabla empleados manejando posibles nulos con COALESCE
    $sql = "SELECT p.*, 
            TRIM(
                COALESCE(e.primer_nombre, '') || ' ' || 
                COALESCE(e.segundo_nombre, '') || ' ' || 
                COALESCE(e.primer_apellido, '') || ' ' || 
                COALESCE(e.segundo_apellido, '')
            ) AS nombre_empleado 
            FROM permisos_ocasionales p
            LEFT JOIN empleados e ON p.id_empleado = e.id_empleado
            ORDER BY p.id_permiso DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $permisos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Error al cargar la lista de permisos: " . $e->getMessage());
}

// Función auxiliar para pintar las etiquetas de estado
function obtenerBadgeEstado($estado) {
    switch ($estado) {
        case 'APROBADO':
            return '<span style="background-color: #d4edda; color: #155724; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 0.85em;">APROBADO</span>';
        case 'RECHAZADO':
            return '<span style="background-color: #f8d7da; color: #721c24; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 0.85em;">RECHAZADO</span>';
        default:
            return '<span style="background-color: #fff3cd; color: #856404; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 0.85em;">PENDIENTE</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SGA - Listado General de Permisos Ocasionales</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f6f9; color: #333; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header-sga { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #0056b3; padding-bottom: 15px; margin-bottom: 20px; }
        .header-sga h2 { margin: 0; color: #0056b3; font-size: 24px; }
        .btn { padding: 8px 12px; border-radius: 4px; text-decoration: none; font-size: 14px; font-weight: bold; display: inline-block; cursor: pointer; border: none; }
        .btn-primary { background-color: #0056b3; color: white; }
        .btn-primary:hover { background-color: #004085; }
        .btn-info { background-color: #17a2b8; color: white; }
        .btn-info:hover { background-color: #138496; }
        .btn-success { background-color: #28a745; color: white; }
        .btn-success:hover { background-color: #218838; }
        .alert-success { background-color: #d4edda; color: #155724; padding: 12px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #c3e6cb; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #e0e0e0; font-size: 14px; }
        th { background-color: #f8f9fa; color: #495057; font-weight: 600; }
        tr:hover { background-color: #f1f3f5; }
        .text-center { text-align: center; }
        .actions { display: flex; gap: 5px; justify-content: center; }
    </style>
</head>
<body>

<div class="container">
    
    <div class="header-sga">
        <div>
            <h2>PREFECTURA DE BOLÍVAR</h2>
            <small style="color: #6c757d; font-weight: bold; letter-spacing: 1px;">SGA - CONTROL DE PERMISOS</small>
        </div>
        <div>
            <a href="crear.php" class="btn btn-primary">+ Nuevo Permiso</a>
            <a href="../index.php" class="btn" style="background: #6c757d; color: white;">Volver al Menú</a>
        </div>
    </div>

    <?php if ($msg_success): ?>
        <div class="alert-success"><?= htmlspecialchars($msg_success) ?></div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th width="80" class="text-center">Nº Permiso</th>
                <th>Empleado / Solicitante</th>
                <th class="text-center">Fecha Permiso</th>
                <th class="text-center">Tiempo Total</th>
                <th class="text-center">Firma Jefe</th>
                <th class="text-center">Firma Director</th>
                <th class="text-center">Estado General</th>
                <th width="150" class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($permisos)): ?>
                <tr>
                    <td colspan="8" class="text-center" style="color: #6c757d; padding: 30px;">No se han registrado permisos ocasionales en el sistema.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($permisos as $p): ?>
                    <tr>
                        <td class="text-center" style="font-weight: bold; color: #0056b3;">
                            <?= str_pad($p['id_permiso'], 7, "0", STR_PAD_LEFT) ?>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars(!empty($p['nombre_empleado']) ? $p['nombre_empleado'] : 'EMPLEADO NO IDENTIFICADO') ?></strong>
                        </td>
                        <td class="text-center">
                            <?= date('d/m/Y', strtotime($p['fecha_permiso'] ?? date('Y-m-d'))) ?>
                        </td>
                        <td class="text-center">
                            <?= $p['total_dias'] ?? 0 ?> Día(s) / <?= number_format($p['total_horas'] ?? 0, 2) ?> Hrs
                        </td>
                        <td class="text-center">
                            <?= obtenerBadgeEstado($p['firma_jefe_estado']) ?>
                        </td>
                        <td class="text-center">
                            <?= obtenerBadgeEstado($p['firma_director_estado']) ?>
                        </td>
                        <td class="text-center">
                            <?= obtenerBadgeEstado($p['estado_legalizacion']) ?>
                        </td>
                        <td class="actions">
                            <!-- Botón para ver y firmar (revisar la papeleta en pantalla) -->
                            <a href="ver.php?id=<?= $p['id_permiso'] ?>" class="btn btn-info" title="Ver e Inspeccionar">Ver</a>
                            
                            <!-- Botón para imprimir PDF (Solo si ya está completamente aprobado) -->
                            <?php if ($p['estado_legalizacion'] === 'APROBADO'): ?>
                                <a href="imprimir_permiso.php?id=<?= $p['id_permiso'] ?>" target="_blank" class="btn btn-success" title="Imprimir Comprobante">Imprimir</a>
                            <?php else: ?>
                                <button class="btn" style="background:#ced4da; color:#6c757d;" disabled title="Disponible al aprobarse">Imprimir</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>