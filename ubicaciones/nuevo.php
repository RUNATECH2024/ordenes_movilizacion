<?php
require_once '../includes/conexion.php';

// Cargar provincias al inicio
$provincias = $pdo->query("SELECT * FROM provincias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>🧭 Nueva Ubicación</title>
<link rel="stylesheet" href="../assets/estilos.css">

<script>
function toggleNuevo(inputId, selectElem) {
    const input = document.getElementById(inputId);
    input.style.display = (selectElem.value === 'nuevo') ? 'block' : 'none';
    input.required = (selectElem.value === 'nuevo');
}

// AJAX para cargar ciudades según provincia
async function cargarCiudades(idProvincia) {
    const res = await fetch('../includes/cargar_ciudades.php?id_provincia=' + idProvincia);
    const ciudades = await res.json();
    const select = document.getElementById('ciudad');
    select.innerHTML = '<option value="">Seleccione una ciudad</option><option value="nuevo">➕ Nueva ciudad</option>';
    ciudades.forEach(c => select.innerHTML += `<option value="${c.id_ciudad}">${c.nombre}</option>`);

    // Reiniciar parroquia y recinto
    document.getElementById('parroquia').innerHTML = '<option value="">Seleccione una parroquia</option><option value="nuevo">➕ Nueva parroquia</option>';
    document.getElementById('recinto').innerHTML = '<option value="">Seleccione un recinto</option><option value="nuevo">➕ Nuevo recinto</option>';
}

// AJAX para cargar parroquias según ciudad
async function cargarParroquias(idCiudad) {
    const res = await fetch('../includes/cargar_parroquias.php?id_ciudad=' + idCiudad);
    const parroquias = await res.json();
    const select = document.getElementById('parroquia');
    select.innerHTML = '<option value="">Seleccione una parroquia</option><option value="nuevo">➕ Nueva parroquia</option>';
    parroquias.forEach(p => select.innerHTML += `<option value="${p.id_parroquia}">${p.nombre}</option>`);

    // Reiniciar recinto
    document.getElementById('recinto').innerHTML = '<option value="">Seleccione un recinto</option><option value="nuevo">➕ Nuevo recinto</option>';
}

// AJAX para cargar recintos según parroquia
async function cargarRecintos(idParroquia) {
    const res = await fetch('../includes/cargar_recintos.php?id_parroquia=' + idParroquia);
    const recintos = await res.json();
    const select = document.getElementById('recinto');
    select.innerHTML = '<option value="">Seleccione un recinto</option><option value="nuevo">➕ Nuevo recinto</option>';
    recintos.forEach(r => select.innerHTML += `<option value="${r.id_recinto}">${r.nombre}</option>`);
}
</script>
</head>

<body>
<h2>🧭 Nueva Ubicación</h2>

<form action="guardar.php" method="POST">
    <div class="form-grid">
        <!-- Provincia -->
        <div class="form-group">
            <label>Provincia</label>
            <select name="id_provincia" id="provincia" required onchange="cargarCiudades(this.value); toggleNuevo('nueva_provincia', this)">
                <option value="">Seleccione una provincia</option>
                <?php foreach ($provincias as $p): ?>
                    <option value="<?= $p['id_provincia'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
                <?php endforeach; ?>
                <option value="nuevo">➕ Nueva provincia</option>
            </select>
            <input type="text" name="nueva_provincia" id="nueva_provincia" placeholder="Nombre de nueva provincia" style="display:none;">
        </div>

        <!-- Ciudad -->
        <div class="form-group">
            <label>Ciudad</label>
            <select name="id_ciudad" id="ciudad" required onchange="cargarParroquias(this.value); toggleNuevo('nueva_ciudad', this)">
                <option value="">Seleccione una ciudad</option>
                <option value="nuevo">➕ Nueva ciudad</option>
            </select>
            <input type="text" name="nueva_ciudad" id="nueva_ciudad" placeholder="Nombre de nueva ciudad" style="display:none;">
        </div>

        <!-- Parroquia -->
        <div class="form-group">
            <label>Parroquia</label>
            <select name="id_parroquia" id="parroquia" required onchange="cargarRecintos(this.value); toggleNuevo('nueva_parroquia', this)">
                <option value="">Seleccione una parroquia</option>
                <option value="nuevo">➕ Nueva parroquia</option>
            </select>
            <input type="text" name="nueva_parroquia" id="nueva_parroquia" placeholder="Nombre de nueva parroquia" style="display:none;">
        </div>

        <!-- Recinto -->
        <div class="form-group">
            <label>Recinto</label>
            <select name="id_recinto" id="recinto" required onchange="toggleNuevo('nuevo_recinto', this)">
                <option value="">Seleccione un recinto</option>
                <option value="nuevo">➕ Nuevo recinto</option>
            </select>
            <input type="text" name="nuevo_recinto" id="nuevo_recinto" placeholder="Nombre de nuevo recinto" style="display:none;">
        </div>

        <!-- Referencia -->
        <div class="form-group" style="flex: 1 1 100%;">
            <label>Punto de Referencia</label>
            <input type="text" name="referencia" required placeholder="Ej. Frente al parque central">
        </div>
    </div>

    <div class="form-buttons">
        <button type="submit">💾 Guardar</button>
        <a href="index.php">⬅️ Regresar</a>
    </div>
</form>
</body>
</html>
