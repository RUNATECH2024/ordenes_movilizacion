<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . '/../includes/conexion.php';

if (!isset($pdo)) {
    die("Error de conexión");
}

try {

    $sql = "
    SELECT
        v.*,
        c.nombres,
        c.apellidos
    FROM vehiculos v
    LEFT JOIN choferes c
    ON v.id_chofer = c.id_chofer
    ORDER BY v.id_vehiculo DESC
    ";

    $stmt = $pdo->query($sql);
    $vehiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    die(
        "Error al obtener vehículos: "
        . $e->getMessage()
    );

}
?>

<!DOCTYPE html>
    <html lang="es">
        <head>
            <meta charset="UTF-8">
                <title>Vehículos</title>
                <link rel="stylesheet" href="../assets/estilos.css">

        </head>
                <body>
                    <div class="container">
                        <h2>🚗 Lista de Vehículos</h2>
                    <div class="menu">
                    <div>
                        <a href="../panel_administracion.php"class="btn btn-primary">← Panel</a>
                        <a href="nuevo.php"
                        class="btn btn-success">➕ Nuevo Vehículo</a>
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

<?php if (!empty($vehiculos)): ?>

<table>

<tr>

<th>Foto</th>
<th>Código</th>
<th>Placa</th>
<th>Matrícula</th>
<th>Marca</th>
<th>Modelo</th>
<th>Tipo</th>
<th>Color</th>
<th>Año</th>
<th>Chofer</th>
<th>Acciones</th>

</tr>

<?php foreach ($vehiculos as $v): ?>

<tr>

<td data-label="Foto">

<?php if(!empty($v['foto_vehiculo'])): ?>

<img
src="../<?= htmlspecialchars($v['foto_vehiculo']) ?>"
width="80"
height="60"
>

<?php else: ?>

Sin foto

<?php endif; ?>

</td>

<td data-label="Código">

<?= htmlspecialchars(
$v['codigo_institucional']
) ?>

</td>

<td data-label="Placa">

<?= htmlspecialchars(
$v['placa']
) ?>

</td>

<td data-label="Matrícula">

<?= htmlspecialchars(
$v['matricula']
) ?>

</td>

<td data-label="Marca">

<?= htmlspecialchars(
$v['marca']
) ?>

</td>

<td data-label="Modelo">

<?= htmlspecialchars(
$v['modelo']
) ?>

</td>

<td data-label="Tipo">

<?= htmlspecialchars(
$v['tipo']
) ?>

</td>

<td data-label="Color">

<?= htmlspecialchars(
$v['color']
) ?>

</td>

<td data-label="Año">

<?= htmlspecialchars(
$v['anio']
) ?>

</td>

<td data-label="Chofer">

<?php

if(!empty($v['nombres'])){

echo htmlspecialchars(
$v['nombres']
." ".
$v['apellidos']
);

}else{

echo "<span class='estado-inactivo'>
Sin asignar
</span>";

}

?>

</td>

<td data-label="Acciones">

<div class="acciones">

<a
href="editar.php?id=<?= $v['id_vehiculo'] ?>"
class="btn btn-warning"
>

✏️ Editar

</a>

<a
href="eliminar.php?id=<?= $v['id_vehiculo'] ?>"
class="btn btn-danger"
onclick="return confirm('¿Eliminar este vehículo?')"
>

❌ Eliminar

</a>

</div>

</td>

</tr>

<?php endforeach; ?>

</table>

<?php else: ?>

<p>No hay vehículos registrados.</p>

<?php endif; ?>

</div>

</div>

</body>
</html>