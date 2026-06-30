<?php
require_once '../includes/conexion.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    die("ID inválido.");
}

$query = $pdo->prepare("
    SELECT * 
    FROM ordenes_movilizacion 
    WHERE id_orden = :id
");

$query->execute([':id'=>$id]);

$orden = $query->fetch(PDO::FETCH_ASSOC);

if (!$orden) {
    die("Orden no encontrada.");
}

$choferes = $pdo->query("
SELECT *
FROM choferes
")->fetchAll(PDO::FETCH_ASSOC);

$vehiculos = $pdo->query("
SELECT *
FROM vehiculos
")->fetchAll(PDO::FETCH_ASSOC);

$ubicaciones = $pdo->query("
SELECT 
u.id_ubicacion,
r.nombre AS recinto,
p.nombre AS parroquia,
ci.nombre AS ciudad,
pr.nombre AS provincia

FROM ubicaciones u
JOIN recintos r
ON u.id_recinto=r.id_recinto

JOIN parroquias p
ON r.id_parroquia=p.id_parroquia

JOIN ciudades ci
ON p.id_ciudad=ci.id_ciudad

JOIN provincias pr
ON ci.id_provincia=pr.id_provincia
")->fetchAll(PDO::FETCH_ASSOC);

$directores = $pdo->query("
SELECT *
FROM directores
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>

<meta charset="UTF-8">
<title>Editar Orden</title>

<link rel="stylesheet"
href="../assets/estilos.css">

</head>

<body>

<div class="container">

<h2>Editar Orden de Movilización</h2>

<form action="actualizar.php"
method="POST">

<input type="hidden"
name="id_orden"
value="<?= $orden['id_orden']?>">

<input type="hidden"
name="detalle_dias"
id="detalle_hidden"
value="<?= htmlspecialchars($orden['detalle_dias']) ?>">

<label>Fecha emisión:</label>

<input
type="date"
id="fecha_emision"
name="fecha_emision"
value="<?= $orden['fecha_emision']?>"
onchange="generarDias()"
required>

<label>Chofer:</label>

<select name="id_chofer">

<?php foreach($choferes as $c): ?>

<option
value="<?= $c['id_chofer']?>"
<?=($c['id_chofer']==$orden['id_chofer'])?'selected':''?>
>

<?= htmlspecialchars(
$c['nombres']." ".
$c['apellidos']
) ?>

</option>

<?php endforeach; ?>

</select>


<label>Vehículo:</label>

<select name="id_vehiculo">

<?php foreach($vehiculos as $v): ?>

<option
value="<?= $v['id_vehiculo']?>"
<?=($v['id_vehiculo']==$orden['id_vehiculo'])?'selected':''?>
>

<?= htmlspecialchars(
$v['placa']." - ".
$v['modelo']
) ?>

</option>

<?php endforeach; ?>

</select>


<label>Ubicación:</label>

<select name="id_ubicacion">

<?php foreach($ubicaciones as $u): ?>

<option
value="<?= $u['id_ubicacion']?>"
<?=($u['id_ubicacion']==$orden['id_ubicacion'])?'selected':''?>
>

<?= htmlspecialchars(
$u['recinto']." - ".
$u['parroquia']." - ".
$u['ciudad']." - ".
$u['provincia']
) ?>

</option>

<?php endforeach; ?>

</select>


<label>Objeto:</label>

<textarea
name="objeto_movilizacion"
required><?= htmlspecialchars($orden['objeto_movilizacion']) ?></textarea>


<label>Días movilización:</label>

<input
type="number"
id="dias_movilizacion"
name="dias_movilizacion"
min="1"
max="15"
value="<?= $orden['dias_movilizacion']?>"
onchange="generarDias()"
required>


<label>Detalle de días:</label>

<textarea
id="detalle_dias"
readonly
rows="4"><?= htmlspecialchars($orden['detalle_dias']) ?></textarea>


<label>Director:</label>

<select name="id_director">

<?php foreach($directores as $d): ?>

<option
value="<?= $d['id_director']?>"
<?=($d['id_director']==$orden['id_director'])?'selected':''?>
>

<?= htmlspecialchars(
$d['nombres']." ".
$d['apellidos']
) ?>

</option>

<?php endforeach; ?>

</select>

<br><br>

<button type="submit">
Actualizar Orden
</button>

</form>

</div>

<script>

function generarDias(){

let fecha=
document.getElementById(
'fecha_emision'
).value;

let dias=
document.getElementById(
'dias_movilizacion'
).value;

if(!fecha || !dias){
return;
}

const nombresDias=[
'Domingo',
'Lunes',
'Martes',
'Miércoles',
'Jueves',
'Viernes',
'Sábado'
];

let fechaBase=
new Date(fecha);

let resultado=[];

for(let i=0;i<dias;i++){

let fechaNueva=
new Date(fechaBase);

fechaNueva.setDate(
fechaNueva.getDate()+i
);

let dia=
nombresDias[
fechaNueva.getDay()
];

let numero=
fechaNueva.getDate();

resultado.push(
dia+" "+numero
);

}

let texto=
resultado.join(", ");

document.getElementById(
'detalle_dias'
).value=texto;

document.getElementById(
'detalle_hidden'
).value=texto;

}

window.onload=
generarDias;

</script>

</body>
</html>