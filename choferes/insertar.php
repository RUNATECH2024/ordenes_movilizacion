<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../includes/conexion.php';

function nullIfEmpty($valor)
{
    return (!isset($valor) || trim($valor) === '') ? null : trim($valor);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    try {

        //==========================
        // SUBIR FOTO
        //==========================
        $foto = null;

        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {

            $carpeta = "../uploads/choferes/";

            if (!file_exists($carpeta)) {
                mkdir($carpeta, 0777, true);
            }

            $extension = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            $nombreFoto = uniqid("CHOFER_") . "." . $extension;

            move_uploaded_file(
                $_FILES['foto']['tmp_name'],
                $carpeta . $nombreFoto
            );

            $foto = $nombreFoto;
        }

        //==========================
        // INSERTAR
        //==========================
        $sql = "INSERT INTO choferes (
                    nombres,
                    apellidos,
                    cedula,
                    fecha_nacimiento,
                    direccion,
                    telefono,
                    correo,
                    numero_licencia,
                    fecha_emision_licencia,
                    fecha_caducidad_licencia,
                    cargo,
                    grupo_sanguineo,
                    contacto_emergencia,
                    telefono_emergencia,
                    codigo_empleado,
                    fecha_ingreso,
                    observaciones,
                    estado,
                    tipo_licencia,
                    id_direccion,
                    foto
                ) VALUES (
                    :nombres,
                    :apellidos,
                    :cedula,
                    :fecha_nacimiento,
                    :direccion,
                    :telefono,
                    :correo,
                    :numero_licencia,
                    :fecha_emision_licencia,
                    :fecha_caducidad_licencia,
                    :cargo,
                    :grupo_sanguineo,
                    :contacto_emergencia,
                    :telefono_emergencia,
                    :codigo_empleado,
                    :fecha_ingreso,
                    :observaciones,
                    :estado,
                    :tipo_licencia,
                    :id_direccion,
                    :foto
                )";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':nombres' => trim($_POST['nombres']),
            ':apellidos' => trim($_POST['apellidos']),
            ':cedula' => trim($_POST['cedula']),
            ':fecha_nacimiento' => nullIfEmpty($_POST['fecha_nacimiento']),
            ':direccion' => nullIfEmpty($_POST['direccion']),
            ':telefono' => nullIfEmpty($_POST['telefono']),
            ':correo' => nullIfEmpty($_POST['correo']),
            ':numero_licencia' => nullIfEmpty($_POST['numero_licencia']),
            ':fecha_emision_licencia' => nullIfEmpty($_POST['fecha_emision_licencia']),
            ':fecha_caducidad_licencia' => nullIfEmpty($_POST['fecha_caducidad_licencia']),
            ':cargo' => nullIfEmpty($_POST['cargo']),
            ':grupo_sanguineo' => nullIfEmpty($_POST['grupo_sanguineo']),
            ':contacto_emergencia' => nullIfEmpty($_POST['contacto_emergencia']),
            ':telefono_emergencia' => nullIfEmpty($_POST['telefono_emergencia']),
            ':codigo_empleado' => nullIfEmpty($_POST['codigo_empleado']),
            ':fecha_ingreso' => nullIfEmpty($_POST['fecha_ingreso']),
            ':observaciones' => nullIfEmpty($_POST['observaciones']),
            ':estado' => $_POST['estado'],
            ':tipo_licencia' => $_POST['tipo_licencia'],
            // Convertimos a entero si viene un ID, de lo contrario pasa como null
            ':id_direccion' => !empty($_POST['id_direccion']) ? (int)$_POST['id_direccion'] : null,
            ':foto' => $foto
        ]);

        header("Location: index.php?ok=1");
        exit;

    } catch (PDOException $e) {
        echo "<pre>";
        echo "ERROR SQL\n\n";
        echo $e->getMessage();
        echo "\n\n";
        print_r($_POST);
        echo "</pre>";
    }
}
?>