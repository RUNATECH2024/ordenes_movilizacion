<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../includes/conexion.php';

function nullIfEmpty($valor) {
    return (!isset($valor) || trim($valor) === '') ? null : $valor;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = $_POST['id'] ?? null;

    if (!$id || !is_numeric($id)) {
        die("ID de chofer no válido.");
    }

    try {

        $stmt = $pdo->prepare("
            UPDATE choferes SET
                nombres = :nombres,
                apellidos = :apellidos,
                cedula = :cedula,
                fecha_nacimiento = :fecha_nacimiento,
                direccion = :direccion,
                telefono = :telefono,
                correo = :correo,
                tipo_licencia = :tipo_licencia,
                numero_licencia = :numero_licencia,
                fecha_emision_licencia = :fecha_emision_licencia,
                fecha_caducidad_licencia = :fecha_caducidad_licencia,
                cargo = :cargo,
                departamento = :departamento,
                grupo_sanguineo = :grupo_sanguineo,
                contacto_emergencia = :contacto_emergencia,
                telefono_emergencia = :telefono_emergencia,
                codigo_empleado = :codigo_empleado,
                fecha_ingreso = :fecha_ingreso,
                observaciones = :observaciones,
                estado = :estado
            WHERE id_chofer = :id
        ");

        $stmt->execute([
            ':id' => $id,

            ':nombres' => trim($_POST['nombres'] ?? ''),
            ':apellidos' => trim($_POST['apellidos'] ?? ''),
            ':cedula' => trim($_POST['cedula'] ?? ''),

            ':fecha_nacimiento' => nullIfEmpty($_POST['fecha_nacimiento'] ?? ''),
            ':direccion' => trim($_POST['direccion'] ?? ''),
            ':telefono' => trim($_POST['telefono'] ?? ''),
            ':correo' => trim($_POST['correo'] ?? ''),

            ':tipo_licencia' => trim($_POST['tipo_licencia'] ?? ''),
            ':numero_licencia' => trim($_POST['numero_licencia'] ?? ''),

            ':fecha_emision_licencia' => nullIfEmpty($_POST['fecha_emision_licencia'] ?? ''),
            ':fecha_caducidad_licencia' => nullIfEmpty($_POST['fecha_caducidad_licencia'] ?? ''),

            ':cargo' => trim($_POST['cargo'] ?? ''),
            ':departamento' => trim($_POST['departamento'] ?? ''),
            ':grupo_sanguineo' => trim($_POST['grupo_sanguineo'] ?? ''),

            ':contacto_emergencia' => trim($_POST['contacto_emergencia'] ?? ''),
            ':telefono_emergencia' => trim($_POST['telefono_emergencia'] ?? ''),

            ':codigo_empleado' => trim($_POST['codigo_empleado'] ?? ''),
            ':fecha_ingreso' => nullIfEmpty($_POST['fecha_ingreso'] ?? ''),

            ':observaciones' => trim($_POST['observaciones'] ?? ''),
            ':estado' => trim($_POST['estado'] ?? 'ACTIVO')
        ]);

        header("Location: index.php?actualizado=1");
        exit;

    } catch (PDOException $e) {

        echo "<h3>Error al actualizar chofer</h3>";
        echo "<pre>";
        echo $e->getMessage();
        echo "\n\nDATOS RECIBIDOS:\n";
        print_r($_POST);
        echo "</pre>";
        exit;
    }
}

header("Location: index.php");
exit;
?>