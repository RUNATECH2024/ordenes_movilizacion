<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../includes/conexion.php';

try {
    // Obtener direcciones activas para el select relacional
    $direcciones = $pdo->query("
        SELECT id_direccion, nombre
        FROM direcciones
        WHERE estado='ACTIVO'
        ORDER BY nombre
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al cargar las direcciones: " . $e->getMessage());
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
        <?php if (isset($_GET['error'])): ?>
            <div style="background-color: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
                ⚠️ Hubo un error al guardar el chofer. Por favor, verifique los datos.
            </div>
        <?php endif; ?>

        <form class="form-dos-columnas" action="insertar.php" method="POST" enctype="multipart/form-data" onsubmit="return validarFormulario()">
            
            <div class="form-group">
                <label>Nombres</label>
                <input type="text" name="nombres" required>
            </div>
            <div class="form-group">
                <label>Apellidos</label>
                <input type="text" name="apellidos" required>
            </div>
            <div class="form-group">
                <label>Cédula / Identificación</label>
                <input type="text" name="cedula" id="cedula" maxlength="10" required>
            </div>
            <div class="form-group">
                <label>Fecha Nacimiento</label>
                <input type="date" name="fecha_nacimiento">
            </div>
            <div class="form-group">
                <label>Dirección Domicilio (Texto)</label>
                <input type="text" name="direccion">
            </div>
            <div class="form-group">
                <label>Teléfono</label>
                <input type="text" name="telefono">
            </div>
            <div class="form-group">
                <label>Correo Electrónico</label>
                <input type="email" name="correo">
            </div>
            <div class="form-group">
                <label>Fotografía</label>
                <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp">
            </div>

            <div class="form-group">
                <label>Tipo Licencia</label>
                <select name="tipo_licencia" required>
                    <option value="">Seleccione</option>
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                    <option value="D">D</option>
                    <option value="E">E</option>
                    <option value="F">F</option>
                    <option value="G">G</option>
                    <option value="CHOFER PROFESIONAL">CHOFER PROFESIONAL</option>
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

            <div class="form-group">
                <label>Cargo</label>
                <input type="text" name="cargo">
            </div>
            <div class="form-group">
                <label>Dirección Institucional (Relación Tabla)</label>
                <select name="id_direccion">
                    <option value="">Seleccione una Dirección de la lista</option>
                    <?php foreach ($direcciones as $d): ?>
                        <option value="<?= $d['id_direccion'] ?>"><?= htmlspecialchars($d['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Código Empleado</label>
                <input type="text" name="codigo_empleado">
            </div>
            <div class="form-group">
                <label>Fecha Ingreso</label>
                <input type="date" name="fecha_ingreso">
            </div>

            <div class="form-group">
                <label>Contacto Emergencia (Nombre)</label>
                <input type="text" name="contacto_emergencia">
            </div>
            <div class="form-group">
                <label>Teléfono Emergencia</label>
                <input type="text" name="telefono_emergencia">
            </div>
            <div class="form-group">
                <label>Grupo Sanguíneo</label>
                <input type="text" name="grupo_sanguineo" placeholder="Ej: ORH+ / A+">
            </div>

            <div class="form-group">
                <label>Estado</label>
                <select name="estado">
                    <option value="ACTIVO">ACTIVO</option>
                    <option value="INACTIVO">INACTIVO</option>
                </select>
            </div>

            <div class="form-group" style="grid-column: span 2;">
                <label>Observaciones / Detalles adicionales</label>
                <textarea name="observaciones" rows="4"></textarea>
            </div>

            <div class="form-buttons">
                <button type="submit">💾 Guardar Chofer</button>
                <a href="index.php" class="btn-cancelar">❌ Cancelar</a>
            </div>
        </form>
    </main>

    <script>
    function validarFormulario() {
        let cedula = document.getElementById("cedula").value.trim();
        // Validación básica de 10 números (Ecuador standard)
        if (!/^[0-9]{10}$/.test(cedula)) {
            alert("La cédula debe contener exactamente 10 dígitos numéricos.");
            document.getElementById("cedula").focus();
            return false;
        }
        return true;
    }
    </script>
</body>
</html>