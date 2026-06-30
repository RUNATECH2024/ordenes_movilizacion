<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../includes/conexion.php';

try {

    $stmt = $pdo->query("
        SELECT * 
        FROM choferes 
        ORDER BY id_chofer DESC
    ");

    $choferes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    die(
        "Error al obtener choferes: " .
        $e->getMessage()
    );

}
?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<title>Choferes</title>

<link rel="stylesheet" href="../assets/estilos.css">

</head>

<body>

<div class="container">

<h2>🧑‍✈️ Listado de Choferes</h2>

<div class="menu">

<div>

<a href="../panel_administracion.php"
class="btn btn-primary">

← Panel

</a>

<a href="nuevo.php"
class="btn btn-success">

➕ Nuevo Chofer

</a>

<a href="../reportes/choferes_pdf.php"
target="_blank"
class="btn btn-info">

📄 Reporte PDF

</a>

</div>

<div>

<a href="../auth/logout.php"
class="btn btn-danger">

🚪 Cerrar sesión

</a>

</div>

</div>

<hr>

<div class="table-container">

<table>

<thead>

<tr>

<th>#</th>
<th>Nombre</th>
<th>Cédula</th>
<th>Teléfono</th>
<th>Tipo Licencia</th>
<th>N° Licencia</th>
<th>Cargo</th>
<th>Departamento</th>
<th>Caducidad</th>
<th>Estado</th>
<th>Acciones</th>

</tr>

</thead>

<tbody>

<?php if (!empty($choferes)): ?>

<?php foreach ($choferes as $c): ?>

<?php

$estado = $c['estado'] ?? 'ACTIVO';

$caducidad =
$c['fecha_caducidad_licencia'] ?? null;

?>

<tr>

<td data-label="#">

<?= htmlspecialchars(
$c['id_chofer']
) ?>

</td>

<td data-label="Nombre">

<?= htmlspecialchars(
$c['nombres']." ".
$c['apellidos']
) ?>

</td>

<td data-label="Cédula">

<?= htmlspecialchars(
$c['cedula']
) ?>

</td>

<td data-label="Teléfono">

<?= htmlspecialchars(
$c['telefono'] ?? ''
) ?>

</td>

<td data-label="Tipo Licencia">

<?= htmlspecialchars(
$c['tipo_licencia'] ?? ''
) ?>

</td>

<td data-label="N° Licencia">

<?= htmlspecialchars(
$c['numero_licencia'] ?? ''
) ?>

</td>

<td data-label="Cargo">

<?= htmlspecialchars(
$c['cargo'] ?? ''
) ?>

</td>

<td data-label="Departamento">

<?= htmlspecialchars(
$c['departamento'] ?? ''
) ?>

</td>

<td data-label="Caducidad">

<?= $caducidad
? date(
'd/m/Y',
strtotime($caducidad)
)
: '-' ?>

</td>

<td data-label="Estado">

<?php if($estado=="ACTIVO"): ?>

<span class="estado-activo">

ACTIVO

</span>

<?php else: ?>

<span class="estado-inactivo">

<?= htmlspecialchars(
$estado
) ?>

</span>

<?php endif; ?>

</td>

<td data-label="Acciones">

<div class="acciones">

<a
href="editar.php?id=<?= $c['id_chofer'] ?>"
class="btn btn-warning"
>

✏️

</a>

<a
href="eliminar.php?id=<?= $c['id_chofer'] ?>"
class="btn btn-danger"
onclick="return confirm('¿Eliminar chofer?')"
>

🗑️

</a>

</div>

</td>

</tr>

<?php endforeach; ?>

<?php else: ?>

<tr>

<td colspan="11">

No existen choferes registrados

</td>

</tr>

<?php endif; ?>

</tbody>

</table>

</div>

</div>

</body>
</html>