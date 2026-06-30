<?php
require_once '../includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = $_POST['id'] ?? null;

    if (!$id || !is_numeric($id)) {
        die("ID inválido");
    }

    // Datos del formulario
    $codigo_institucional = $_POST['codigo_institucional'] ?? '';
    $placa = $_POST['placa'] ?? '';
    $matricula = $_POST['matricula'] ?? '';
    $chasis = $_POST['chasis'] ?? '';
    $motor = $_POST['motor'] ?? '';
    $marca = $_POST['marca'] ?? '';
    $modelo = $_POST['modelo'] ?? '';
    $tipo = $_POST['tipo'] ?? '';
    $unidad = $_POST['unidad'] ?? '';
    $color = $_POST['color'] ?? '';
    $anio = !empty($_POST['anio']) ? $_POST['anio'] : null;
    $descripcion_vehiculo = $_POST['descripcion_vehiculo'] ?? '';

    $id_chofer =
        !empty($_POST['id_chofer'])
        ? $_POST['id_chofer']
        : null;


    // Obtener foto actual
    $stmt = $pdo->prepare("
        SELECT foto_vehiculo
        FROM vehiculos
        WHERE id_vehiculo=?
    ");

    $stmt->execute([$id]);

    $vehiculo = $stmt->fetch(PDO::FETCH_ASSOC);

    $foto_vehiculo = $vehiculo['foto_vehiculo'];


    // Si sube nueva imagen
    if (
        isset($_FILES['foto_vehiculo']) &&
        $_FILES['foto_vehiculo']['error']==0
    ){

        $carpeta="../uploads/vehiculos/";

        if(!file_exists($carpeta)){
            mkdir($carpeta,0777,true);
        }

        // eliminar foto anterior
        if(
            !empty($foto_vehiculo)
            &&
            file_exists("../".$foto_vehiculo)
        ){
            unlink("../".$foto_vehiculo);
        }

        $extension = pathinfo(
            $_FILES['foto_vehiculo']['name'],
            PATHINFO_EXTENSION
        );

        $nombreImagen=
        time().'_'.
        uniqid().
        '.'.$extension;

        move_uploaded_file(
            $_FILES['foto_vehiculo']['tmp_name'],
            $carpeta.$nombreImagen
        );

        $foto_vehiculo=
        'uploads/vehiculos/'.
        $nombreImagen;
    }


    try{

        $sql="
        UPDATE vehiculos
        SET

        codigo_institucional=?,
        placa=?,
        matricula=?,
        chasis=?,
        motor=?,
        marca=?,
        modelo=?,
        tipo=?,
        unidad=?,
        color=?,
        anio=?,
        descripcion_vehiculo=?,
        id_chofer=?,
        foto_vehiculo=?

        WHERE id_vehiculo=?

        ";

        $stmt=$pdo->prepare($sql);

        $stmt->execute([

            $codigo_institucional,
            $placa,
            $matricula,
            $chasis,
            $motor,
            $marca,
            $modelo,
            $tipo,
            $unidad,
            $color,
            $anio,
            $descripcion_vehiculo,
            $id_chofer,
            $foto_vehiculo,
            $id

        ]);

        header("Location:index.php?update=1");
        exit();

    }

    catch(PDOException $e){

        echo "Error: ".$e->getMessage();

    }

}else{

    echo "Acceso no permitido";

}
?>