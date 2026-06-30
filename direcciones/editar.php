<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . '/../includes/conexion.php';

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    die("ID no válido");
}

/* =========================
   OBTENER REGISTRO
========================= */

$stmt = $pdo->prepare("
    SELECT * 
    FROM direcciones 
    WHERE id_direccion = ?
");

$stmt->execute([$id]);

$direccion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$direccion) {
    die("Dirección no encontrada");
}

/* =========================
   ACTUALIZAR
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = $_POST['nombre'] ?? '';
    $codigo = $_POST['codigo'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $estado = $_POST['estado'] ?? 'ACTIVO';

    try {

        $sql = "
            UPDATE direcciones
            SET nombre = :nombre,
                codigo = :codigo,
                descripcion = :descripcion,
                estado = :estado
            WHERE id_direccion = :id
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':nombre' => $nombre,
            ':codigo' => $codigo,
            ':descripcion' => $descripcion,
            ':estado' => $estado,
            ':id' => $id
        ]);

        header("Location: index.php?update=1");
        exit;

    } catch (PDOException $e) {

        die("Error al actualizar: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<title>
Editar Dirección
</title>

<link rel="stylesheet"
href="../assets/estilos.css">

</head>

<body>

<div class="container">

<h2>
✏️ Editar Dirección
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
value="<?= htmlspecialchars($direccion['codigo']) ?>"
required>

</div>


<div class="form-group">

<label>Nombre</label>

<input type="text"
name="nombre"
value="<?= htmlspecialchars($direccion['nombre']) ?>"
required>

</div>


<div class="form-group">

<label>Descripción</label>

<textarea name="descripcion"
rows="4">

<?= htmlspecialchars($direccion['descripcion']) ?>

</textarea>

</div>


<div class="form-group">

<label>Estado</label>

<select name="estado">

<option value="ACTIVO"
<?= $direccion['estado']=='ACTIVO'?'selected':'' ?>>

ACTIVO

</option>

<option value="INACTIVO"
<?= $direccion['estado']=='INACTIVO'?'selected':'' ?>>

INACTIVO

</option>

</select>

</div>


<div class="form-buttons">

<button type="submit"
class="btn btn-success">

💾 Actualizar

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