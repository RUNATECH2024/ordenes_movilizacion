<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../includes/conexion.php';

$mensaje = $_GET['mensaje'] ?? '';
$error = $_GET['error'] ?? '';

try {
    /*
     * Garantiza que todo empleado activo cuyo cargo vigente sea CHOFER/CONDUCTOR
     * tenga su registro técnico en la tabla choferes. La persona se administra
     * únicamente desde Empleados; aquí solo se gestiona el vehículo.
     */
    $pdo->exec("
        INSERT INTO choferes (
            id_empleado,
            cedula,
            nombres,
            apellidos,
            fecha_nacimiento,
            telefono,
            correo,
            cargo,
            grupo_sanguineo,
            fecha_ingreso,
            estado,
            foto
        )
        SELECT
            e.id_empleado,
            e.cedula,
            TRIM(e.primer_nombre || ' ' || COALESCE(e.segundo_nombre, '')),
            TRIM(e.primer_apellido || ' ' || COALESCE(e.segundo_apellido, '')),
            e.fecha_nacimiento,
            COALESCE(cp.celular, cp.telefono),
            cp.correo_personal,
            c.nombre,
            ts.nombre,
            hl.fecha_inicio,
            e.estado,
            e.foto
        FROM empleados e
        JOIN LATERAL (
            SELECT hl1.*
            FROM historial_laboral hl1
            WHERE hl1.id_empleado = e.id_empleado
              AND hl1.activo = TRUE
            ORDER BY hl1.fecha_inicio DESC, hl1.id_historial DESC
            LIMIT 1
        ) hl ON TRUE
        JOIN cargos c ON c.id_cargo = hl.id_cargo
        LEFT JOIN LATERAL (
            SELECT cp1.celular, cp1.telefono, cp1.correo_personal
            FROM contacto_personal cp1
            WHERE cp1.id_empleado = e.id_empleado
            LIMIT 1
        ) cp ON TRUE
        LEFT JOIN tipos_sangre ts ON ts.id_tipo_sangre = e.id_tipo_sangre
        WHERE e.estado = 'ACTIVO'
          AND (
              UPPER(c.nombre) LIKE '%CHOFER%'
              OR UPPER(c.nombre) LIKE '%CONDUCTOR%'
          )
          AND NOT EXISTS (
              SELECT 1
              FROM choferes ch
              WHERE ch.id_empleado = e.id_empleado
          )
    ");

    $stmt = $pdo->query("
        WITH cargo_chofer_actual AS (
            SELECT DISTINCT ON (hl.id_empleado)
                hl.id_empleado,
                c.nombre AS cargo_nombre
            FROM historial_laboral hl
            JOIN cargos c ON c.id_cargo = hl.id_cargo
            WHERE hl.activo = TRUE
              AND (
                  UPPER(c.nombre) LIKE '%CHOFER%'
                  OR UPPER(c.nombre) LIKE '%CONDUCTOR%'
              )
            ORDER BY hl.id_empleado, hl.fecha_inicio DESC, hl.id_historial DESC
        )
        SELECT
            ch.id_chofer,
            ch.id_empleado,
            e.cedula,
            e.primer_nombre,
            e.segundo_nombre,
            e.primer_apellido,
            e.segundo_apellido,
            e.foto AS foto_empleado,
            ch.foto AS foto_chofer,
            ch.estado AS estado_chofer,
            cca.cargo_nombre,
            cp.celular,
            cp.telefono,
            v.id_vehiculo,
            v.placa,
            v.marca,
            v.modelo,
            v.tipo,
            d.nombre AS direccion_vehiculo
        FROM cargo_chofer_actual cca
        JOIN empleados e ON e.id_empleado = cca.id_empleado
        JOIN choferes ch ON ch.id_empleado = e.id_empleado
        LEFT JOIN LATERAL (
            SELECT cp1.celular, cp1.telefono
            FROM contacto_personal cp1
            WHERE cp1.id_empleado = e.id_empleado
            LIMIT 1
        ) cp ON TRUE
        LEFT JOIN LATERAL (
            SELECT v1.*
            FROM vehiculos v1
            WHERE v1.id_chofer = ch.id_chofer
            ORDER BY v1.id_vehiculo DESC
            LIMIT 1
        ) v ON TRUE
        LEFT JOIN direcciones d ON d.id_direccion = v.id_direccion
        WHERE e.estado = 'ACTIVO'
          AND UPPER(COALESCE(ch.estado, 'ACTIVO')) = 'ACTIVO'
        ORDER BY e.primer_apellido, e.segundo_apellido, e.primer_nombre, e.segundo_nombre
    ");
    $choferes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $vehiculosLibres = $pdo->query("
        SELECT
            v.id_vehiculo,
            v.placa,
            v.marca,
            v.modelo,
            v.tipo,
            d.nombre AS direccion_nombre
        FROM vehiculos v
        LEFT JOIN direcciones d ON d.id_direccion = v.id_direccion
        WHERE v.id_chofer IS NULL
        ORDER BY v.placa, v.marca, v.modelo
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error al cargar el módulo de choferes: " . htmlspecialchars($e->getMessage()));
}

function nombreCompleto(array $chofer): string
{
    return trim(implode(' ', array_filter([
        $chofer['primer_nombre'] ?? '',
        $chofer['segundo_nombre'] ?? '',
        $chofer['primer_apellido'] ?? '',
        $chofer['segundo_apellido'] ?? ''
    ])));
}

function descripcionVehiculo(array $vehiculo): string
{
    $detalle = trim(($vehiculo['marca'] ?? '') . ' ' . ($vehiculo['modelo'] ?? ''));
    $direccion = trim((string)($vehiculo['direccion_nombre'] ?? $vehiculo['direccion_vehiculo'] ?? ''));
    $texto = trim(($vehiculo['placa'] ?? '') . ($detalle !== '' ? ' - ' . $detalle : ''));
    return $direccion !== '' ? $texto . ' | ' . $direccion : $texto . ' | Sin dirección';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignación de Vehículos a Choferes</title>
    <link rel="stylesheet" href="../assets/estilos.css">
    <style>
        .descripcion-modulo {
            background: #eef5ff;
            border-left: 4px solid #1769d2;
            border-radius: 6px;
            padding: 12px 15px;
            margin: 14px 0 18px;
        }
        .alerta-exito, .alerta-error, .alerta-info {
            border-radius: 6px;
            margin: 12px 0;
            padding: 11px 14px;
        }
        .alerta-exito { background: #dff6e7; color: #146c35; }
        .alerta-error { background: #fde3e3; color: #9b1c1c; }
        .alerta-info { background: #fff4d5; color: #7a5700; }
        .foto-empleado {
            width: 58px;
            height: 58px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid #d2d8e0;
        }
        .sin-foto {
            width: 58px;
            height: 58px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #edf0f4;
            font-size: 28px;
        }
        .asignacion-form {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 390px;
        }
        .asignacion-form select {
            min-width: 285px;
            padding: 8px;
        }
        .asignacion-form button {
            white-space: nowrap;
        }
        .vehiculo-actual {
            line-height: 1.45;
            min-width: 180px;
        }
        .sin-asignar { color: #777; font-style: italic; }
        .table-container { overflow-x: auto; }
    </style>
</head>
<body>
<div class="container">
    <h2>🚘 Asignación de Vehículos a Choferes</h2>

    <div class="menu">
        <div>
            <a href="../panel_administracion.php" class="btn btn-primary">← Panel</a>
            <a href="../empleados/crear.php" class="btn btn-success">➕ Registrar empleado</a>
            <a href="../empleados/index.php" class="btn btn-info">👥 Ver empleados</a>
        </div>
        <div>
            <a href="../auth/logout.php" class="btn btn-danger">🚪 Cerrar sesión</a>
        </div>
    </div>

    <div class="descripcion-modulo">
        Los choferes se crean y editan exclusivamente en <strong>Empleados / Personal</strong> con el cargo <strong>CHOFER</strong>.
        En esta pantalla únicamente se asigna, cambia o libera el vehículo correspondiente.
    </div>

    <?php if ($mensaje === 'asignado'): ?>
        <div class="alerta-exito">✅ El vehículo fue asignado correctamente.</div>
    <?php elseif ($mensaje === 'liberado'): ?>
        <div class="alerta-exito">✅ El chofer quedó sin vehículo asignado y el vehículo anterior quedó libre.</div>
    <?php elseif ($mensaje === 'sin_cambios'): ?>
        <div class="alerta-info">ℹ️ La asignación seleccionada ya estaba registrada.</div>
    <?php elseif ($mensaje === 'gestion_empleados'): ?>
        <div class="alerta-info">ℹ️ Los datos personales del chofer se administran desde el módulo Empleados.</div>
    <?php endif; ?>

    <?php if ($error === 'vehiculo_ocupado'): ?>
        <div class="alerta-error">⚠️ El vehículo seleccionado acaba de ser asignado a otro chofer. Actualiza la página y selecciona otro.</div>
    <?php elseif ($error === 'chofer_invalido'): ?>
        <div class="alerta-error">⚠️ El empleado seleccionado no tiene actualmente el cargo CHOFER activo.</div>
    <?php elseif ($error === 'general'): ?>
        <div class="alerta-error">⚠️ No fue posible guardar la asignación. Revisa los datos e inténtalo nuevamente.</div>
    <?php endif; ?>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Chofer registrado en Empleados</th>
                    <th>Cédula</th>
                    <th>Teléfono</th>
                    <th>Cargo</th>
                    <th>Vehículo actual</th>
                    <th>Dirección del vehículo</th>
                    <th>Asignar / Cambiar vehículo</th>
                    <th>Empleado</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($choferes): ?>
                <?php foreach ($choferes as $chofer): ?>
                    <?php
                    $foto = $chofer['foto_empleado'] ?: $chofer['foto_chofer'];
                    $rutaFoto = null;
                    if ($foto && file_exists('../uploads/' . $foto)) {
                        $rutaFoto = '../uploads/' . $foto;
                    } elseif ($foto && file_exists('../uploads/choferes/' . $foto)) {
                        $rutaFoto = '../uploads/choferes/' . $foto;
                    }
                    ?>
                    <tr>
                        <td>
                            <?php if ($rutaFoto): ?>
                                <img class="foto-empleado" src="<?= htmlspecialchars($rutaFoto) ?>" alt="Foto del empleado">
                            <?php else: ?>
                                <div class="sin-foto" title="Sin fotografía">👤</div>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= htmlspecialchars(nombreCompleto($chofer)) ?></strong></td>
                        <td><?= htmlspecialchars($chofer['cedula'] ?? '') ?></td>
                        <td><?= htmlspecialchars($chofer['celular'] ?: ($chofer['telefono'] ?: '-')) ?></td>
                        <td><?= htmlspecialchars($chofer['cargo_nombre'] ?? 'CHOFER') ?></td>
                        <td class="vehiculo-actual">
                            <?php if (!empty($chofer['id_vehiculo'])): ?>
                                <strong><?= htmlspecialchars($chofer['placa']) ?></strong><br>
                                <?= htmlspecialchars(trim(($chofer['marca'] ?? '') . ' ' . ($chofer['modelo'] ?? ''))) ?>
                            <?php else: ?>
                                <span class="sin-asignar">Sin vehículo asignado</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($chofer['direccion_vehiculo'] ?? 'Sin dirección asignada') ?></td>
                        <td>
                            <form class="asignacion-form" action="guardar_asignacion.php" method="POST">
                                <input type="hidden" name="id_chofer" value="<?= (int)$chofer['id_chofer'] ?>">
                                <select name="id_vehiculo" aria-label="Vehículo para <?= htmlspecialchars(nombreCompleto($chofer)) ?>">
                                    <option value="">-- Dejar sin vehículo --</option>

                                    <?php if (!empty($chofer['id_vehiculo'])): ?>
                                        <option value="<?= (int)$chofer['id_vehiculo'] ?>" selected>
                                            <?= htmlspecialchars(descripcionVehiculo($chofer)) ?> (actual)
                                        </option>
                                    <?php endif; ?>

                                    <?php foreach ($vehiculosLibres as $vehiculo): ?>
                                        <option value="<?= (int)$vehiculo['id_vehiculo'] ?>">
                                            <?= htmlspecialchars(descripcionVehiculo($vehiculo)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-success">💾 Guardar</button>
                            </form>
                        </td>
                        <td>
                            <a class="btn btn-warning" href="../empleados/editar.php?id=<?= (int)$chofer['id_empleado'] ?>" title="Editar datos del empleado">✏️</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" style="text-align:center; padding:25px;">
                        No existen empleados activos con el cargo <strong>CHOFER</strong>.
                        Registra uno desde Empleados / Personal.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
