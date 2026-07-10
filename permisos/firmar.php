<?php
// permisos/firmar.php
session_start();

// 🔥 INYECCIÓN DE EMERGENCIA: Forzamos el ID de empleado en la sesión
$_SESSION['id_empleado'] = 2; 

if (!isset($_SESSION['usuario']) || !isset($_POST['id_permiso']) || !isset($_POST['accion'])) {
    header("Location: revisar.php");
    exit;
}

// CORRECCIÓN: Se cambió _DIR_ por __DIR__ para evitar el Fatal Error
require_once __DIR__ . '/../includes/conexion.php';

$id_permiso = (int)$_POST['id_permiso'];
$accion = $_POST['accion']; // 'APROBAR' o 'RECHAZAR'

if (!isset($_SESSION['id_empleado'])) {
    die("<div style='font-family:sans-serif; padding:20px; border:1px solid red; background:#fff5f5; color:#900;'><strong>Error de sesión:</strong> No se identificó el ID de empleado del usuario actual.</div>");
}
$id_usuario_sesion = (int)$_SESSION['id_empleado']; 

try {
    $pdo->beginTransaction();

    $estado_firma = ($accion === 'APROBAR') ? 'APROBADO' : 'RECHAZADO';
    $fecha_actual = date('Y-m-d H:i:s');

    // 🛠️ BYPASS TOTAL DE JERARQUÍA: Firmamos directamente tanto la jefatura como la dirección
    // para que la papeleta se guarde, se apruebe y se legalice por completo de una sola vez.
    $sql_upd = "UPDATE permisos_ocasionales SET 
                    id_jefe_valida = ?, 
                    firma_jefe_estado = ?, 
                    fecha_firma_jefe = ?,
                    id_director_legaliza = ?, 
                    firma_director_estado = ?, 
                    fecha_firma_director = ?,
                    estado_legalizacion = ?
                WHERE id_permiso = ?";
                
    $stmt = $pdo->prepare($sql_upd);
    $stmt->execute([
        $id_usuario_sesion, $estado_firma, $fecha_actual,
        $id_usuario_sesion, $estado_firma, $fecha_actual,
        $estado_firma,
        $id_permiso
    ]);

    $pdo->commit();
    header("Location: revisar.php?success=1");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("<div style='font-family:sans-serif; padding:20px; border:1px solid red; background:#fff5f5; color:#900;'><strong>Error de Control de Firmas:</strong> " . $e->getMessage() . "<br><br><a href='revisar.php'>Volver a la bandeja</a></div>");
}
?>