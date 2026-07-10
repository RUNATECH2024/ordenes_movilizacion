<?php
// directores/nuevo.php
session_start();
if (!isset($_SESSION['usuario'])) { 
    header("Location: ../auth/login.php"); 
    exit; 
}
require_once '../includes/conexion.php';

$error = '';

try {
    $direcciones = $pdo->query("SELECT id_direccion, nombre FROM direcciones WHERE estado = 'ACTIVO' ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
    $empleados = $pdo->query("SELECT id_empleado, cedula, CONCAT(primer_apellido, ' ', COALESCE(segundo_apellido, ''), ' ', primer_nombre) AS nombre_completo FROM empleados WHERE estado = 'ACTIVO' ORDER BY primer_apellido ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de catálogo: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_direccion = !empty($_POST['id_direccion']) ? (int)$_POST['id_direccion'] : null;
    $id_empleado  = !empty($_POST['id_empleado']) ? (int)$_POST['id_empleado'] : null; // Admite NULL (Vacante)
    $estado       = $_POST['estado'] ?? 'ACTIVO';

    if (!$id_direccion) {
        $error = "El campo Dirección / Área de Gobierno es obligatorio.";
    } else {
        try {
            // Validación preventiva: Evitar dos directores ACTIVOS asignados a la misma dirección
            if ($estado === 'ACTIVO') {
                $checkActive = $pdo->prepare("SELECT COUNT(*) FROM directores WHERE id_direccion = :id_direccion AND estado = 'ACTIVO'");
                $checkActive->execute([':id_direccion' => $id_direccion]);
                if ($checkActive->fetchColumn() > 0) {
                    throw new Exception("Esta Dirección/Área ya cuenta con un Director activo asignado. Modifique el anterior a INACTIVO antes de proceder.");
                }
            }

            $stmt = $pdo->prepare("INSERT INTO directores (id_direccion, id_empleado, estado) VALUES (:id_direccion, :id_empleado, :estado)");
            $stmt->execute([
                ':id_direccion' => $id_direccion,
                ':id_empleado'  => $id_empleado,
                ':estado'       => $estado
            ]);

            header("Location: index.php?ok=1");
            exit;
        } catch (PDOException $e) {
            // CAPTURA EL TRIGGER DE POSTGRESQL PARA EL PROCESO INVERSO
            if ($e->getCode() == 'P0001') {
                $error = "Operación denegada: El funcionario seleccionado ya es JEFE activo de un departamento menor y no puede asumir como Director.";
            } else {
                $error = "Error al guardar en el sistema: " . $e->getMessage();
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SGA - Designar Director</title>
    <link rel="stylesheet" href="../assets/estilos.css">
</head>
<body>
<div class="container" style="max-width: 600px;">
    <h2>➕ Designar Nuevo Cargo Directivo</h2>
    <hr>

    <?php if($error): ?>
        <div style="background-color: #fed7d7; color: #9b2c2c; padding: 12px; margin-bottom: 20px; border-radius: 4px; font-weight: 500;">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" style="display: flex; flex-direction: column; gap: 15px;">
        <div class="form-group">
            <label style="font-weight: bold; color: #2d3748;">Dirección / Área de Gobierno</label>
            <select name="id_direccion" required style="width: 100%; padding: 8px; margin-top: 5px;">
                <option value="">-- Seleccione el Área --</option>
                <?php foreach ($direcciones as $dir): ?>
                    <option value="<?= $dir['id_direccion'] ?>" <?= isset($_POST['id_direccion']) && $_POST['id_direccion'] == $dir['id_direccion'] ? 'selected' : '' ?>><?= htmlspecialchars($dir['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label style="font-weight: bold; color: #2d3748;">Funcionario Designado (Opcional)</label>
            <select name="id_empleado" style="width: 100%; padding: 8px; margin-top: 5px;">
                <option value="">-- Dejar Vacante de Momento --</option>
                <?php foreach ($empleados as $emp): ?>
                    <option value="<?= $emp['id_empleado'] ?>" <?= isset($_POST['id_empleado']) && $_POST['id_empleado'] == $emp['id_empleado'] ? 'selected' : '' ?>>[<?= htmlspecialchars($emp['cedula']) ?>] <?= htmlspecialchars($emp['nombre_completo']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label style="font-weight: bold; color: #2d3748;">Estado Inicial</label>
            <select name="estado" style="width: 100%; padding: 8px; margin-top: 5px;">
                <option value="ACTIVO" <?= isset($_POST['estado']) && $_POST['estado'] === 'ACTIVO' ? 'selected' : '' ?>>ACTIVO (Ejerciendo)</option>
                <option value="INACTIVO" <?= isset($_POST['estado']) && $_POST['estado'] === 'INACTIVO' ? 'selected' : '' ?>>INACTIVO (En Espera / Suspendido)</option>
            </select>
        </div>

        <div style="display: flex; gap: 10px; margin-top: 10px;">
            <button type="submit" class="btn btn-success" style="flex: 1; padding: 10px; font-weight: bold; cursor: pointer;">💾 Asignar Director</button>
            <a href="index.php" class="btn btn-secondary" style="flex: 1; text-align:center; line-height:2.5; text-decoration:none;">Cancelar</a>
        </div>
    </form>
</div>
</body>
</html>