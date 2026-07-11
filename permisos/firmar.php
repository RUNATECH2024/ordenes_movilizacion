<?php
// permisos/firmar.php
session_start();

// 1. VALIDACIÓN DE SESIÓN Y PARÁMETROS
if (!isset($_SESSION['usuario']) || !isset($_SESSION['id_empleado']) || !isset($_POST['id_permiso']) || !isset($_POST['accion'])) {
    header("Location: revisar.php");
    exit;
}

require_once __DIR__ . '/../includes/conexion.php';

$id_permiso = (int)$_POST['id_permiso'];
$accion = $_POST['accion']; // 'APROBAR' o 'RECHAZAR'
$id_usuario_sesion = (int)$_SESSION['id_empleado']; 

// Mapeamos el estado final según la acción elegida
$estado_firma = ($accion === 'APROBAR') ? 'APROBADO' : 'RECHAZADO';
$fecha_actual = date('Y-m-d H:i:s');

try {
    $pdo->beginTransaction();

    // 2. LEER LA INFORMACIÓN DEL PERMISO (Bloqueo para evitar concurrencia)
    $queryPermiso = $pdo->prepare("
        SELECT id_permiso, id_jefe_valida, id_director_legaliza, firma_jefe_estado, firma_director_estado, estado_legalizacion
        FROM permisos_ocasionales 
        WHERE id_permiso = :id FOR UPDATE
    ");
    $queryPermiso->execute([':id' => $id_permiso]);
    $permiso = $queryPermiso->fetch(PDO::FETCH_ASSOC);

    if (!$permiso) {
        throw new Exception("El permiso solicitado Nº $id_permiso no existe en el sistema.");
    }

    // 3. DETERMINAR EL ROL DE MANERA DINÁMICA UTILIZANDO LAS IDS REGISTRADAS
    // COMODÍN DE DESARROLLO: Si tu ID es 2, el sistema te considerará tanto Jefe como Director para poder firmar todo.
    $esJefeReal = (!empty($permiso['id_jefe_valida']) && (int)$permiso['id_jefe_valida'] === $id_usuario_sesion) || $id_usuario_sesion === 2;
    $esDirectorReal = (!empty($permiso['id_director_legaliza']) && (int)$permiso['id_director_legaliza'] === $id_usuario_sesion) || $id_usuario_sesion === 2;

    if (!$esJefeReal && !$esDirectorReal) {
        throw new Exception(
            "Acceso denegado. <br>" .
            "- Tu ID en sesión: <strong>" . $id_usuario_sesion . "</strong><br>" .
            "- ID del Jefe en la Papeleta: <strong>" . ($permiso['id_jefe_valida'] ?? 'VACÍO') . "</strong><br>" .
            "- ID del Director en la Papeleta: <strong>" . ($permiso['id_director_legaliza'] ?? 'VACÍO') . "</strong>"
        );
    }

    $campos_update = [];
    $valores_update = [];

    // 4. ACCIÓN: SI ES EL JEFE INMEDIATO ASIGNADO (O EL ADMINISTRADOR ID 2)
    if ($esJefeReal) {
        $campos_update[] = "firma_jefe_estado = ?";
        $campos_update[] = "fecha_firma_jefe = ?";
        
        $valores_update[] = $estado_firma;
        $valores_update[] = $fecha_actual;
    }

    // 5. ACCIÓN: SI ES EL DIRECTOR DE TALENTO HUMANO / ADMINISTRATIVO ASIGNADO (O EL ADMINISTRADOR ID 2)
    if ($esDirectorReal) {
        $campos_update[] = "firma_director_estado = ?";
        $campos_update[] = "fecha_firma_director = ?";
        
        $valores_update[] = $estado_firma;
        $valores_update[] = $fecha_actual;
    }

    // 6. CONTROL DEL ESTADO GLOBAL DE LEGALIZACIÓN (Clave para el Trigger de Vacaciones)
    // Evaluamos cómo quedarán los estados combinados. 
    // Nota: Si eres ID 2, como cumples ambas condiciones, el script firmará ambos campos al mismo tiempo.
    $jefe_estado_futuro = $esJefeReal ? $estado_firma : $permiso['firma_jefe_estado'];
    $director_estado_futuro = $esDirectorReal ? $estado_firma : $permiso['firma_director_estado'];

    if ($jefe_estado_futuro === 'RECHAZADO' || $director_estado_futuro === 'RECHAZADO') {
        // Si cualquiera de las dos autoridades lo rechaza, el permiso muere de forma global
        $campos_update[] = "estado_legalizacion = 'RECHAZADO'";
    } elseif ($jefe_estado_futuro === 'APROBADO' && $director_estado_futuro === 'APROBADO') {
        // ¡AMBAS FIRMAS COMPLETADAS! Aquí se cambia a APROBADO y el trigger descuenta automáticamente las horas
        $campos_update[] = "estado_legalizacion = 'APROBADO'";
    } else {
        // Si uno ya aprobó pero el otro sigue pendiente
        $campos_update[] = "estado_legalizacion = 'PENDIENTE'";
    }

    // 7. EJECUTAR LA ACTUALIZACIÓN EN POSTGRESQL
    if (!empty($campos_update)) {
        $sql_upd = "UPDATE permisos_ocasionales SET " . implode(", ", $campos_update) . " WHERE id_permiso = ?";
        $valores_update[] = $id_permiso;

        $stmt = $pdo->prepare($sql_upd);
        $stmt->execute($valores_update);
    }

    $pdo->commit();
    header("Location: revisar.php?success=1");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Eliminamos el htmlspecialchars del mensaje para que las etiquetas <br> y <strong> de nuestro aviso se muestren bien en pantalla si algo más ocurre
    die("<div style='font-family:sans-serif; padding:20px; border:1px solid red; background:#fff5f5; color:#900;'><strong>Error de Control de Firmas:</strong> " . $e->getMessage() . "<br><br><a href='revisar.php'>Volver a la bandeja</a></div>");
}
?>