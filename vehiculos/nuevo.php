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
    // Obtener lista de direcciones institucionales activas
    $sql_direcciones = "SELECT id_direccion, nombre FROM direcciones WHERE estado = 'ACTIVO' ORDER BY nombre ASC";
    $stmt_direcciones = $pdo->query($sql_direcciones);
    $direcciones = $stmt_direcciones->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error al cargar datos del formulario: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Vehículo</title>
    <link rel="stylesheet" href="../assets/estilos.css">
</head>
<body>

    <h2>🚗 Nuevo Vehículo</h2>

    <form class="form-dos-columnas" action="insertar.php" method="POST" enctype="multipart/form-data" onsubmit="return validarFormulario()">

        <div class="form-group">
            <label>Código Institucional:</label>
            <input type="text" name="codigo_institucional" required>
        </div>

        <div class="form-group">
            <label>Placa:</label>
            <input type="text" name="placa" required>
        </div>

        <div class="form-group">
            <label>Matrícula:</label>
            <input type="text" name="matricula" required>
        </div>

        <div class="form-group">
            <label>Chasis:</label>
            <input type="text" name="chasis" required>
        </div>

        <div class="form-group">
            <label>Motor:</label>
            <input type="text" name="motor" required>
        </div>

        <div class="form-group">
            <label>Marca:</label>
            <input type="text" name="marca">
        </div>

        <div class="form-group">
            <label>Modelo:</label>
            <input type="text" name="modelo">
        </div>

        <div class="form-group">
            <label>Tipo:</label>
            <input type="text" name="tipo">
        </div>

        <div class="form-group">
            <label>Unidad:</label>
            <input type="text" name="unidad">
        </div>

        <div class="form-group">
            <label>Color:</label>
            <input type="text" name="color">
        </div>

        <div class="form-group">
            <label>Año:</label>
            <input type="number" name="anio" id="anio" min="1900" max="<?= date('Y') ?>">
        </div>

        <div class="form-group">
            <label>Dirección Institucional:</label>
            <select name="id_direccion">
                <option value="">Seleccione una Dirección</option>
                <?php foreach ($direcciones as $dir): ?>
                    <option value="<?= $dir['id_direccion'] ?>">
                        <?= htmlspecialchars($dir['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group ancho-completo">
            <label>Descripción del Vehículo:</label>
            <textarea name="descripcion_vehiculo" rows="4"></textarea>
        </div>

        <div class="form-group ancho-completo">
            <label>Foto del Vehículo:</label>
            <input type="file" name="foto_vehiculo" accept=".jpg,.jpeg,.png,.webp">
        </div>

        <div class="form-buttons">
            <button type="submit">💾 Guardar Vehículo</button>
            <a href="index.php" class="btn btn-secondary">← Regresar</a>
        </div>

    </form>

    <script>
    function validarFormulario() {
        let anio = document.getElementById('anio').value;
        let actual = new Date().getFullYear();

        if (anio && (anio < 1900 || anio > actual)) {
            alert("Ingrese un año válido entre 1900 y " + actual);
            document.getElementById('anio').focus();
            return false;
        }
        return true;
    }
    </script>

</body>
</html>