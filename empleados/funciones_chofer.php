<?php
/**
 * Funciones de apoyo para unificar el rol Chofer con el módulo Empleados.
 * La tabla choferes se conserva como tabla técnica por compatibilidad con
 * vehículos y órdenes de movilización, pero sus datos se generan desde empleados.
 */

function normalizarTextoCargo($texto)
{
    $texto = strtr(trim((string)$texto), [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n',
        'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N'
    ]);
    return strtoupper($texto);
}

function esCargoChofer($nombreCargo)
{
    $nombre = normalizarTextoCargo($nombreCargo);
    return strpos($nombre, 'CHOFER') !== false || strpos($nombre, 'CONDUCTOR') !== false;
}

function obtenerCargoPorId(PDO $pdo, $idCargo)
{
    $stmt = $pdo->prepare("SELECT id_cargo, id_jefatura, nombre FROM cargos WHERE id_cargo = ? AND UPPER(COALESCE(estado, 'ACTIVO')) = 'ACTIVO'");
    $stmt->execute([(int)$idCargo]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function buscarIdChoferDeEmpleado(PDO $pdo, $idEmpleado, $cedula = null)
{
    $stmt = $pdo->prepare(
        "SELECT id_chofer
         FROM choferes
         WHERE id_empleado = :id_empleado
            OR (:cedula IS NOT NULL AND cedula = :cedula)
         ORDER BY CASE WHEN id_empleado = :id_empleado THEN 0 ELSE 1 END, id_chofer
         LIMIT 1"
    );
    $stmt->execute([
        ':id_empleado' => (int)$idEmpleado,
        ':cedula' => $cedula !== null && trim((string)$cedula) !== '' ? trim((string)$cedula) : null
    ]);

    $id = $stmt->fetchColumn();
    return $id ? (int)$id : null;
}

function sincronizarChoferDesdeEmpleado(PDO $pdo, $idEmpleado, array $datos, $estado = 'ACTIVO')
{
    $cedula = trim((string)($datos['cedula'] ?? ''));
    $nombres = trim((string)($datos['primer_nombre'] ?? '') . ' ' . (string)($datos['segundo_nombre'] ?? ''));
    $apellidos = trim((string)($datos['primer_apellido'] ?? '') . ' ' . (string)($datos['segundo_apellido'] ?? ''));
    $estado = strtoupper((string)$estado) === 'ACTIVO' ? 'ACTIVO' : 'INACTIVO';

    $idChofer = buscarIdChoferDeEmpleado($pdo, $idEmpleado, $cedula);

    $valores = [
        ':id_empleado' => (int)$idEmpleado,
        ':cedula' => $cedula,
        ':nombres' => $nombres,
        ':apellidos' => $apellidos,
        ':fecha_nacimiento' => !empty($datos['fecha_nacimiento']) ? $datos['fecha_nacimiento'] : null,
        ':telefono' => !empty($datos['celular']) ? trim((string)$datos['celular']) : (!empty($datos['telefono']) ? trim((string)$datos['telefono']) : null),
        ':correo' => !empty($datos['correo_personal']) ? trim((string)$datos['correo_personal']) : null,
        ':cargo' => 'CHOFER',
        ':grupo_sanguineo' => !empty($datos['tipo_sangre_nombre']) ? trim((string)$datos['tipo_sangre_nombre']) : null,
        ':fecha_ingreso' => !empty($datos['fecha_ingreso']) ? $datos['fecha_ingreso'] : date('Y-m-d'),
        ':estado' => $estado,
        ':foto' => !empty($datos['foto']) ? trim((string)$datos['foto']) : null,
    ];

    if ($idChofer) {
        $sql = "UPDATE choferes SET
                    id_empleado = :id_empleado,
                    cedula = :cedula,
                    nombres = :nombres,
                    apellidos = :apellidos,
                    fecha_nacimiento = :fecha_nacimiento,
                    telefono = :telefono,
                    correo = :correo,
                    cargo = :cargo,
                    grupo_sanguineo = :grupo_sanguineo,
                    fecha_ingreso = :fecha_ingreso,
                    estado = :estado,
                    foto = COALESCE(:foto, foto)
                WHERE id_chofer = :id_chofer";
        $valores[':id_chofer'] = $idChofer;
        $pdo->prepare($sql)->execute($valores);
    } else {
        $sql = "INSERT INTO choferes (
                    id_empleado, cedula, nombres, apellidos, fecha_nacimiento,
                    telefono, correo, cargo, grupo_sanguineo, fecha_ingreso,
                    estado, foto
                ) VALUES (
                    :id_empleado, :cedula, :nombres, :apellidos, :fecha_nacimiento,
                    :telefono, :correo, :cargo, :grupo_sanguineo, :fecha_ingreso,
                    :estado, :foto
                ) RETURNING id_chofer";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($valores);
        $idChofer = (int)$stmt->fetchColumn();
    }

    if ($estado !== 'ACTIVO' && $idChofer) {
        $pdo->prepare("UPDATE vehiculos SET id_chofer = NULL WHERE id_chofer = ?")->execute([$idChofer]);
    }

    return $idChofer;
}

function desactivarChoferDeEmpleado(PDO $pdo, $idEmpleado, $cedula = null)
{
    $idChofer = buscarIdChoferDeEmpleado($pdo, $idEmpleado, $cedula);
    if (!$idChofer) {
        return;
    }

    $pdo->prepare("UPDATE vehiculos SET id_chofer = NULL WHERE id_chofer = ?")->execute([$idChofer]);
    $pdo->prepare("UPDATE choferes SET estado = 'INACTIVO' WHERE id_chofer = ?")->execute([$idChofer]);
}
