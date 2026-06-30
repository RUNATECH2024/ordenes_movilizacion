<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Nuevo Chofer</title>
<link rel="stylesheet" href="../assets/estilos.css">
</head>

<body>

<header>
    <h2>➕ Registrar Nuevo Chofer</h2>
    <nav>
        <a href="index.php">← Listado de Choferes</a>
        <a href="../panel_administracion.php">🏠 Panel</a>
        <a href="../auth/logout.php">🚪 Cerrar sesión</a>
    </nav>
</header>

<main>

<form class="form-dos-columnas"
      action="insertar.php"
      method="post"
      onsubmit="return validarFormulario()">

<!-- DATOS BÁSICOS -->
<div class="form-group">
    <label>Nombres</label>
    <input type="text" name="nombres" required>
</div>

<div class="form-group">
    <label>Apellidos</label>
    <input type="text" name="apellidos" required>
</div>

<div class="form-group">
    <label>Cédula</label>
    <input type="text" name="cedula" id="cedula" maxlength="10" required>
</div>

<div class="form-group">
    <label>Fecha Nacimiento</label>
    <input type="date" name="fecha_nacimiento">
</div>

<div class="form-group">
    <label>Dirección</label>
    <input type="text" name="direccion">
</div>

<div class="form-group">
    <label>Teléfono</label>
    <input type="text" name="telefono">
</div>

<div class="form-group">
    <label>Correo</label>
    <input type="email" name="correo">
</div>

<!-- LICENCIA -->
<div class="form-group">
    <label>Tipo Licencia</label>
    <select name="tipo_licencia" required>
        <option value="">Seleccione</option>
        <option value="A">A</option>
        <option value="B">B</option>
        <option value="C">C</option>
        <option value="D">D</option>
        <option value="E">E</option>
        <option value="G">G</option>
    </select>
</div>

<div class="form-group">
    <label>Número Licencia</label>
    <input type="text" name="numero_licencia">
</div>

<div class="form-group">
    <label>Fecha Emisión</label>
    <input type="date" name="fecha_emision_licencia">
</div>

<div class="form-group">
    <label>Fecha Caducidad</label>
    <input type="date" name="fecha_caducidad_licencia">
</div>

<!-- LABORAL -->
<div class="form-group">
    <label>Cargo</label>
    <input type="text" name="cargo">
</div>

<div class="form-group">
    <label>Departamento</label>
    <input type="text" name="departamento">
</div>

<div class="form-group">
    <label>Código Empleado</label>
    <input type="text" name="codigo_empleado">
</div>

<div class="form-group">
    <label>Fecha Ingreso</label>
    <input type="date" name="fecha_ingreso">
</div>

<!-- EMERGENCIA -->
<div class="form-group">
    <label>Contacto Emergencia</label>
    <input type="text" name="contacto_emergencia">
</div>

<div class="form-group">
    <label>Teléfono Emergencia</label>
    <input type="text" name="telefono_emergencia">
</div>

<div class="form-group">
    <label>Grupo Sanguíneo</label>
    <input type="text" name="grupo_sanguineo">
</div>

<!-- ESTADO -->
<div class="form-group">
    <label>Estado</label>
    <select name="estado">
        <option value="ACTIVO">ACTIVO</option>
        <option value="INACTIVO">INACTIVO</option>
    </select>
</div>

<div class="form-group" style="grid-column: span 2;">
    <label>Observaciones</label>
    <textarea name="observaciones" rows="4"></textarea>
</div>

<div class="form-buttons">
    <button type="submit">💾 Guardar Chofer</button>
    <a href="index.php">❌ Cancelar</a>
</div>

</form>

</main>

<script>
function validarFormulario() {
    const cedula = document.getElementById('cedula').value;
    if (cedula.length !== 10 || !/^\d+$/.test(cedula)) {
        alert("La cédula debe tener 10 dígitos");
        return false;
    }
    return true;
}
</script>

</body>
</html>