<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../includes/conexion.php';

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    die("ID de chofer no válido.");
}

try {
    // 1. Obtener los datos del chofer específico
    $stmt = $pdo->prepare("SELECT * FROM choferes WHERE id_chofer = ?");
    $stmt->execute([$id]);
    $chofer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$chofer) {
        die("Chofer no encontrado.");
    }

    // 2. Obtener todas las direcciones activas para el select relacional
    $stmt_dir = $pdo->query("SELECT id_direccion, nombre FROM direcciones WHERE estado = 'ACTIVO' ORDER BY nombre");
    $direcciones = $stmt_dir->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error en la base de datos: " . $e->getMessage());
}

function fechaInput($fecha) {
    return !empty($fecha) ? date('Y-m-d', strtotime($fecha)) : '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Chofer</title>
    <link rel="stylesheet" href="../assets/estilos.css">
</head>
<body>

<header>
    <h2>✏️ Editar Chofer</h2>
    <nav>
        <a href="index.php">← Listado de Choferes</a>
        <a href="../panel_administracion.php">🏠 Panel</a>
    </nav>
</header>

<main>

<form class="form-dos-columnas" action="actualizar.php" method="POST" enctype="multipart/form-data">

    <input type="hidden" name="id" value="<?= $chofer['id_chofer'] ?>">

    <div class="form-group">
        <label>Nombres</label>
        <input type="text" name="nombres" value="<?= htmlspecialchars($chofer['nombres'] ?? '') ?>" required>
    </div>

    <div class="form-group">
        <label>Apellidos</label>
        <input type="text" name="apellidos" value="<?= htmlspecialchars($chofer['apellidos'] ?? '') ?>" required>
    </div>

    <div class="form-group">
        <label>Cédula</label>
        <input type="text" name="cedula" id="cedula" maxlength="10" value="<?= htmlspecialchars($chofer['cedula'] ?? '') ?>" required>
    </div>

    <div class="form-group">
        <label>Fecha Nacimiento</label>
        <input type="date" name="fecha_nacimiento" value="<?= fechaInput($chofer['fecha_nacimiento']) ?>">
    </div>

    <div class="form-group">
        <label>Dirección Domicilio (Texto)</label>
        <input type="text" name="direccion" value="<?= htmlspecialchars($chofer['direccion'] ?? '') ?>">
    </div>

    <div class="form-group">
        <label>Teléfono</label>
        <input type="text" name="telefono" value="<?= htmlspecialchars($chofer['telefono'] ?? '') ?>">
    </div>

    <div class="form-group">
        <label>Correo</label>
        <input type="email" name="correo" value="<?= htmlspecialchars($chofer['correo'] ?? '') ?>">
    </div>

    <div class="form-group">
        <label>Fotografía Actual</label><br>
        <?php if(!empty($chofer['foto']) && file_exists("../uploads/choferes/" . $chofer['foto'])): ?>
            <img src="../uploads/choferes/<?= htmlspecialchars($chofer['foto']) ?>" width="140" style="border-radius:8px; border:1px solid #ccc; object-fit: cover;">
        <?php else: ?>
            <p style="color: #666; font-style: italic;">Sin fotografía</p>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label>Cambiar Fotografía</label>
        <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp">
    </div>

    <div class="form-group">
        <label>Tipo Licencia</label>
        <select name="tipo_licencia" required>
            <option value="">Seleccione</option>
            <?php
            $tipos = ['A','B','C','D','E','F','G','CHOFER PROFESIONAL'];
            foreach($tipos as $tipo){
                $selected = (($chofer['tipo_licencia'] ?? '') == $tipo) ? 'selected' : '';
                echo "<option value='$tipo' $selected>$tipo</option>";
            }
            ?>
        </select>
    </div>

    <div class="form-group">
        <label>Número Licencia</label>
        <input type="text" name="numero_licencia" value="<?= htmlspecialchars($chofer['numero_licencia'] ?? '') ?>">
    </div>

    <div class="form-group">
        <label>Fecha Emisión Licencia</label>
        <input type="date" name="fecha_emision_licencia" value="<?= fechaInput($chofer['fecha_emision_licencia']) ?>">
    </div>

    <div class="form-group">
        <label>Fecha Caducidad Licencia</label>
        <input type="date" name="fecha_caducidad_licencia" value="<?= fechaInput($chofer['fecha_caducidad_licencia']) ?>">
    </div>

    <div class="form-group">
        <label>Cargo</label>
        <input type="text" name="cargo" value="<?= htmlspecialchars($chofer['cargo'] ?? '') ?>">
    </div>

    <div class="form-group">
        <label>Dirección Institucional (Relación Tabla)</label>
        <select name="id_direccion">
            <option value="">Seleccione una Dirección</option>
            <?php foreach ($direcciones as $d): ?>
                <?php $selected = (($chofer['id_direccion'] ?? null) == $d['id_direccion']) ? 'selected' : ''; ?>
                <option value="<?= $d['id_direccion'] ?>" <?= $selected ?>>
                    <?= htmlspecialchars($d['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Código Empleado</label>
        <input type="text" name="codigo_empleado" value="<?= htmlspecialchars($chofer['codigo_empleado'] ?? '') ?>">
    </div>

    <div class="form-group">
        <label>Fecha Ingreso</label>
        <input type="date" name="fecha_ingreso" value="<?= fechaInput($chofer['fecha_ingreso']) ?>">
    </div>

    <div class="form-group">
        <label>Contacto Emergencia</label>
        <input type="text" name="contacto_emergencia" value="<?= htmlspecialchars($chofer['contacto_emergencia'] ?? '') ?>">
    </div>

    <div class="form-group">
        <label>Teléfono Emergencia</label>
        <input type="text" name="telefono_emergencia" value="<?= htmlspecialchars($chofer['telefono_emergencia'] ?? '') ?>">
    </div>

    <div class="form-group">
        <label>Grupo Sanguíneo</label>
        <input type="text" name="grupo_sanguineo" value="<?= htmlspecialchars($chofer['grupo_sanguineo'] ?? '') ?>">
    </div>

    <div class="form-group">
        <label>Estado</label>
        <select name="estado">
            <option value="ACTIVO" <?= (($chofer['estado'] ?? '') == 'ACTIVO') ? 'selected' : '' ?>>ACTIVO</option>
            <option value="INACTIVO" <?= (($chofer['estado'] ?? '') == 'INACTIVO') ? 'selected' : '' ?>>INACTIVO</option>
        </select>
    </div>

    <div class="form-group" style="grid-column: span 2;">
        <label>Observaciones</label>
        <textarea name="observaciones" rows="4"><?= htmlspecialchars($chofer['observaciones'] ?? '') ?></textarea>
    </div>

    <div class="form-buttons" style="grid-column: span 2;">
        <button type="submit">💾 Guardar Cambios</button>
        <a href="index.php" class="btn-cancelar">❌ Cancelar</a>
    </div>

</form>

</main>

<script>
document.querySelector("form").addEventListener("submit", function(e){
    let cedula = document.getElementById("cedula").value.trim();
    if(!/^[0-9]{10}$/.test(cedula)){
        alert("La cédula debe tener exactamente 10 dígitos numéricos.");
        document.getElementById("cedula").focus();
        e.preventDefault();
    }
});
</script>

</body>
</html>