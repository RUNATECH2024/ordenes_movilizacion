<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../includes/conexion.php';

try {
    // Calcular el siguiente número de orden.
    $sqlSecuencia = "
        SELECT COALESCE(
            MAX(
                CAST(
                    NULLIF(REGEXP_REPLACE(numero_orden, '\\D', '', 'g'), '')
                    AS INTEGER
                )
            ),
            0
        ) + 1 AS siguiente
        FROM ordenes_movilizacion
    ";

    $stmtSiguiente = $pdo->query($sqlSecuencia);
    $siguienteNum = $stmtSiguiente->fetch(PDO::FETCH_ASSOC)['siguiente'];
    $siguienteOrden = "ORD-" . str_pad($siguienteNum, 5, "0", STR_PAD_LEFT);

    // Solo se muestran choferes activos.
    $choferes = $pdo->query("
        SELECT id_chofer, nombres || ' ' || apellidos AS nombre
        FROM choferes
        WHERE UPPER(COALESCE(estado, 'ACTIVO')) = 'ACTIVO'
        ORDER BY nombres, apellidos
    ")->fetchAll(PDO::FETCH_ASSOC);

    $ubicaciones = $pdo->query("
        SELECT u.id_ubicacion,
               r.nombre AS recinto,
               p.nombre AS parroquia,
               c.nombre AS ciudad,
               pr.nombre AS provincia
        FROM ubicaciones u
        JOIN recintos r ON u.id_recinto = r.id_recinto
        JOIN parroquias p ON r.id_parroquia = p.id_parroquia
        JOIN ciudades c ON p.id_ciudad = c.id_ciudad
        JOIN provincias pr ON c.id_provincia = pr.id_provincia
        ORDER BY pr.nombre, c.nombre, p.nombre, r.nombre
    ")->fetchAll(PDO::FETCH_ASSOC);

    // NUEVO: cargar todas las Secretarías/Direcciones registradas.
    $direcciones = $pdo->query("
        SELECT id_direccion, nombre
        FROM direcciones
        ORDER BY nombre
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error al cargar datos iniciales: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva Orden de Movilización</title>
    <link rel="stylesheet" href="../assets/estilos.css">
</head>
<body>
<div class="container">
    <h1>Nueva Orden de Movilización</h1>

    <form action="insertar.php" method="POST" class="form-dos-columnas" id="formOrden">
        <div class="form-group">
            <label for="numero_orden">Número de Orden</label>
            <input
                type="text"
                id="numero_orden"
                name="numero_orden"
                value="<?= htmlspecialchars($siguienteOrden) ?>"
                readonly
                style="background:#f5f5f5; font-weight:bold;"
                required
            >
        </div>

        <div class="form-group">
            <label for="fecha_emision">Fecha de Emisión</label>
            <input type="date" id="fecha_emision" name="fecha_emision" required onchange="generarDias()">
        </div>

        <div class="form-group">
            <label for="id_chofer">Chofer</label>
            <select name="id_chofer" id="id_chofer" required onchange="manejarCambioChofer(this)">
                <option value="">Seleccione</option>
                <?php foreach ($choferes as $c): ?>
                    <option value="<?= (int)$c['id_chofer'] ?>">
                        <?= htmlspecialchars($c['nombre']) ?>
                    </option>
                <?php endforeach; ?>
                <option value="nuevo">➕ Crear nuevo chofer</option>
            </select>
        </div>

        <div class="form-group">
            <label for="vehiculo_asignado">Vehículo Asignado</label>
            <input
                type="text"
                id="vehiculo_asignado"
                value=""
                placeholder="Seleccione primero un chofer"
                readonly
                style="background:#f5f5f5;"
            >

            <!-- El identificador real del vehículo se envía oculto al guardar. -->
            <input type="hidden" name="id_vehiculo" id="id_vehiculo" value="">
            <small id="mensajeVehiculo" style="display:block; margin-top:6px;"></small>
        </div>

        <div class="form-group">
            <label for="id_ubicacion">Ubicación</label>
            <select name="id_ubicacion" id="id_ubicacion" required onchange="checkNuevo(this,'../ubicaciones/nuevo.php')">
                <option value="">Seleccione</option>
                <?php foreach ($ubicaciones as $u): ?>
                    <option value="<?= (int)$u['id_ubicacion'] ?>">
                        <?= htmlspecialchars($u['recinto'] . " / " . $u['parroquia'] . " / " . $u['ciudad'] . " / " . $u['provincia']) ?>
                    </option>
                <?php endforeach; ?>
                <option value="nuevo">➕ Crear nueva ubicación</option>
            </select>
        </div>

        <div class="form-group">
            <label for="objeto_movilizacion">Objeto de movilización</label>
            <textarea id="objeto_movilizacion" name="objeto_movilizacion" rows="3" required></textarea>
        </div>

        <div class="form-group">
            <label for="dias_movilizacion">Días de Movilización</label>
            <select name="dias_movilizacion" id="dias_movilizacion" required onchange="generarDias()">
                <option value="">Seleccione</option>
                <?php for ($i = 1; $i <= 10; $i++): ?>
                    <option value="<?= $i ?>"><?= $i ?> día<?= ($i > 1 ? 's' : '') ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="detalle_dias">Detalle de días</label>
            <textarea id="detalle_dias" name="detalle_dias" readonly style="background:#f5f5f5" rows="4"></textarea>
        </div>

        <!-- NUEVO: primero se selecciona la Secretaría/Dirección. -->
        <div class="form-group">
            <label for="id_direccion">Secretaría / Dirección</label>
            <select name="id_direccion" id="id_direccion" required onchange="cargarDirector(this.value)">
                <option value="">Seleccionar Secretaría o Dirección</option>
                <?php foreach ($direcciones as $direccion): ?>
                    <option value="<?= (int)$direccion['id_direccion'] ?>">
                        <?= htmlspecialchars($direccion['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- El usuario ve el nombre, pero no puede modificarlo manualmente. -->
        <div class="form-group">
            <label for="director_responsable">Director Responsable</label>
            <input
                type="text"
                id="director_responsable"
                value=""
                placeholder="Seleccione primero una Secretaría o Dirección"
                readonly
                style="background:#f5f5f5;"
            >

            <!-- Este valor oculto es el que se guarda en ordenes_movilizacion.id_director. -->
            <input type="hidden" name="id_director" id="id_director" value="">
            <small id="mensajeDirector" style="display:block; margin-top:6px;"></small>
        </div>

        <div class="form-buttons">
            <button type="submit">Guardar Orden</button>
            <a href="index.php">Cancelar</a>
        </div>
    </form>
</div>

<script>
function checkNuevo(selectElem, urlNuevo) {
    if (selectElem.value === "nuevo") {
        window.location.href = urlNuevo;
    }
}

function manejarCambioChofer(selectElem) {
    if (selectElem.value === 'nuevo') {
        window.location.href = '../choferes/nuevo.php';
        return;
    }

    cargarVehiculo(selectElem.value);
}

async function cargarVehiculo(idChofer) {
    const campoDescripcion = document.getElementById('vehiculo_asignado');
    const campoId = document.getElementById('id_vehiculo');
    const mensaje = document.getElementById('mensajeVehiculo');

    campoDescripcion.value = '';
    campoId.value = '';
    mensaje.textContent = '';
    mensaje.style.color = '';

    if (!idChofer) {
        campoDescripcion.placeholder = 'Seleccione primero un chofer';
        return;
    }

    campoDescripcion.placeholder = 'Buscando vehículo asignado...';

    try {
        const respuesta = await fetch(
            'obtener_vehiculo.php?id_chofer=' + encodeURIComponent(idChofer),
            {
                method: 'GET',
                headers: { 'Accept': 'application/json' },
                cache: 'no-store'
            }
        );

        if (!respuesta.ok) {
            throw new Error('No se pudo consultar el vehículo asignado.');
        }

        const datos = await respuesta.json();

        if (datos.success && datos.vehiculo) {
            campoDescripcion.value = datos.vehiculo.descripcion;
            campoId.value = datos.vehiculo.id_vehiculo;
            campoDescripcion.placeholder = '';
            mensaje.textContent = 'Vehículo asignado automáticamente.';
            mensaje.style.color = '#198754';
        } else {
            campoDescripcion.value = '';
            campoId.value = '';
            campoDescripcion.placeholder = 'No existe un vehículo asignado';
            mensaje.textContent = datos.message || 'El chofer seleccionado no tiene un vehículo asignado.';
            mensaje.style.color = '#dc3545';
        }
    } catch (error) {
        campoDescripcion.value = '';
        campoId.value = '';
        campoDescripcion.placeholder = 'Error al cargar el vehículo';
        mensaje.textContent = error.message;
        mensaje.style.color = '#dc3545';
    }
}

async function cargarDirector(idDireccion) {
    const campoNombre = document.getElementById('director_responsable');
    const campoId = document.getElementById('id_director');
    const mensaje = document.getElementById('mensajeDirector');

    campoNombre.value = '';
    campoId.value = '';
    mensaje.textContent = '';
    mensaje.style.color = '';

    if (!idDireccion) {
        campoNombre.placeholder = 'Seleccione primero una Secretaría o Dirección';
        return;
    }

    campoNombre.placeholder = 'Buscando director responsable...';

    try {
        const respuesta = await fetch(
            'obtener_director.php?id_direccion=' + encodeURIComponent(idDireccion),
            {
                method: 'GET',
                headers: { 'Accept': 'application/json' },
                cache: 'no-store'
            }
        );

        if (!respuesta.ok) {
            throw new Error('No se pudo consultar el director responsable.');
        }

        const datos = await respuesta.json();

        if (datos.success && datos.director) {
            campoNombre.value = datos.director.nombre;
            campoId.value = datos.director.id_director;
            campoNombre.placeholder = '';
            mensaje.textContent = 'Director asignado automáticamente.';
            mensaje.style.color = '#198754';
        } else {
            campoNombre.value = '';
            campoId.value = '';
            campoNombre.placeholder = 'No existe un director activo asignado';
            mensaje.textContent = datos.message || 'La Secretaría o Dirección seleccionada no tiene un director activo.';
            mensaje.style.color = '#dc3545';
        }
    } catch (error) {
        campoNombre.value = '';
        campoId.value = '';
        campoNombre.placeholder = 'Error al cargar el director';
        mensaje.textContent = error.message;
        mensaje.style.color = '#dc3545';
    }
}

function generarDias() {
    const fechaInput = document.getElementById('fecha_emision').value;
    const diasInput = document.getElementById('dias_movilizacion').value;
    const detalleTextArea = document.getElementById('detalle_dias');

    if (!fechaInput || !diasInput) {
        detalleTextArea.value = '';
        return;
    }

    const nombresDias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    const resultado = [];
    const parts = fechaInput.split('-');
    const fechaBase = new Date(parts[0], parts[1] - 1, parts[2]);
    const dias = parseInt(diasInput, 10);

    for (let i = 0; i < dias; i++) {
        const nuevaFecha = new Date(fechaBase);
        nuevaFecha.setDate(fechaBase.getDate() + i);

        const diaNombre = nombresDias[nuevaFecha.getDay()];
        const numeroDia = nuevaFecha.getDate();
        resultado.push(diaNombre + ' ' + numeroDia);
    }

    detalleTextArea.value = resultado.join(', ');
}

// Impide guardar una orden sin un director obtenido automáticamente.
document.getElementById('formOrden').addEventListener('submit', function (event) {
    const idChofer = document.getElementById('id_chofer').value;
    const idVehiculo = document.getElementById('id_vehiculo').value;
    const idDireccion = document.getElementById('id_direccion').value;
    const idDirector = document.getElementById('id_director').value;

    if (!idChofer) {
        event.preventDefault();
        alert('Seleccione un chofer.');
        document.getElementById('id_chofer').focus();
        return;
    }

    if (!idVehiculo) {
        event.preventDefault();
        alert('El chofer seleccionado no tiene un vehículo asignado.');
        document.getElementById('id_chofer').focus();
        return;
    }

    if (!idDireccion) {
        event.preventDefault();
        alert('Seleccione una Secretaría o Dirección.');
        document.getElementById('id_direccion').focus();
        return;
    }

    if (!idDirector) {
        event.preventDefault();
        alert('La Secretaría o Dirección seleccionada no tiene un director responsable activo.');
    }
});
</script>
</body>
</html>
