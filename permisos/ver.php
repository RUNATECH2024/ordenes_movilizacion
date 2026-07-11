<?php
// permisos/ver.php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . '/../includes/conexion.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID de permiso no válido");
}

try {
    // 1. DATOS GENERALES DEL PERMISO
    $queryPermiso = $pdo->prepare("
        SELECT p.*,
               e.id_empleado,
               e.primer_nombre || ' ' || e.primer_apellido AS empleado_nombre,
               e.cedula AS empleado_cedula,
               cp.nombre AS clase_permiso_nombre,
               cc.nombre AS condicion_concesion_nombre
        FROM permisos_ocasionales p
        JOIN empleados e ON p.id_empleado = e.id_empleado
        LEFT JOIN clases_permiso cp ON p.id_clase_permiso = cp.id_clase_permiso
        LEFT JOIN condiciones_concesion cc ON p.id_condicion = cc.id_condicion
        WHERE p.id_permiso = :id
    ");
    $queryPermiso->execute([':id' => $id]);
    $permiso = $queryPermiso->fetch(PDO::FETCH_ASSOC);

    if (!$permiso) {
        die("Permiso no encontrado");
    }

    // 2. BUSCAR EL JEFE INMEDIATO DINÁMICO
    $queryJefe = $pdo->prepare("
        SELECT ej.primer_nombre || ' ' || ej.primer_apellido AS jefe_nombre
        FROM historial_laboral hl
        JOIN cargos c ON hl.id_cargo = c.id_cargo
        JOIN jefaturas j ON c.id_jefatura = j.id_jefatura
        JOIN historial_jefaturas hj ON j.id_jefatura = hj.id_jefatura
        JOIN empleados ej ON hj.id_empleado_jefe = ej.id_empleado
        WHERE hl.id_empleado = :id_empleado 
          AND hl.activo = true 
          AND hj.estado = 'ACTIVO'
        LIMIT 1
    ");
    $queryJefe->execute([':id_empleado' => $permiso['id_empleado']]);
    $jefeRes = $queryJefe->fetch(PDO::FETCH_ASSOC);
    
    if (!empty($permiso['id_jefe_valida'])) {
        $queryJefeFijo = $pdo->prepare("SELECT primer_nombre || ' ' || primer_apellido AS jefe_nombre FROM empleados WHERE id_empleado = :id");
        $queryJefeFijo->execute([':id' => $permiso['id_jefe_valida']]);
        $jefeFijo = $queryJefeFijo->fetch(PDO::FETCH_ASSOC);
        $jefe_nombre = $jefeFijo['jefe_nombre'] ?? '';
    } else {
        $jefe_nombre = $jefeRes['jefe_nombre'] ?? 'JEFE INMEDIATO PENDIENTE';
    }

    // 3. BUSCAR AL DIRECTOR ADMINISTRATIVO
    $queryDirector = $pdo->prepare("
        SELECT ed.primer_nombre || ' ' || ed.primer_apellido AS director_nombre
        FROM direcciones d
        JOIN directores dir ON d.id_direccion = dir.id_direccion
        JOIN empleados ed ON dir.id_empleado = ed.id_empleado
        WHERE d.codigo = 'DA'
          AND dir.estado = 'ACTIVO'
        LIMIT 1
    ");
    $queryDirector->execute();
    $dirRes = $queryDirector->fetch(PDO::FETCH_ASSOC);

    if (!empty($permiso['id_director_legaliza'])) {
        $queryDirFijo = $pdo->prepare("SELECT primer_nombre || ' ' || primer_apellido AS director_nombre FROM empleados WHERE id_empleado = :id");
        $queryDirFijo->execute([':id' => $permiso['id_director_legaliza']]);
        $dirFijo = $queryDirFijo->fetch(PDO::FETCH_ASSOC);
        $director_nombre = $dirFijo['director_nombre'] ?? '';
    } else {
        $director_nombre = $dirRes['director_nombre'] ?? 'DIRECTOR ADMINISTRATIVO';
    }

} catch (PDOException $e) {
    die("Error en la base de datos: " . $e->getMessage());
}

$numeroPermiso = str_pad($permiso['id_permiso'] ?? $id, 7, "0", STR_PAD_LEFT);

// Formateo de tiempos
$fechaDetalle = $permiso['fecha_permiso'] ? date("d/m/Y", strtotime($permiso['fecha_permiso'])) : date("d/m/Y", strtotime($permiso['fecha_registro']));
$horaSalida = $permiso['hora_salida'] ? date("H:i", strtotime($permiso['hora_salida'])) : '________';
$horaLlegada = $permiso['hora_llegada'] ? date("H:i", strtotime($permiso['hora_llegada'])) : '________';
$totalDias = $permiso['total_dias'] ?? '0';
$totalHoras = $permiso['total_horas'] ?? '0';

$motivo = strtolower($permiso['clase_permiso_nombre'] ?? '');
$tipoConcesion = strtolower($permiso['condicion_concesion_nombre'] ?? '');
$observaciones = $permiso['observaciones'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SGA - Vista Previa de Permiso</title>
    <style>
        body { font-family: 'Helvetica', Arial, sans-serif; font-size: 13px; color: #000; background-color: #f4f6f9; padding: 20px; margin: 0; }
        .wrapper { max-width: 800px; margin: 0 auto; background: #fff; padding: 20px; box-shadow: 0 4px 8px rgba(0,0,0,0.05); border-radius: 4px; }
        
        /* Controles de Acción Superior */
        .toolbar { display: flex; justify-content: space-between; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #ddd; }
        .btn { padding: 8px 16px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; text-decoration: none; font-size: 12px; display: inline-flex; align-items: center; }
        .btn-secondary { background-color: #6c757d; color: #fff; }
        .btn-primary { background-color: #003399; color: #fff; }
        .btn:hover { opacity: 0.9; }

        /* Contenedor Espejo de la Papeleta */
        .papeleta-container { border: 2px solid #003399; padding: 20px; box-sizing: border-box; background: #fff; }
        
        .encabezado-tabla { width: 100%; border-collapse: collapse; margin-bottom: 20px; table-layout: fixed; }
        .logo-seccion { text-align: center; color: #003399; width: 50%; vertical-align: middle; }
        .logo-seccion h1 { margin: 0; font-size: 16px; font-weight: bold; text-transform: uppercase; }
        .logo-seccion p { margin: 3px 0 0 0; font-size: 11px; font-style: italic; }
        .titulo-seccion { text-align: center; color: #003399; width: 50%; vertical-align: middle; }
        .titulo-seccion h2 { margin: 0; font-size: 18px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .titulo-seccion .num-doc { font-size: 18px; color: #cc0000; font-weight: bold; margin-top: 5px; }
        
        .solicitante-tabla { width: 100%; border-collapse: collapse; margin-bottom: 20px; table-layout: fixed; }
        .linea-puntos { border-bottom: 1px dotted #003399; font-size: 13px; font-weight: bold; height: 28px; vertical-align: bottom; color: #000; }
        .subtexto { font-size: 10px; color: #003399; font-weight: bold; text-align: center; padding-top: 5px; text-transform: uppercase; }
        
        .tabla-principal { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .tabla-principal td { border: 1px solid #003399; padding: 10px; vertical-align: top; }
        .label-vertical { color: #003399; text-align: center; vertical-align: middle !important; font-weight: bold; width: 18%; font-size: 10px; text-transform: uppercase; line-height: 1.3; }
        
        .subtabla-checks { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .subtabla-checks td { border: none !important; padding: 6px 0 !important; color: #003399; width: 50%; font-size: 12px; font-weight: bold; }
        .subtabla-checks span.marcado { color: #000; font-weight: bold; }
        .campo-subrayado { border-bottom: 1px dotted #003399; color: #000; font-weight: bold; padding: 0 4px; }
        
        .firma-celda { text-align: center; vertical-align: bottom !important; width: 28%; height: 100px; padding: 5px !important; }
        .linea-firma { border-top: 1px dotted #003399; margin-top: 55px; padding-top: 5px; font-size: 11px; font-weight: bold; color: #000; text-transform: uppercase; }
    </style>
</head>
<body>

<div class="wrapper">

    <!-- BARRA DE ACCIONES -->
    <div class="toolbar">
        <a href="lista_permisos.php" class="btn btn-secondary">← Volver al Listado</a>
        <a href="imprimir_permiso.php?id=<?= $id ?>" class="btn btn-primary">Descargar Papeleta (PDF) ↓</a>
    </div>

    <!-- FORMATO BOLETA -->
    <div class="papeleta-container">

        <!-- ENCABEZADO -->
        <table class="encabezado-tabla">
            <tr>
                <td class="logo-seccion">
                    <img src="../assets/img/logo.png" style="height: 48px; margin-bottom: 4px;" alt="Logo" onerror="this.style.display='none'"><br>
                    <h1>PREFECTURA DE BOLÍVAR</h1>
                    <p>Un nuevo tiempo<br>Hombro a hombro</p>
                </td>
                <td class="titulo-seccion">
                    <h2>PERMISO OCASIONAL</h2>
                    <div class="num-doc">Nº <?= $numeroPermiso ?></div>
                </td>
            </tr>
        </table>

        <!-- INFORMACIÓN DEL SOLICITANTE -->
        <table class="solicitante-tabla">
            <tr>
                <td width="68%" class="linea-puntos">
                    &nbsp;<?= htmlspecialchars($permiso['empleado_nombre']) ?> &nbsp;&nbsp;&nbsp; (C.I: <?= htmlspecialchars($permiso['empleado_cedula']) ?>)
                </td>
                <td width="5%"></td>
                <td width="27%" class="linea-puntos"></td>
            </tr>
            <tr>
                <td class="subtexto" style="text-align: left; padding-left: 10px;">NOMBRE Y CÉDULA</td>
                <td></td>
                <td class="subtexto">FIRMA EMPLEADO</td>
            </tr>
        </table>

        <!-- TABLA MATRIZ -->
        <table class="tabla-principal">
            <tr>
                <!-- CLASE DE PERMISO -->
                <td class="label-vertical">CLASE DE<br>PERMISO</td>
                <td width="45%">
                    <table class="subtabla-checks">
                        <tr>
                            <td><?= (strpos($motivo, 'oficina') !== false ? '( X )' : '( &nbsp; )') ?> Oficina</td>
                            <td><?= (strpos($motivo, 'calamidad') !== false ? '( X )' : '( &nbsp; )') ?> Calamidad Doméstica</td>
                        </tr>
                        <tr>
                            <td><?= (strpos($motivo, 'personal') !== false ? '( X )' : '( &nbsp; )') ?> <span class="marcado">Personal</span></td>
                            <td><?= (strpos($motivo, 'enfermedad') !== false ? '( X )' : '( &nbsp; )') ?> Enfermedad</td>
                        </tr>
                    </table>
                </td>
                <!-- JEFE INMEDIATO -->
                <td class="label-vertical" width="10%">JEFE<br>INMEDIATO</td>
                <td class="firma-celda">
                    <div class="linea-firma"><?= htmlspecialchars($jefe_nombre) ?></div>
                    <div style="font-size: 8px; color: #003399; margin-top: 3px; font-weight: bold;">ESTADO: <?= htmlspecialchars($permiso['firma_jefe_estado'] ?? 'PENDIENTE') ?></div>
                    <div class="subtexto">FIRMA</div>
                </td>
            </tr>

            <tr>
                <!-- TIEMPO DE PERMISO -->
                <td class="label-vertical">TIEMPO DE<br>PERMISO</td>
                <td style="line-height: 1.6;">
                    Día(s): <span class="campo-subrayado">&nbsp;<?= $fechaDetalle ?>&nbsp;</span><br>
                    Hora de Salida: <span class="campo-subrayado">&nbsp;<?= $horaSalida ?>&nbsp;</span> &nbsp;&nbsp;
                    Hora de Llegada: <span class="campo-subrayado">&nbsp;<?= $horaLlegada ?>&nbsp;</span><br>
                    Total: &nbsp;&nbsp;&nbsp;&nbsp; Días: <span class="campo-subrayado">&nbsp;<?= $totalDias ?>&nbsp;</span> &nbsp;&nbsp;&nbsp;&nbsp; Horas: <span class="campo-subrayado">&nbsp;<?= $totalHoras ?>&nbsp;</span>
                </td>
                <!-- LEGALIZADO POR TALENTO HUMANO -->
                <td class="label-vertical">LEGALIZADO</td>
                <td class="firma-celda">
                    <div class="linea-firma"><?= htmlspecialchars($director_nombre) ?></div>
                    <div style="font-size: 8px; color: #003399; margin-top: 3px; font-weight: bold;">ESTADO: <?= htmlspecialchars($permiso['estado_legalizacion'] ?? 'PENDIENTE') ?></div>
                    <div class="subtexto">FIRMA TALENTO HUMANO</div>
                </td>
            </tr>

            <tr>
                <!-- CONCESIÓN -->
                <td class="label-vertical">JEFE DE<br>DEPARTAMENTO</td>
                <td colspan="3">
                    <div style="color: #003399; font-weight: bold; margin-bottom: 8px;">El Permiso se concede:</div>
                    <table class="subtabla-checks" style="width: 100%;">
                        <tr>
                            <td style="width: 25%;"><?= (strpos($tipoConcesion, 'con sueldo') !== false ? '( X )' : '( &nbsp; )') ?> Con Sueldo</td>
                            <td style="width: 25%;"><?= (strpos($tipoConcesion, 'sin sueldo') !== false ? '( X )' : '( &nbsp; )') ?> Sin Sueldo</td>
                            <td style="width: 25%;"><?= (strpos($tipoConcesion, 'compens') !== false ? '( X )' : '( &nbsp; )') ?> Comp. horas extras</td>
                            <td style="width: 25%;"><?= (strpos($tipoConcesion, 'vacaci') !== false ? '( X )' : '( &nbsp; )') ?> Desc. Vacación</td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr>
                <!-- OBSERVACIONES -->
                <td class="label-vertical">OFICINA DE<br>PERSONAL /<br>OBSERVACIONES</td>
                <td colspan="3" style="min-height: 50px; font-weight: bold; line-height: 1.5; color: #000;">
                    <?= htmlspecialchars($observaciones) ?>
                </td>
            </tr>
        </table>

    </div>
</div>

</body>
</html>