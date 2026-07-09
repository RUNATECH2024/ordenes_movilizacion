<?php
// jefaturas/nuevo.php
session_start();
if (!isset($_SESSION['usuario'])) { header("Location: ../auth/login.php"); exit; }
require_once '../includes/conexion.php';

$error = '';

try {
    $direcciones = $pdo->query("SELECT id_direccion, nombre FROM direcciones WHERE estado = 'ACTIVO' ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
    $empleados = $pdo->query("SELECT id_empleado, cedula, CONCAT(primer_apellido, ' ', primer_nombre) AS nombre_completo FROM empleados WHERE estado = 'ACTIVO' ORDER BY primer_apellido ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_direccion     = !empty($_POST['id_direccion']) ? (int)$_POST['id_direccion'] : null;
    $codigo           = !empty($_POST['codigo']) ? trim($_POST['codigo']) : null;
    $nombre           = !empty($_POST['nombre']) ? trim($_POST['nombre']) : null;
    $descripcion      = !empty($_POST['descripcion']) ? trim($_POST['descripcion']) : null;
    $id_empleado_jefe = !empty($_POST['id_empleado_jefe']) ? (int)$_POST['id_empleado_jefe'] : null; // Admite NULL

    if (!$id_direccion || !$codigo || !$nombre) {
        $error = "Los campos Dirección, Código y Nombre son obligatorios.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO jefaturas (id_direccion, codigo, nombre, descripcion, id_empleado_jefe, estado) VALUES (:id_direccion, :codigo, :nombre, :descripcion, :id_empleado_jefe, 'ACTIVO')");
            $stmt->execute([
                ':id_direccion'     => $id_direccion,
                ':codigo'           => $codigo,
                ':nombre'           => $nombre,
                ':descripcion'      => $descripcion,
                ':id_empleado_jefe' => $id_empleado_jefe
            ]);
            header("Location: index.php?ok=1");
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 'P0001') {
                $error = "Operación denegada: El empleado ya es DIRECTOR activo y no puede ser Jefe.";
            } else {
                $error = "Error al guardar: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SGA - Nueva Jefatura</title>
    <link rel="stylesheet" href="../assets/estilos.css">
</head>
<body>
<div class="container" style="max-width: 650px;">
    <h2>➕ Registrar Nueva Jefatura</h2>
    <hr>
    <?php if($error): ?>
        <div style="background-color: #fed7d7; color: #9b2c2c; padding: 12px; margin-bottom: 20px; border-radius: 4px;">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" style="display: flex; flex-direction: column; gap: 15px;">
        <div>
            <label>Dirección Macro</label>
            <select name="id_direccion" required style="width: 100%; padding: 8px;">
                <option value="">-- Seleccione Dirección --</option>
                <?php foreach ($direcciones as $dir): ?>
                    <option value="<?= $dir['id_direccion'] ?>"><?= htmlspecialchars($dir['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Código de Jefatura</label>
            <input type="text" name="codigo" required style="width: 100%; padding: 8px;">
        </div>
        <div>
            <label>Nombre de la Jefatura</label>
            <input type="text" name="nombre" required style="width: 100%; padding: 8px;">
        </div>
        <div>
            <label>Asignar Jefe Inicial (Opcional)</label>
            <select name="id_empleado_jefe" style="width: 100%; padding: 8px;">
                <option value="">-- Dejar Vacante Temporalmente --</option>
                <?php foreach ($empleados as $emp): ?>
                    <option value="<?= $emp['id_empleado'] ?>">[<?= htmlspecialchars($emp['cedula']) ?>] <?= htmlspecialchars($emp['nombre_completo']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Descripción</label>
            <textarea name="descripcion" rows="3" style="width: 100%; padding: 8px;"></textarea>
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-success" style="flex:1; padding: 10px;">Guardar</button>
            <a href="index.php" class="btn btn-secondary" style="flex:1; text-align:center; line-height:2.3; text-decoration:none;">Cancelar</a>
        </div>
    </form>
</div>
</body>
</html>