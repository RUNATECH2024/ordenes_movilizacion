<?php
require_once __DIR__ . '/../includes/conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Datos formulario
    $codigo_institucional = $_POST['codigo_institucional'];
    $placa = $_POST['placa'];
    $matricula = $_POST['matricula'];
    $chasis = $_POST['chasis'];
    $motor = $_POST['motor'];
    $marca = $_POST['marca'];
    $modelo = $_POST['modelo'];
    $tipo = $_POST['tipo'];
    $unidad = $_POST['unidad'];
    $color = $_POST['color'];
    $anio = !empty($_POST['anio']) ? $_POST['anio'] : null;
    $descripcion_vehiculo = $_POST['descripcion_vehiculo'];

    $id_chofer = !empty($_POST['id_chofer'])
        ? $_POST['id_chofer']
        : null;

    // Foto
    $foto_vehiculo = null;

    if (
        isset($_FILES['foto_vehiculo']) &&
        $_FILES['foto_vehiculo']['error'] == 0
    ) {

        $carpeta = "../uploads/vehiculos/";

        // Crear carpeta si no existe
        if (!file_exists($carpeta)) {
            mkdir($carpeta, 0777, true);
        }

        $extension = pathinfo(
            $_FILES['foto_vehiculo']['name'],
            PATHINFO_EXTENSION
        );

        $nombreFoto =
            time() . "_" .
            uniqid() . "." .
            $extension;

        $rutaCompleta = $carpeta . $nombreFoto;

        move_uploaded_file(
            $_FILES['foto_vehiculo']['tmp_name'],
            $rutaCompleta
        );

        // Ruta que se guarda en BD
        $foto_vehiculo =
            "uploads/vehiculos/" .
            $nombreFoto;
    }

    try {

        $sql = "INSERT INTO vehiculos
        (
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
            id_chofer,
            foto_vehiculo
        )
        VALUES
        (
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
            :id_chofer,
            :foto_vehiculo
        )";

        $stmt = $pdo->prepare($sql);

        $stmt->bindParam(
            ':codigo_institucional',
            $codigo_institucional
        );

        $stmt->bindParam(
            ':placa',
            $placa
        );

        $stmt->bindParam(
            ':matricula',
            $matricula
        );

        $stmt->bindParam(
            ':chasis',
            $chasis
        );

        $stmt->bindParam(
            ':motor',
            $motor
        );

        $stmt->bindParam(
            ':marca',
            $marca
        );

        $stmt->bindParam(
            ':modelo',
            $modelo
        );

        $stmt->bindParam(
            ':tipo',
            $tipo
        );

        $stmt->bindParam(
            ':unidad',
            $unidad
        );

        $stmt->bindParam(
            ':color',
            $color
        );

        $stmt->bindParam(
            ':anio',
            $anio
        );

        $stmt->bindParam(
            ':descripcion_vehiculo',
            $descripcion_vehiculo
        );

        $stmt->bindParam(
            ':id_chofer',
            $id_chofer
        );

        $stmt->bindParam(
            ':foto_vehiculo',
            $foto_vehiculo
        );

        $stmt->execute();

        header("Location:index.php?success=1");
        exit();

    } catch(PDOException $e){

        echo "Error al guardar: "
             . $e->getMessage();
    }

}
?>