<?php
require_once '../includes/conexion.php';

if (!isset($_GET['id'])) {
    die("❌ ID de ubicación no especificado.");
}

$id_ubicacion = $_GET['id'];

$sql = "SELECT u.id_ubicacion, u.referencia, 
               r.id_recinto, r.nombre AS nombre_recinto,
               p.id_parroquia, p.nombre AS nombre_parroquia,
               c.id_ciudad, c.nombre AS nombre_ciudad,
               pr.id_provincia, pr.nombre AS nombre_provincia
        FROM ubicaciones u
        JOIN recintos r ON u.id_recinto = r.id_recinto
        JOIN parroquias p ON r.id_parroquia = p.id_parroquia
        JOIN ciudades c ON p.id_ciudad = c.id_ciudad
        JOIN provincias pr ON c.id_provincia = pr.id_provincia
        WHERE u.id_ubicacion = ?";
$stmt = $conexion->prepare($sql);
$stmt->execute([$id_ubicacion]);
$ubicacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ubicacion) {
    die("❌ No se encontró la ubicación.");
}

$provincias = $conexion->query("SELECT id_provincia, nombre FROM provincias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$ciudades = $conexion->query("SELECT id_ciudad, nombre FROM ciudades WHERE id_provincia = {$ubicacion['id_provincia']} ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$parroquias = $conexion->query("SELECT id_parroquia, nombre FROM parroquias WHERE id_ciudad = {$ubicacion['id_ciudad']} ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$recintos = $conexion->query("SELECT id_recinto, nombre FROM recintos WHERE id_parroquia = {$ubicacion['id_parroquia']} ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Ubicación</title>
    <link rel="stylesheet" href="../assets/estilos.css">
    <script>
    function toggleNuevo(inputId, selectElem) {
        const input = document.getElementById(inputId);
        input.style.display = (selectElem.value === 'nuevo') ? 'block' : 'none';
        input.required = (selectElem.value === 'nuevo');
    }

    async function cargarCiudades(idProvincia) {
        const res = await fetch('../includes/cargar_ciudades.php?id_provincia=' + idProvincia);
        const ciudades = await res.json();
        const select = document.getElementById('ciudad');
        select.innerHTML = '<option value="">Seleccione una ciudad</option>';
        ciudades.forEach(c => {
            select.innerHTML += `<option value="${c.id_ciudad}">${c.nombre}</option>`;
        });
    }

    async function cargarParroquias(idCiudad) {
        const res = await fetch('../includes/cargar_parroquias.php?id_ciudad=' + idCiudad);
        const parroquias = await res.json();
        const select = document.getElementById('parroquia');
        select.innerHTML = '<option value="">Seleccione una parroquia</option>';
        parroquias.forEach(p => {
            select.innerHTML += `<option value="${p.id_parroquia}">${p.nombre}</option>`;
        });
    }

    async function cargarRecintos(idParroquia) {
        const res = await fetch('../includes/cargar_recintos.php?id_parroquia=' + idParroquia);
        const recintos = await res.json();
        const select = document.getElementById('recinto');
        select.innerHTML = '<option value="">Seleccione un recinto</option>';
        recintos.forEach(r => {
            select.innerHTML += `<option value="${r.id_recinto}">${r.nombre}</option>`;
        });
    }
    </script>
</head>
<body>
<h2>✏️ Editar Ubicación</h2>

<form action="actualizar.php" method="POST">
    <input type="hidden" name="id_ubicacion" value="<?= $ubicacion['id_ubicacion'] ?>">

    <label>Provincia:<br>
        <select name="id_provincia" id="provincia" required onchange="cargarCiudades(this.value); toggleNuevo('nueva_provincia', this)">
            <option value="">Seleccione una provincia</option>
            <?php foreach ($provincias as $p): ?>
                <option value="<?= $p['id_provincia'] ?>" <?= $p['id_provincia'] == $ubicacion['id_provincia'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['nombre']) ?>
                </option>
            <?php endforeach; ?>
            <option value="nuevo">➕ Crear nueva provincia</option>
        </select><br>
        <input type="text" name="nueva_provincia" id="nueva_provincia" placeholder="Nueva provincia" style="display:none;">
    </label><br><br>

    <label>Ciudad:<br>
        <select name="id_ciudad" id="ciudad" required onchange="cargarParroquias(this.value); toggleNuevo('nueva_ciudad', this)">
            <option value="">Seleccione una ciudad</option>
            <?php foreach ($ciudades as $c): ?>
                <option value="<?= $c['id_ciudad'] ?>" <?= $c['id_ciudad'] == $ubicacion['id_ciudad'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['nombre']) ?>
                </option>
            <?php endforeach; ?>
            <option value="nuevo">➕ Crear nueva ciudad</option>
        </select><br>
        <input type="text" name="nueva_ciudad" id="nueva_ciudad" placeholder="Nueva ciudad" style="display:none;">
    </label><br><br>

    <label>Parroquia:<br>
        <select name="id_parroquia" id="parroquia" required onchange="cargarRecintos(this.value); toggleNuevo('nueva_parroquia', this)">
            <option value="">Seleccione una parroquia</option>
            <?php foreach ($parroquias as $p): ?>
                <option value="<?= $p['id_parroquia'] ?>" <?= $p['id_parroquia'] == $ubicacion['id_parroquia'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['nombre']) ?>
                </option>
            <?php endforeach; ?>
            <option value="nuevo">➕ Crear nueva parroquia</option>
        </select><br>
        <input type="text" name="nueva_parroquia" id="nueva_parroquia" placeholder="Nueva parroquia" style="display:none;">
    </label><br><br>

    <label>Recinto:<br>
        <select name="id_recinto" id="recinto" required onchange="toggleNuevo('nuevo_recinto', this)">
            <option value="">Seleccione un recinto</option>
            <?php foreach ($recintos as $r): ?>
                <option value="<?= $r['id_recinto'] ?>" <?= $r['id_recinto'] == $ubicacion['id_recinto'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($r['nombre']) ?>
                </option>
            <?php endforeach; ?>
            <option value="nuevo">➕ Crear nuevo recinto</option>
        </select><br>
        <input type="text" name="nuevo_recinto" id="nuevo_recinto" placeholder="Nuevo recinto" style="display:none;">
    </label><br><br>

    <label>Referencia:<br>
        <input type="text" name="referencia" value="<?= htmlspecialchars($ubicacion['referencia']) ?>" required>
    </label><br><br>

    <button type="submit">💾 Guardar Cambios</button>
</form>

<br>
<a href="index.php">⬅️ Regresar</a>
</body>
</html>
