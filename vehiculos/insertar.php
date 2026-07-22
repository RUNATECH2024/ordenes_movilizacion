<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . '/../includes/conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Datos formulario
    $codigo_institucional = !empty($_POST['codigo_institucional']) ? trim($_POST['codigo_institucional']) : null;
    $placa = !empty($_POST['placa']) ? trim($_POST['placa']) : null;
    $matricula = !empty($_POST['matricula']) ? trim($_POST['matricula']) : null;
    $chasis = !empty($_POST['chasis']) ? trim($_POST['chasis']) : null;
    $motor = !empty($_POST['motor']) ? trim($_POST['motor']) : null;
    $marca = !empty($_POST['marca']) ? trim($_POST['marca']) : null;
    $modelo = !empty($_POST['modelo']) ? trim($_POST['modelo']) : null;
    $tipo = !empty($_POST['tipo']) ? trim($_POST['tipo']) : null;
    $unidad = !empty($_POST['unidad']) ? trim($_POST['unidad']) : null;
    $color = !empty($_POST['color']) ? trim($_POST['color']) : null;
    $anio = !empty($_POST['anio']) ? (int)$_POST['anio'] : null;
    $descripcion_vehiculo = !empty($_POST['descripcion_vehiculo']) ? trim($_POST['descripcion_vehiculo']) : null;

    // Relación con la Dirección Institucional.
    // El chofer ya no se asigna desde este módulo; se asigna desde Choferes.
    $id_direccion = !empty($_POST['id_direccion']) ? (int)$_POST['id_direccion'] : null;

    //==========================
    // SUBIR FOTO VEHÍCULO
    //==========================
    $foto_vehiculo = null;

    if (isset($_FILES['foto_vehiculo']) && $_FILES['foto_vehiculo']['error'] == 0) {

        $carpeta = "../uploads/vehiculos/";

        // Crear carpeta si no existe
        if (!file_exists($carpeta)) {
            mkdir($carpeta, 0777, true);
        }

        $extension = strtolower(pathinfo($_FILES['foto_vehiculo']['name'], PATHINFO_EXTENSION));
        $nombreFoto = time() . "_" . uniqid("VEHICULO_") . "." . $extension;
        $rutaCompleta = $carpeta . $nombreFoto;

        if (move_uploaded_file($_FILES['foto_vehiculo']['tmp_name'], $rutaCompleta)) {
            // Guardamos solo el nombre del archivo para estandarizar con choferes
            $foto_vehiculo = $nombreFoto;
        }
    }

    try {

        $sql = "INSERT INTO vehiculos (
                    codigo_institucional,
                    placa,
                    matricula,
                    chasis,
                    motor,
                    marca,
                    modelo,
                    tipo,
                    unidad,
                    color,
                    anio,
                    descripcion_vehiculo,
                    id_direccion,
                    foto_vehiculo
                ) VALUES (
                    :codigo_institucional,
                    :placa,
                    :matricula,
                    :chasis,
                    :motor,
                    :marca,
                    :modelo,
                    :tipo,
                    :unidad,
                    :color,
                    :anio,
                    :descripcion_vehiculo,
                    :id_direccion,
                    :foto_vehiculo
                )";

        $stmt = $pdo->prepare($sql);

        // Usamos un array asociativo directo en el execute para un código más limpio y rápido
        $stmt->execute([
            ':codigo_institucional' => $codigo_institucional,
            ':placa' => $placa,
            ':matricula' => $matricula,
            ':chasis' => $chasis,
            ':motor' => $motor,
            ':marca' => $marca,
            ':modelo' => $modelo,
            ':tipo' => $tipo,
            ':unidad' => $unidad,
            ':color' => $color,
            ':anio' => $anio,
            ':descripcion_vehiculo' => $descripcion_vehiculo,
            ':id_direccion' => $id_direccion,
            ':foto_vehiculo' => $foto_vehiculo
        ]);

        header("Location: index.php?success=1");
        exit();

    } catch (PDOException $e) {
        echo "<h3>Error al guardar el vehículo</h3>";
        echo "<pre>";
        echo $e->getMessage();
        echo "</pre>";
        exit;
    }
}

header("Location: index.php");
exit;