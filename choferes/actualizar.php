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

    $id = $_POST['id'] ?? null;

    if (!$id || !is_numeric($id)) {
        die("ID de chofer no válido.");
    }

    try {

        //==========================
        // OBTENER FOTO ACTUAL
        //==========================
        $consulta = $pdo->prepare("SELECT foto FROM choferes WHERE id_chofer = ?");
        $consulta->execute([$id]);
        $actual = $consulta->fetch(PDO::FETCH_ASSOC);

        $foto = $actual['foto'] ?? null;

        //==========================
        // SUBIR NUEVA FOTO (SI SE SELECCIONÓ UNA)
        //==========================
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {

            $carpeta = "../uploads/choferes/";

            if (!file_exists($carpeta)) {
                mkdir($carpeta, 0777, true);
            }

            // Eliminar la foto anterior del servidor si existe
            if (!empty($foto) && file_exists($carpeta . $foto)) {
                unlink($carpeta . $foto);
            }

            $extension = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            $foto = uniqid("CHOFER_") . "." . $extension;

            move_uploaded_file(
                $_FILES['foto']['tmp_name'],
                $carpeta . $foto
            );
        }

        //==========================
        // ACTUALIZAR REGISTRO
        //==========================
        $sql = "UPDATE choferes SET
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
                    grupo_sanguineo = :grupo_sanguineo,
                    contacto_emergencia = :contacto_emergencia,
                    telefono_emergencia = :telefono_emergencia,
                    codigo_empleado = :codigo_empleado,
                    fecha_ingreso = :fecha_ingreso,
                    observaciones = :observaciones,
                    estado = :estado,
                    id_direccion = :id_direccion,
                    foto = :foto
                WHERE id_chofer = :id";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':id' => $id,
            ':nombres' => trim($_POST['nombres']),
            ':apellidos' => trim($_POST['apellidos']),
            ':cedula' => trim($_POST['cedula']),
            ':fecha_nacimiento' => nullIfEmpty($_POST['fecha_nacimiento']),
            ':direccion' => nullIfEmpty($_POST['direccion']),
            ':telefono' => nullIfEmpty($_POST['telefono']),
            ':correo' => nullIfEmpty($_POST['correo']),
            ':tipo_licencia' => nullIfEmpty($_POST['tipo_licencia']),
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
            // Convertimos la dirección institucional enviada a entero o null si no se seleccionó ninguna
            ':id_direccion' => !empty($_POST['id_direccion']) ? (int)$_POST['id_direccion'] : null,
            ':foto' => $foto
        ]);

        header("Location: index.php?actualizado=1");
        exit;

    } catch (PDOException $e) {
        echo "<h3>Error al actualizar</h3>";
        echo "<pre>";
        echo $e->getMessage();
        echo "</pre>";
        exit;
    }
}

header("Location: index.php");
exit;