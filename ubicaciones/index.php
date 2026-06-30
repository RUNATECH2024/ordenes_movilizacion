<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../includes/conexion.php';

try {

    $stmt = $pdo->query("
        SELECT 
            u.id_ubicacion,
            r.nombre AS recinto,
            p.nombre AS parroquia,
            c.nombre AS ciudad,
            pr.nombre AS provincia

        FROM ubicaciones u

        JOIN recintos r
        ON u.id_recinto = r.id_recinto

        JOIN parroquias p
        ON r.id_parroquia = p.id_parroquia

        JOIN ciudades c
        ON p.id_ciudad = c.id_ciudad

        JOIN provincias pr
        ON c.id_provincia = pr.id_provincia

        ORDER BY
        pr.nombre,
        c.nombre,
        p.nombre,
        r.nombre
    ");

    $ubicaciones =
    $stmt->fetchAll(PDO::FETCH_ASSOC);

}
catch(PDOException $e){

    die(
        "Error al obtener ubicaciones: ".
        $e->getMessage()
    );

}
?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<title>
Ubicaciones
</title>

<link rel="stylesheet"
href="../assets/estilos.css">

</head>

<body>

<div class="container">

<h2>
📍 Listado de Ubicaciones
</h2>


<div class="menu">

<div>

<a href="../panel_administracion.php"
class="btn btn-primary">

← Panel

</a>


<a href="nuevo.php"
class="btn btn-success">

➕ Nueva Ubicación

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
<th>Recinto</th>
<th>Parroquia</th>
<th>Ciudad</th>
<th>Provincia</th>
<th>Acciones</th>

</tr>

</thead>

<tbody>

<?php if(!empty($ubicaciones)): ?>

<?php foreach($ubicaciones as $u): ?>

<tr>

<td data-label="#">

<?= htmlspecialchars(
$u['id_ubicacion']
) ?>

</td>


<td data-label="Recinto">

<?= htmlspecialchars(
$u['recinto']
) ?>

</td>


<td data-label="Parroquia">

<?= htmlspecialchars(
$u['parroquia']
) ?>

</td>


<td data-label="Ciudad">

<?= htmlspecialchars(
$u['ciudad']
) ?>

</td>


<td data-label="Provincia">

<?= htmlspecialchars(
$u['provincia']
) ?>

</td>


<td data-label="Acciones">

<div class="acciones">

<a
href="editar.php?id=<?= $u['id_ubicacion'] ?>"
class="btn btn-warning"
>

✏️

</a>


<a
href="eliminar.php?id=<?= $u['id_ubicacion'] ?>"
class="btn btn-danger"
onclick="return confirm('¿Eliminar ubicación?')"
>

🗑️

</a>

</div>

</td>

</tr>

<?php endforeach; ?>

<?php else: ?>

<tr>

<td colspan="6">

No hay ubicaciones registradas

</td>

</tr>

<?php endif; ?>

</tbody>

</table>

</div>

</div>

</body>
</html>