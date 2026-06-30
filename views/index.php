<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../includes/conexion.php';

try {

    $query = $pdo->query("

        SELECT 
            o.id_orden,
            o.numero_orden,
            o.fecha_emision,
            o.objeto_movilizacion,
            o.dias_movilizacion,
            o.detalle_dias,

            c.nombres AS chofer_nombres,
            c.apellidos AS chofer_apellidos,

            v.placa,

            r.nombre AS recinto,
            p.nombre AS parroquia,

            d.nombres AS director_nombres,
            d.apellidos AS director_apellidos

        FROM ordenes_movilizacion o

        JOIN choferes c
        ON o.id_chofer = c.id_chofer

        JOIN vehiculos v
        ON o.id_vehiculo = v.id_vehiculo

        JOIN ubicaciones u
        ON o.id_ubicacion = u.id_ubicacion

        JOIN recintos r
        ON u.id_recinto = r.id_recinto

        JOIN parroquias p
        ON r.id_parroquia = p.id_parroquia

        JOIN directores d
        ON o.id_director = d.id_director

        ORDER BY o.fecha_emision DESC

    ");

    $ordenes = $query->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e){

    die(
        "Error al consultar órdenes: "
        .$e->getMessage()
    );

}

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<title>
Órdenes de Movilización
</title>

<link rel="stylesheet"
href="../assets/estilos.css">

</head>

<body>

<div class="container">

<h2>
📝 Órdenes de Movilización
</h2>


<div class="menu">

<div>

<a href="../panel_administracion.php"
class="btn btn-primary">

← Panel

</a>


<a href="nueva_orden.php"
class="btn btn-success">

➕ Nueva Orden

</a>


<a href="../reportes/reporte_pdf.php"
target="_blank"
class="btn btn-danger">

📄 Exportar PDF

</a>


<a href="../reportes/reporte_excel.php"
target="_blank"
class="btn btn-info">

📊 Exportar Excel

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

<th># Orden</th>
<th>Fecha</th>
<th>Chofer</th>
<th>Placa</th>
<th>Recinto</th>
<th>Parroquia</th>
<th>Objeto</th>
<th>Días</th>
<th>Detalle</th>
<th>Director</th>
<th>Acciones</th>

</tr>

</thead>


<tbody>

<?php if(!empty($ordenes)): ?>

<?php foreach($ordenes as $orden): ?>

<tr>

<td data-label="Orden">

<?= htmlspecialchars(
$orden['numero_orden']
) ?>

</td>


<td data-label="Fecha">

<?= date(
'd/m/Y',
strtotime($orden['fecha_emision'])
) ?>

</td>


<td data-label="Chofer">

<?= htmlspecialchars(
$orden['chofer_nombres']
." ".
$orden['chofer_apellidos']
) ?>

</td>


<td data-label="Placa">

<?= htmlspecialchars(
$orden['placa']
) ?>

</td>


<td data-label="Recinto">

<?= htmlspecialchars(
$orden['recinto']
) ?>

</td>


<td data-label="Parroquia">

<?= htmlspecialchars(
$orden['parroquia']
) ?>

</td>


<td data-label="Objeto">

<?= htmlspecialchars(
$orden['objeto_movilizacion']
) ?>

</td>


<td data-label="Días">

<?= htmlspecialchars(
$orden['dias_movilizacion']
) ?>

</td>


<td data-label="Detalle">

<?= htmlspecialchars(
$orden['detalle_dias']
) ?>

</td>


<td data-label="Director">

<?= htmlspecialchars(
$orden['director_nombres']
." ".
$orden['director_apellidos']
) ?>

</td>


<td data-label="Acciones">

<div class="acciones">

<a
class="btn btn-info"
href="ver_orden.php?id=<?= $orden['id_orden'] ?>"
>

👁

</a>


<a
class="btn btn-warning"
href="editar_orden.php?id=<?= $orden['id_orden'] ?>"
>

✏️

</a>


<a
class="btn btn-danger"
href="eliminar_orden.php?id=<?= $orden['id_orden'] ?>"
onclick="return confirm('¿Desea eliminar esta orden?')"
>

❌

</a>


<a
class="btn btn-primary"
href="../reportes/imprimir_orden.php?id=<?= $orden['id_orden'] ?>"
target="_blank"
>

🖨

</a>

</div>

</td>

</tr>

<?php endforeach; ?>

<?php else: ?>

<tr>

<td colspan="11">

No existen órdenes registradas

</td>

</tr>

<?php endif; ?>

</tbody>

</table>

</div>

</div>

</body>
</html>