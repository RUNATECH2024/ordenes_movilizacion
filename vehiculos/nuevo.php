<?php
session_start();
require_once __DIR__ . '/../includes/conexion.php';

// Obtener lista de choferes
$sql = "SELECT id_chofer, nombres, apellidos
        FROM choferes
        ORDER BY nombres ASC";

$stmt = $pdo->query($sql);
$choferes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Nuevo Vehículo</title>
<link rel="stylesheet" href="../assets/estilos.css">

<style>

.form-dos-columnas{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:15px;
    max-width:1000px;
}

.form-group{
    display:flex;
    flex-direction:column;
}

.form-group label{
    font-weight:bold;
    margin-bottom:5px;
}

.form-group input,
.form-group select,
.form-group textarea{
    padding:10px;
    border:1px solid #ccc;
    border-radius:5px;
}

.ancho-completo{
    grid-column:span 2;
}

.form-buttons{
    grid-column:span 2;
    margin-top:20px;
}

button{
    padding:10px 20px;
    cursor:pointer;
}

</style>

</head>
<body>

<h2>🚗 Nuevo Vehículo</h2>

<form
class="form-dos-columnas"
action="insertar.php"
method="POST"
enctype="multipart/form-data"
onsubmit="return validarFormulario()"
>

<div class="form-group">
<label>Código Institucional:</label>
<input type="text"
name="codigo_institucional"
required>
</div>

<div class="form-group">
<label>Placa:</label>
<input type="text"
name="placa"
required>
</div>

<div class="form-group">
<label>Matrícula:</label>
<input type="text"
name="matricula"
required>
</div>

<div class="form-group">
<label>Chasis:</label>
<input type="text"
name="chasis"
required>
</div>

<div class="form-group">
<label>Motor:</label>
<input type="text"
name="motor"
required>
</div>

<div class="form-group">
<label>Marca:</label>
<input type="text"
name="marca">
</div>

<div class="form-group">
<label>Modelo:</label>
<input type="text"
name="modelo">
</div>

<div class="form-group">
<label>Tipo:</label>
<input type="text"
name="tipo">
</div>

<div class="form-group">
<label>Unidad:</label>
<input type="text"
name="unidad">
</div>

<div class="form-group">
<label>Color:</label>
<input type="text"
name="color">
</div>

<div class="form-group">
<label>Año:</label>
<input
type="number"
name="anio"
id="anio"
min="1900"
max="<?= date('Y')?>"
>
</div>

<div class="form-group">
<label>Chofer asignado:</label>

<select name="id_chofer">
<option value="">Seleccione</option>

<?php foreach($choferes as $chofer): ?>

<option value="<?= $chofer['id_chofer'] ?>">

<?= htmlspecialchars(
$chofer['nombres']." ".
$chofer['apellidos']
) ?>

</option>

<?php endforeach; ?>

</select>

</div>

<div class="form-group ancho-completo">

<label>Descripción del Vehículo:</label>

<textarea
name="descripcion_vehiculo"
rows="4"
></textarea>

</div>

<div class="form-group ancho-completo">

<label>Foto del Vehículo:</label>

<input
type="file"
name="foto_vehiculo"
accept="image/*"
>

</div>

<div class="form-buttons">

<button type="submit">
💾 Guardar
</button>

<a href="index.php">
← Regresar
</a>

</div>

</form>


<script>

function validarFormulario(){

let anio=
document.getElementById(
'anio'
).value;

let actual=
new Date().getFullYear();

if(
anio &&
(anio<1900 ||
anio>actual)
){

alert(
"Ingrese un año válido entre 1900 y "+actual
);

return false;

}

return true;

}

</script>

</body>
</html>