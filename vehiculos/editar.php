<?php
require_once '../includes/conexion.php';

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    die("ID de vehículo no válido.");
}

// Obtener vehículo
$stmt = $pdo->prepare("
SELECT * 
FROM vehiculos
WHERE id_vehiculo=?
");

$stmt->execute([$id]);

$vehiculo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vehiculo) {
    die("Vehículo no encontrado.");
}

// Obtener choferes
$stmtChoferes = $pdo->query("
SELECT id_chofer,nombres,apellidos
FROM choferes
ORDER BY nombres
");

$choferes = $stmtChoferes->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>
<meta charset="UTF-8">
<title>Editar Vehículo</title>
<link rel="stylesheet" href="../assets/estilos.css">
</head>

<body>

<div class="container">

<h2>✏️ Editar Vehículo</h2>

<form
action="actualizar.php"
method="POST"
enctype="multipart/form-data"
class="form-dos-columnas"
>

<input
type="hidden"
name="id"
value="<?= $vehiculo['id_vehiculo'] ?>"
>

<div class="form-group">
<label>Código Institucional:</label>
<input
type="text"
name="codigo_institucional"
value="<?= htmlspecialchars($vehiculo['codigo_institucional']) ?>"
required>
</div>

<div class="form-group">
<label>Placa:</label>
<input
type="text"
name="placa"
value="<?= htmlspecialchars($vehiculo['placa']) ?>"
required>
</div>

<div class="form-group">
<label>Matrícula:</label>
<input
type="text"
name="matricula"
value="<?= htmlspecialchars($vehiculo['matricula']) ?>"
required>
</div>

<div class="form-group">
<label>Chasis:</label>
<input
type="text"
name="chasis"
value="<?= htmlspecialchars($vehiculo['chasis']) ?>"
required>
</div>

<div class="form-group">
<label>Motor:</label>
<input
type="text"
name="motor"
value="<?= htmlspecialchars($vehiculo['motor']) ?>"
required>
</div>

<div class="form-group">
<label>Marca:</label>
<input
type="text"
name="marca"
value="<?= htmlspecialchars($vehiculo['marca']) ?>">
</div>

<div class="form-group">
<label>Modelo:</label>
<input
type="text"
name="modelo"
value="<?= htmlspecialchars($vehiculo['modelo']) ?>">
</div>

<div class="form-group">
<label>Tipo:</label>
<input
type="text"
name="tipo"
value="<?= htmlspecialchars($vehiculo['tipo']) ?>">
</div>

<div class="form-group">
<label>Unidad:</label>
<input
type="text"
name="unidad"
value="<?= htmlspecialchars($vehiculo['unidad']) ?>">
</div>

<div class="form-group">
<label>Color:</label>
<input
type="text"
name="color"
value="<?= htmlspecialchars($vehiculo['color']) ?>">
</div>

<div class="form-group">
<label>Año:</label>
<input
type="number"
name="anio"
value="<?= htmlspecialchars($vehiculo['anio']) ?>"
min="1900"
max="<?= date('Y') ?>">
</div>

<div class="form-group">
<label>Chofer:</label>

<select name="id_chofer">

<option value="">Seleccione</option>

<?php foreach($choferes as $c): ?>

<option
value="<?= $c['id_chofer']?>"

<?= ($vehiculo['id_chofer']==$c['id_chofer'])
? 'selected'
: '' ?>

>

<?= htmlspecialchars(
$c['nombres'].' '.$c['apellidos']
) ?>

</option>

<?php endforeach; ?>

</select>

</div>


<div class="form-group" style="grid-column:span 2;">

<label>Descripción:</label>

<textarea
name="descripcion_vehiculo"
rows="4"
><?= htmlspecialchars($vehiculo['descripcion_vehiculo']) ?></textarea>

</div>


<div class="form-group">

<label>Foto actual:</label>

<?php if(!empty($vehiculo['foto_vehiculo'])): ?>

<img
src="../<?= $vehiculo['foto_vehiculo'] ?>"
width="150"
style="border-radius:10px"
>

<?php else: ?>

<p>Sin imagen</p>

<?php endif; ?>

</div>


<div class="form-group">

<label>Nueva Foto:</label>

<input
type="file"
name="foto_vehiculo"
accept="image/*"
>

</div>


<div class="form-buttons">

<button type="submit">
💾 Actualizar
</button>

<a href="index.php">
← Regresar
</a>

</div>

</form>

</div>

</body>
</html>