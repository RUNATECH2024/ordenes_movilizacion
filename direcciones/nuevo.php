<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . '/../includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = $_POST['nombre'] ?? '';
    $codigo = $_POST['codigo'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';

    try {

        $sql = "INSERT INTO direcciones 
                (nombre, codigo, descripcion, estado)
                VALUES 
                (:nombre, :codigo, :descripcion, 'ACTIVO')";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':nombre' => $nombre,
            ':codigo' => $codigo,
            ':descripcion' => $descripcion
        ]);

        header("Location: index.php?success=1");
        exit;

    } catch (PDOException $e) {

        die("Error al guardar dirección: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<title>
Nueva Dirección
</title>

<link rel="stylesheet"
href="../assets/estilos.css">

</head>

<body>

<div class="container">

<h2>
🏢 Nueva Dirección
</h2>


<div class="menu">

<a href="index.php"
class="btn btn-primary">

← Volver

</a>

</div>

<hr>


<form method="POST"
class="form-dos-columnas">


<div class="form-group">

<label>Código</label>

<input type="text"
name="codigo"
placeholder="Ej: DA"
required>

</div>


<div class="form-group">

<label>Nombre</label>

<input type="text"
name="nombre"
placeholder="Ej: Dirección Administrativa"
required>

</div>


<div class="form-group">

<label>Descripción</label>

<textarea name="descripcion"
rows="4"
placeholder="Descripción de la dirección">

</textarea>

</div>


<div class="form-buttons">

<button type="submit"
class="btn btn-success">

💾 Guardar

</button>

<a href="index.php"
class="btn btn-danger">

Cancelar

</a>

</div>


</form>

</div>

</body>

</html>