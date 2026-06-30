<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . '/../includes/conexion.php';

try {

    $stmt = $pdo->query("
        SELECT *
        FROM direcciones
        ORDER BY id_direccion DESC
    ");

    $direcciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    die("Error al obtener direcciones: " . $e->getMessage());

}
?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<title>
Direcciones
</title>

<link rel="stylesheet"
href="../assets/estilos.css">

</head>

<body>

<div class="container">

<h2>
🏢 Direcciones
</h2>


<div class="menu">

<div>

<a href="../panel_administracion.php"
class="btn btn-primary">

← Panel

</a>


<a href="nuevo.php"
class="btn btn-success">

➕ Nueva Dirección

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
<th>Código</th>
<th>Nombre</th>
<th>Descripción</th>
<th>Estado</th>
<th>Acciones</th>

</tr>

</thead>


<tbody>

<?php if (!empty($direcciones)): ?>

<?php foreach ($direcciones as $d): ?>

<tr>

<td data-label="#">

<?= htmlspecialchars($d['id_direccion']) ?>

</td>


<td data-label="Código">

<?= htmlspecialchars($d['codigo']) ?>

</td>


<td data-label="Nombre">

<?= htmlspecialchars($d['nombre']) ?>

</td>


<td data-label="Descripción">

<?= htmlspecialchars($d['descripcion']) ?>

</td>


<td data-label="Estado">

<?php if ($d['estado'] == 'ACTIVO'): ?>

<span class="estado-activo">

ACTIVO

</span>

<?php else: ?>

<span class="estado-inactivo">

<?= htmlspecialchars($d['estado']) ?>

</span>

<?php endif; ?>

</td>


<td data-label="Acciones">

<div class="acciones">

<a href="editar.php?id=<?= $d['id_direccion'] ?>"
class="btn btn-warning">

✏️

</a>


<a href="eliminar.php?id=<?= $d['id_direccion'] ?>"
class="btn btn-danger"
onclick="return confirm('¿Eliminar dirección?')">

❌

</a>

</div>

</td>

</tr>

<?php endforeach; ?>

<?php else: ?>

<tr>

<td colspan="6">

No hay direcciones registradas

</td>

</tr>

<?php endif; ?>

</tbody>

</table>

</div>

</div>

</body>

</html>