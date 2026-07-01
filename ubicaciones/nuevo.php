<?php
require_once '../includes/conexion.php';
try {
    $provincias = $pdo->query("SELECT * FROM provincias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al cargar provincias: " . $e->getMessage());
}
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
        if (selectElem.value === 'nuevo') {
            input.style.display = 'block';
            input.required = true;
        } else {
            input.style.display = 'none';
            input.required = false;
            input.value = '';
        }
    }

    async function cargarCiudades(idProvincia) {
        if (!idProvincia || idProvincia === 'nuevo') return;
        try {
            const res = await fetch('cargar_ciudades.php?id_provincia=' + idProvincia);
            if (!res.ok) throw new Error('Error en la respuesta del servidor');
            const ciudades = await res.json();
            
            let select = document.getElementById('ciudad');
            select.innerHTML = '<option value="">Seleccione una ciudad</option><option value="nuevo">➕ Nueva ciudad</option>';
            
            ciudades.forEach(c => {
                select.innerHTML += `<option value="${c.id_ciudad}">${c.nombre}</option>`;
            });
        } catch (error) {
            console.error('Error al cargar ciudades:', error);
            alert('Error al procesar las ciudades. Revisa la consola (F12).');
        }
        document.getElementById('parroquia').innerHTML = '<option value="">Seleccione una parroquia</option><option value="nuevo">➕ Nueva parroquia</option>';
        document.getElementById('recinto').innerHTML = '<option value="">Seleccione un recinto</option><option value="nuevo">➕ Nuevo recinto</option>';
    }

    async function cargarParroquias(idCiudad) {
        if (!idCiudad || idCiudad === 'nuevo') return;
        try {
            const res = await fetch('cargar_parroquias.php?id_ciudad=' + idCiudad);
            if (!res.ok) throw new Error('Error en la respuesta del servidor');
            const parroquias = await res.json();
            
            let select = document.getElementById('parroquia');
            select.innerHTML = '<option value="">Seleccione una parroquia</option><option value="nuevo">➕ Nueva parroquia</option>';
            
            parroquias.forEach(p => {
                select.innerHTML += `<option value="${p.id_parroquia}">${p.nombre} (${p.tipo})</option>`;
            });
        } catch (error) {
            console.error('Error al cargar parroquias:', error);
        }
        document.getElementById('recinto').innerHTML = '<option value="">Seleccione un recinto</option><option value="nuevo">➕ Nuevo recinto</option>';
    }

    async function cargarRecintos(idParroquia) {
        if (!idParroquia || idParroquia === 'nuevo') return;
        try {
            const res = await fetch('cargar_recintos.php?id_parroquia=' + idParroquia);
            if (!res.ok) throw new Error('Error en la respuesta del servidor');
            const recintos = await res.json();
            
            let select = document.getElementById('recinto');
            select.innerHTML = '<option value="">Seleccione un recinto</option><option value="nuevo">➕ Nuevo recinto</option>';
            
            recintos.forEach(r => {
                select.innerHTML += `<option value="${r.id_recinto}">${r.nombre}</option>`;
            });
        } catch (error) {
            console.error('Error al cargar recintos:', error);
        }
    }
    </script>
</head>
<body>
    <h2>🧭 Nueva Ubicación</h2>
    <form action="guardar.php" method="POST">
        <div class="form-grid">
            <div class="form-group">
                <label>Provincia</label>
                <select name="id_provincia" id="provincia" required onchange="cargarCiudades(this.value);toggleNuevo('nueva_provincia',this)">
                    <option value="">Seleccione una provincia</option>
                    <?php foreach($provincias as $p): ?>
                        <option value="<?= $p['id_provincia'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
                    <?php endforeach; ?>
                    <option value="nuevo">➕ Nueva provincia</option>
                </select>
                <input type="text" name="nueva_provincia" id="nueva_provincia" placeholder="Nueva provincia" style="display:none;">
            </div>

            <div class="form-group">
                <label>Ciudad</label>
                <select name="id_ciudad" id="ciudad" required onchange="cargarParroquias(this.value);toggleNuevo('nueva_ciudad',this)">
                    <option value="">Seleccione una ciudad</option>
                    <option value="nuevo">➕ Nueva ciudad</option>
                </select>
                <input type="text" name="nueva_ciudad" id="nueva_ciudad" placeholder="Nueva ciudad" style="display:none;">
            </div>

            <div class="form-group">
                <label>Parroquia</label>
                <select name="id_parroquia" id="parroquia" required onchange="cargarRecintos(this.value);toggleNuevo('nueva_parroquia',this)">
                    <option value="">Seleccione una parroquia</option>
                    <option value="nuevo">➕ Nueva parroquia</option>
                </select>
                <input type="text" name="nueva_parroquia" id="nueva_parroquia" placeholder="Nueva parroquia" style="display:none;">
            </div>

            <div class="form-group">
                <label>Recinto</label>
                <select name="id_recinto" id="recinto" required onchange="toggleNuevo('nuevo_recinto',this)">
                    <option value="">Seleccione un recinto</option>
                    <option value="nuevo">➕ Nuevo recinto</option>
                </select>
                <input type="text" name="nuevo_recinto" id="nuevo_recinto" placeholder="Nuevo recinto" style="display:none;">
            </div>

            <div class="form-group">
                <label>Barrio / Comunidad</label>
                <input type="text" name="barrio_comunidad" placeholder="Ej. La Merced" required>
            </div>

            <div class="form-group" style="flex:1 1 100%;">
                <label>Punto de Referencia</label>
                <input type="text" name="punto_referencia" required placeholder="Ej. Frente al parque central">
            </div>
        </div>
        <div class="form-buttons">
            <button type="submit">💾 Guardar</button>
            <a href="index.php">⬅️ Regresar</a>
        </div>
    </form>
</body>
</html>