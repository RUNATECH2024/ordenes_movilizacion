<?php
// jefaturas/editar.php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../includes/conexion.php';

$id_jefatura = $_GET['id'] ?? null;
if (!$id_jefatura || !is_numeric($id_jefatura)) { die("ID no válido"); }

$error = '';

try {
    $stmt_jef = $pdo->prepare("SELECT * FROM jefaturas WHERE id_jefatura = ?");
    $stmt_jef->execute([$id_jefatura]);
    $jefatura = $stmt_jef->fetch(PDO::FETCH_ASSOC);
    if (!$jefatura) { die("Jefatura no encontrada"); }

    // CORRECCIÓN: Obtenemos el ID del jefe actual consultando el historial activo
    $stmt_actual_jefe = $pdo->prepare("SELECT id_empleado_jefe FROM historial_jefaturas WHERE id_jefatura = ? AND estado = 'ACTIVO' LIMIT 1");
    $stmt_actual_jefe->execute([$id_jefatura]);
    $jefatura['id_empleado_jefe'] = $stmt_actual_jefe->fetchColumn() ?: null;

    $direcciones = $pdo->query("SELECT id_direccion, nombre FROM direcciones WHERE estado = 'ACTIVO' ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
    $empleados = $pdo->query("SELECT id_empleado, cedula, CONCAT(primer_apellido, ' ', COALESCE(segundo_apellido, ''), ' ', primer_nombre) AS nombre_completo FROM empleados WHERE estado = 'ACTIVO' ORDER BY primer_apellido ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_direccion     = !empty($_POST['id_direccion']) ? (int)$_POST['id_direccion'] : null;
    $codigo           = !empty($_POST['codigo']) ? trim($_POST['codigo']) : null;
    $nombre           = !empty($_POST['nombre']) ? trim($_POST['nombre']) : null;
    $descripcion      = !empty($_POST['descripcion']) ? trim($_POST['descripcion']) : null;
    $estado           = $_POST['estado'] ?? 'ACTIVO';
    $id_empleado_jefe = !empty($_POST['id_empleado_jefe']) ? (int)$_POST['id_empleado_jefe'] : null;

    if (!$id_direccion || !$codigo || !$nombre) {
        $error = "Los campos Dirección, Código y Nombre son obligatorios.";
    } else {
        try {
            $pdo->beginTransaction(); // Iniciamos transacción para asegurar los datos

            // 1. CORRECCIÓN: UPDATE sin la columna inexistente 'id_empleado_jefe'
            $stmt = $pdo->prepare("
                UPDATE jefaturas 
                SET id_direccion = :id_direccion, codigo = :codigo, nombre = :nombre, 
                    descripcion = :descripcion, estado = :estado
                WHERE id_jefatura = :id_jefatura
            ");
            $stmt->execute([
                ':id_direccion' => $id_direccion,
                ':codigo'       => $codigo,
                ':nombre'       => $nombre,
                ':descripcion'  => $descripcion,
                ':estado'       => $estado,
                ':id_jefatura'  => $id_jefatura
            ]);

            // 2. LÓGICA DE ACTUALIZACIÓN DEL HISTORIAL DE JEFES
            if ($jefatura['id_empleado_jefe'] != $id_empleado_jefe) {
                
                // Desactivamos al jefe anterior de esta jefatura
                $pdo->prepare("UPDATE historial_jefaturas SET estado = 'INACTIVO', fecha_fin = CURRENT_DATE WHERE id_jefatura = ? AND estado = 'ACTIVO'")
                    ->execute([$id_jefatura]);

                // Si se asignó un nuevo jefe (y no se dejó vacante), creamos su registro activo
                if ($id_empleado_jefe) {
                    // Desactivamos si este nuevo jefe tenía otra jefatura activa antes
                    $pdo->prepare("UPDATE historial_jefaturas SET estado = 'INACTIVO', fecha_fin = CURRENT_DATE WHERE id_empleado_jefe = ? AND estado = 'ACTIVO'")
                        ->execute([$id_empleado_jefe]);

                    $sql_ins_jef = "INSERT INTO historial_jefaturas (id_jefatura, id_empleado_jefe, fecha_inicio, estado, created_at) 
                                    VALUES (?, ?, CURRENT_DATE, 'ACTIVO', CURRENT_TIMESTAMP)";
                    $pdo->prepare($sql_ins_jef)->execute([$id_jefatura, $id_empleado_jefe]);
                }
            }

            $pdo->commit();
            header("Location: index.php?ok=1");
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            // CAPTURA DEL TRIGGER PERSONALIZADO DE POSTGRESQL
            if ($e->getCode() == 'P0001' || strpos($e->getMessage(), 'Director') !== false) {
                $error = "Operación denegada: El empleado seleccionado ya está asignado como DIRECTOR activo en el sistema y no puede duplicar funciones como Jefe.";
            } else {
                $error = "Error al actualizar: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SGA - Editar Jefatura</title>
    <link rel="stylesheet" href="../assets/estilos.css">
</head>
<body>
<div class="container" style="max-width: 650px;">
    <h2>✏️ Editar Jefatura Departamental</h2>
    <hr>

    <?php if($error): ?>
        <div style="background-color: #fed7d7; color: #9b2c2c; padding: 12px; margin-bottom: 20px; border-radius: 4px; font-weight: 500;">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form class="form-dos-columnas" method="POST">
        <div class="form-group">
            <label>Dirección Macro Perteneciente</label>
            <select name="id_direccion" required style="width: 100%; padding: 8px;">
                <?php foreach ($direcciones as $dir): ?>
                    <option value="<?= $dir['id_direccion'] ?>" <?= $jefatura['id_direccion'] == $dir['id_direccion'] ? 'selected' : '' ?>><?= htmlspecialchars($dir['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Código</label>
            <input type="text" name="codigo" value="<?= htmlspecialchars($jefatura['codigo']) ?>" required style="padding: 8px;">
        </div>

        <div class="form-group" style="grid-column: span 2;">
            <label>Nombre de la Jefatura</label>
            <input type="text" name="nombre" value="<?= htmlspecialchars($jefatura['nombre']) ?>" required style="padding: 8px; width: 100%;">
        </div>

        <div class="form-group" style="grid-column: span 2;">
            <label>Jefe Responsable</label>
            <select name="id_empleado_jefe" style="width: 100%; padding: 8px;">
                <option value="">-- Dejar Vacante --</option>
                <?php foreach ($empleados as $emp): ?>
                    <option value="<?= $emp['id_empleado'] ?>" <?= $jefatura['id_empleado_jefe'] == $emp['id_empleado'] ? 'selected' : '' ?>>[<?= htmlspecialchars($emp['cedula']) ?>] <?= htmlspecialchars($emp['nombre_completo']) ?></option>
                <?php endforeach; ?>
            </select>
            <small style="color: #718096; display: block; margin-top: 4px;">Selecciona "-- Dejar Vacante --" si necesitas quitar a este empleado para promoverlo a Director.</small>
        </div>

        <div class="form-group" style="grid-column: span 2;">
            <label>Estado</label>
            <select name="estado" style="width: 100%; padding: 8px;">
                <option value="ACTIVO" <?= $jefatura['estado'] === 'ACTIVO' ? 'selected' : '' ?>>ACTIVO</option>
                <option value="INACTIVO" <?= $jefatura['estado'] === 'INACTIVO' ? 'selected' : '' ?>>INACTIVO</option>
            </select>
        </div>

        <div class="form-group" style="grid-column: span 2;">
            <label>Descripción</label>
            <textarea name="descripcion" rows="3" style="width: 100%; padding: 8px;"><?= htmlspecialchars($jefatura['descripcion'] ?? '') ?></textarea>
        </div>

        <div class="form-buttons ancho-completo" style="grid-column: span 2; display: flex; gap: 10px; margin-top: 10px;">
            <button type="submit" class="btn btn-success" style="flex: 1; padding: 10px; font-weight: bold; cursor: pointer;">💾 Guardar Cambios</button>
            <a href="index.php" class="btn btn-secondary" style="flex: 1; text-align:center; line-height:2.5; text-decoration:none;">Cancelar</a>
        </div>
    </form>
</div>
</body>
</html>