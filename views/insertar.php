<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../includes/conexion.php";

function nullIfEmpty($valor)
{
    return (isset($valor) && trim($valor) !== "") ? trim($valor) : null;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    //=========================
    // SUBIR FOTO
    //=========================

    $nombreFoto = null;

    if (isset($_FILES["foto"]) && $_FILES["foto"]["error"] == 0) {

        $permitidos = ['jpg','jpeg','png','webp'];

        $extension = strtolower(pathinfo($_FILES["foto"]["name"], PATHINFO_EXTENSION));

        if (in_array($extension,$permitidos)) {

            if(!is_dir("../uploads/choferes")){
                mkdir("../uploads/choferes",0777,true);
            }

            $nombreFoto = uniqid("chofer_").".".$extension;

            move_uploaded_file(
                $_FILES["foto"]["tmp_name"],
                "../uploads/choferes/".$nombreFoto
            );
        }
    }

    try {

        $sql = "INSERT INTO choferes(

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
            departamento,
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
            :departamento,
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
            ':direccion' => trim($_POST['direccion']),
            ':telefono' => trim($_POST['telefono']),
            ':correo' => trim($_POST['correo']),

            ':numero_licencia' => trim($_POST['numero_licencia']),
            ':fecha_emision_licencia' => nullIfEmpty($_POST['fecha_emision_licencia']),
            ':fecha_caducidad_licencia' => nullIfEmpty($_POST['fecha_caducidad_licencia']),

            ':cargo' => trim($_POST['cargo']),
            ':departamento' => trim($_POST['departamento']),
            ':grupo_sanguineo' => trim($_POST['grupo_sanguineo']),

            ':contacto_emergencia' => trim($_POST['contacto_emergencia']),
            ':telefono_emergencia' => trim($_POST['telefono_emergencia']),

            ':codigo_empleado' => trim($_POST['codigo_empleado']),
            ':fecha_ingreso' => nullIfEmpty($_POST['fecha_ingreso']),

            ':observaciones' => trim($_POST['observaciones']),

            ':estado' => $_POST['estado'],

            ':tipo_licencia' => $_POST['tipo_licencia'],

            ':id_direccion' => !empty($_POST['id_direccion']) ? $_POST['id_direccion'] : null,

            ':foto' => $nombreFoto

        ]);

        header("Location:index.php?ok=1");
        exit;

    } catch(PDOException $e){

        echo "<h3>Error al guardar el chofer</h3>";

        echo "<pre>";
        echo $e->getMessage();
        echo "</pre>";

    }

}else{

    header("Location:nuevo.php");
    exit;

}