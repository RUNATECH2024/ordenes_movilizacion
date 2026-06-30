<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = $_POST['id'] ?? null;

    if (!$id || !is_numeric($id)) {
        die("ID inválido");
    }

    // Datos del formulario
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

    // Relaciones (Chofer y Dirección)
    $id_chofer = !empty($_POST['id_chofer']) ? (int)$_POST['id_chofer'] : null;
    $id_direccion = !empty($_POST['id_direccion']) ? (int)$_POST['id_direccion'] : null;

    //==========================
    // OBTENER FOTO ACTUAL
    //==========================
    $stmt = $pdo->prepare("SELECT foto_vehiculo FROM vehiculos WHERE id_vehiculo = ?");
    $stmt->execute([$id]);
    $vehiculo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $foto_vehiculo = $vehiculo['foto_vehiculo'] ?? null;

    //==========================
    // PROCESAR NUEVA IMAGEN
    //==========================
    if (isset($_FILES['foto_vehiculo']) && $_FILES['foto_vehiculo']['error'] == 0) {

        $carpeta = "../uploads/vehiculos/";

        if (!file_exists($carpeta)) {
            mkdir($carpeta, 0777, true);
        }

        // Eliminar foto anterior si existe en el disco
        if (!empty($foto_vehiculo)) {
            // Buscamos tanto si se guardó la ruta completa como solo el nombre
            $ruta_antigua = strpos($foto_vehiculo, 'uploads/') === 0 ? "../" . $foto_vehiculo : $carpeta . $foto_vehiculo;
            if (file_exists($ruta_antigua)) {
                unlink($ruta_antigua);
            }
        }

        $extension = strtolower(pathinfo($_FILES['foto_vehiculo']['name'], PATHINFO_EXTENSION));
        $nombreImagen = time() . '_' . uniqid("VEHICULO_") . '.' . $extension;

        if (move_uploaded_file($_FILES['foto_vehiculo']['tmp_name'], $carpeta . $nombreImagen)) {
            $foto_vehiculo = $nombreImagen;
        }
    }

    //==========================
    // EJECUTAR UPDATE
    //==========================
    try {
        $sql = "UPDATE vehiculos SET
                    codigo_institucional = ?,
                    placa = ?,
                    matricula = ?,
                    chasis = ?,
                    motor = ?,
                    marca = ?,
                    modelo = ?,
                    tipo = ?,
                    unidad = ?,
                    color = ?,
                    anio = ?,
                    descripcion_vehiculo = ?,
                    id_chofer = ?,
                    id_direccion = ?,
                    foto_vehiculo = ?
                WHERE id_vehiculo = ?";

        $stmt = $pdo->prepare($sql);
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
            $id_direccion,
            $foto_vehiculo,
            $id
        ]);

        header("Location: index.php?update=1");
        exit();

    } catch (PDOException $e) {
        echo "<h3>Error al actualizar el vehículo</h3>";
        echo "<pre>" . $e->getMessage() . "</pre>";
        exit;
    }

} else {
    echo "Acceso no permitido";
}