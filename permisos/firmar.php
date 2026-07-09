<?php
// permisos/firmar.php
session_start();

if (!isset($_SESSION['usuario']) || !isset($_POST['id_permiso']) || !isset($_POST['accion'])) {
    header("Location: revisar.php");
    exit;
}

require_once __DIR__ . '/../includes/conexion.php';

$id_permiso = (int)$_POST['id_permiso'];
$accion = $_POST['accion']; // 'APROBAR' o 'RECHAZAR'

// Se asume que guardas el id_empleado del usuario conectado en la sesión al hacer login
if (!isset($_SESSION['id_empleado'])) {
    die("Error de sesión: No se identificó el ID de empleado del usuario actual.");
}
$id_usuario_sesion = (int)$_SESSION['id_empleado']; 

try {
    $pdo->beginTransaction();

    // 1. OBTENER LA ESTRUCTURA LABORAL DEL DUEÑO DEL PERMISO PARA SABER QUIÉN ES SU JEFE Y SU DIRECTOR
    $sql_rol = "SELECT p.id_permiso, p.id_empleado AS id_solicitante,
                       jef.id_empleado_jefe AS id_empleado_jefe,
                       dir.id_empleado AS id_empleado_director
                FROM permisos_ocasionales p
                JOIN empleados e ON p.id_empleado = e.id_empleado
                JOIN historial_laboral hl ON e.id_empleado = hl.id_empleado AND hl.activo = TRUE
                JOIN cargos c ON hl.id_cargo = c.id_cargo
                JOIN jefaturas jef ON c.id_jefatura = jef.id_jefatura
                JOIN direcciones dir ON jef.id_direccion = dir.id_direccion
                WHERE p.id_permiso = ?";
    
    $stmt_rol = $pdo->prepare($sql_rol);
    $stmt_rol->execute([$id_permiso]);
    $permiso_info = $stmt_rol->fetch(PDO::FETCH_ASSOC);

    if (!$permiso_info) {
        throw new Exception("El permiso solicitado no existe o el empleado no posee una estructura laboral activa.");
    }

    $estado_firma = ($accion === 'APROBAR') ? 'APROBADO' : 'RECHAZADO';
    $fecha_actual = date('Y-m-d H:i:s');
    $se_firmo = false;

    // CASO A: El usuario en sesión es el JEFE INMEDIATO del solicitante
    if ($id_usuario_sesion == $permiso_info['id_empleado_jefe']) {
        $sql_upd = "UPDATE permisos_ocasionales SET 
                        id_jefe_valida = ?, 
                        firma_jefe_estado = ?, 
                        fecha_firma_jefe = ? 
                    WHERE id_permiso = ?";
        $pdo->prepare($sql_upd)->execute([$id_usuario_sesion, $estado_firma, $fecha_actual, $id_permiso]);
        $se_firmo = true;
    } 

    // CASO B: El usuario en sesión es el DIRECTOR de la unidad macro
    if ($id_usuario_sesion == $permiso_info['id_empleado_director']) {
        $sql_upd = "UPDATE permisos_ocasionales SET 
                        id_director_legaliza = ?, 
                        firma_director_estado = ?, 
                        fecha_firma_director = ? 
                    WHERE id_permiso = ?";
        $pdo->prepare($sql_upd)->execute([$id_usuario_sesion, $estado_firma, $fecha_actual, $id_permiso]);
        $se_firmo = true;
    }

    if (!$se_firmo) {
        throw new Exception("No tienes jerarquía o asignación como Jefe o Director sobre este subordinado para firmar esta papeleta.");
    }

    // 2. CONTROL GLOBAL DE LEGALIZACIÓN EN LA PAPELETA
    // Volvemos a consultar los estados de firmas actualizados en esta transacción
    $stmt_chk = $pdo->prepare("SELECT firma_jefe_estado, firma_director_estado FROM permisos_ocasionales WHERE id_permiso = ?");
    $stmt_chk->execute([$id_permiso]);
    $estados_actuales = $stmt_chk->fetch(PDO::FETCH_ASSOC);

    // Si ambas autoridades aprobaron, el permiso se legaliza y se activa automáticamente el TRIGGER de vacaciones
    if ($estados_actuales['firma_jefe_estado'] == 'APROBADO' && $estados_actuales['firma_director_estado'] == 'APROBADO') {
        $pdo->prepare("UPDATE permisos_ocasionales SET estado_legalizacion = 'APROBADO' WHERE id_permiso = ?")
            ->execute([$id_permiso]);
    } 
    // Si cualquiera de las dos autoridades lo rechaza, el flujo muere y pasa a RECHAZADO
    elseif ($estados_actuales['firma_jefe_estado'] == 'RECHAZADO' || $estados_actuales['firma_director_estado'] == 'RECHAZADO') {
        $pdo->prepare("UPDATE permisos_ocasionales SET estado_legalizacion = 'RECHAZADO' WHERE id_permiso = ?")
            ->execute([$id_permiso]);
    }

    $pdo->commit();
    header("Location: revisar.php?success=1");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    // Puedes cambiar este mensaje por un redireccionamiento con error si prefieres integrarlo a la UI
    die("<div style='font-family:sans-serif; padding:20px; border:1px solid red; background:#fff5f5; color:#900;'><strong>Error de Control de Firmas:</strong> " . $e->getMessage() . "<br><br><a href='revisar.php'>Volver a la bandeja</a></div>");
}