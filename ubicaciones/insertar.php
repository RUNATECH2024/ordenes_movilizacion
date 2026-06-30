<?php
require_once '../includes/conexion.php';

// Función reutilizable para insertar o recuperar ID
function obtenerOInsertar($conexion, $tabla, $campos, $valores) {
    $idCampos = [
        'provincias' => 'id_provincia',
        'ciudades' => 'id_ciudad',
        'parroquias' => 'id_parroquia',
        'recintos' => 'id_recinto',
    ];
    $id_columna = $idCampos[$tabla] ?? "id_$tabla";

    $condiciones = array_map(fn($c) => "$c = ?", $campos);
    $sqlBuscar = "SELECT $id_columna FROM $tabla WHERE " . implode(' AND ', $condiciones);
    $stmt = $conexion->prepare($sqlBuscar);
    $stmt->execute($valores);
    $resultado = $stmt->fetchColumn();

    if ($resultado) {
        return $resultado;
    } else {
        $camposLista = implode(', ', $campos);
        $placeholders = implode(', ', array_fill(0, count($valores), '?'));
        $sqlInsertar = "INSERT INTO $tabla ($camposLista) VALUES ($placeholders) RETURNING $id_columna";
        $stmt = $conexion->prepare($sqlInsertar);
        $stmt->execute($valores);
        return $stmt->fetchColumn();
    }
}

// Función auxiliar para obtener nombre por ID
function obtenerNombre($conexion, $tabla, $columna_id, $valor_id) {
    $stmt = $conexion->prepare("SELECT nombre FROM $tabla WHERE $columna_id = ?");
    $stmt->execute([$valor_id]);
    return $stmt->fetchColumn();
}

try {
    // 1. Provincia
    if ($_POST['id_provincia'] === 'nuevo') {
        $id_provincia = obtenerOInsertar($conexion, 'provincias', ['nombre'], [$_POST['nueva_provincia']]);
    } else {
        $id_provincia = $_POST['id_provincia'];
    }

    // 2. Ciudad
    if ($_POST['id_ciudad'] === 'nuevo') {
        $id_ciudad = obtenerOInsertar($conexion, 'ciudades', ['nombre', 'id_provincia'], [$_POST['nueva_ciudad'], $id_provincia]);
    } else {
        $id_ciudad = $_POST['id_ciudad'];
    }

    // 3. Parroquia
    if ($_POST['id_parroquia'] === 'nuevo') {
        $id_parroquia = obtenerOInsertar($conexion, 'parroquias', ['nombre', 'id_ciudad'], [$_POST['nueva_parroquia'], $id_ciudad]);
    } else {
        $id_parroquia = $_POST['id_parroquia'];
    }

    // 4. Recinto
    if ($_POST['id_recinto'] === 'nuevo') {
        $id_recinto = obtenerOInsertar($conexion, 'recintos', ['nombre', 'id_parroquia'], [$_POST['nuevo_recinto'], $id_parroquia]);
    } else {
        $id_recinto = $_POST['id_recinto'];
    }

    // 5. Insertar ubicación
    $referencia = $_POST['referencia'];
    $stmt = $conexion->prepare("INSERT INTO ubicaciones (id_recinto, referencia) VALUES (?, ?)");
    $stmt->execute([$id_recinto, $referencia]);

    // Obtener nombres para mostrar mensaje
    $nombreProvincia = obtenerNombre($conexion, 'provincias', 'id_provincia', $id_provincia);
    $nombreCiudad = obtenerNombre($conexion, 'ciudades', 'id_ciudad', $id_ciudad);
    $nombreParroquia = obtenerNombre($conexion, 'parroquias', 'id_parroquia', $id_parroquia);
    $nombreRecinto = obtenerNombre($conexion, 'recintos', 'id_recinto', $id_recinto);

    // Redireccionar con mensaje
    header("Location: nuevo.php?exito=1&provincia=" . urlencode($nombreProvincia) .
        "&ciudad=" . urlencode($nombreCiudad) .
        "&parroquia=" . urlencode($nombreParroquia) .
        "&recinto=" . urlencode($nombreRecinto) .
        "&referencia=" . urlencode($referencia));
    exit;

} catch (PDOException $e) {
    echo "❌ Error al insertar ubicación: " . $e->getMessage();
}
