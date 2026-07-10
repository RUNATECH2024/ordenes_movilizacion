<?php
// directores/editar.php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../includes/conexion.php';

$id_director = $_GET['id'] ?? null;
if (!$id_director || !is_numeric($id_director)) { die("ID de director no válido"); }

$error = '';

try {
    // Obtener el registro actual de la asignación del director
    $stmt_dir = $pdo->prepare("SELECT * FROM directores WHERE id_director = ?");
    $stmt_dir->execute([$id_director]);
    $director_actual = $stmt_dir->fetch(PDO::FETCH_ASSOC);
    if (!$director_actual) { die("Asignación de dirección no encontrada"); }

    // Cargar catálogos de Direcciones y Empleados Activos
    $direcciones = $pdo->query("SELECT id_direccion, nombre FROM direcciones WHERE estado = 'ACTIVO' ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
    $empleados = $pdo->query("SELECT id_empleado, cedula, CONCAT(primer_apellido, ' ', COALESCE(segundo_apellido, ''), ' ', primer_nombre) AS nombre_completo FROM empleados WHERE estado = 'ACTIVO' ORDER BY primer_apellido ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de catálogo: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_direccion = !empty($_POST['id_direccion']) ? (int)$_POST['id_direccion'] : null;
    $id_empleado  = !empty($_POST['id_empleado']) ? (int)$_POST['id_empleado'] : null; // Permite NULL si se desea dejar vacante
    $estado       = $_POST['estado'] ?? 'ACTIVO';

    if (!$id_direccion) {
        $error = "El campo Dirección / Área de Gobierno es obligatorio.";
    } else {
        try {
            // Validación preventiva: Evitar duplicidad de directores ACTIVOS en una misma dirección
            if ($estado === 'ACTIVO') {
                $checkActive = $pdo->prepare("SELECT COUNT(*) FROM directores WHERE id_direccion = :id_direccion AND estado = 'ACTIVO' AND id_director <> :id_director");
                $checkActive->execute([
                    ':id_direccion' => $id_direccion,
                    ':id_director'  => $id_director
                ]);
                if ($checkActive->fetchColumn() > 0) {
                    throw new Exception("Esta Dirección/Área ya cuenta con un Director activo asignado. Modifique la otra asignación a INACTIVO antes de proceder.");
                }
            }

            $stmt = $pdo->prepare("
                UPDATE directores 
                SET id_direccion = :id_direccion, 
                    id_empleado = :id_empleado, 
                    estado = :estado
                WHERE id_director = :id_director
            ");
            $stmt->execute([
                ':id_direccion' => $id_direccion,
                ':id_empleado'  => $id_empleado, // Guarda el ID o NULL en Postgres
                ':estado'       => $estado,
                ':id_director'  => $id_director
            ]);

            header("Location: index.php?ok=1");
            exit;
        } catch (PDOException $e) {
            // CAPTURA EL TRIGGER DE EXCEPCIÓN DE POSTGRESQL (validar_jefe_o_director)
            if ($e->getCode() == 'P0001') {
                $error = "Operación denegada: El funcionario seleccionado ya es JEFE activo de un departamento menor y no puede duplicar funciones como Director.";
            } else {
                $error = "Error al actualizar en el sistema: " . $e->getMessage();
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
    <title>SGA - Modificar Director</title>
    <link rel="stylesheet" href="../assets/estilos.css">
</head>
<body>
<div class="container" style="max-width: 600px;">
    <h2>✏️ Modificar Personal Directivo</h2>
    <hr>

    <?php if($error): ?>
        <div style="background-color: #fed7d7; color: #9b2c2c; padding: 12px; margin-bottom: 20px; border-radius: 4px; font-weight: 500;">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" style="display: flex; flex-direction: column; gap: 15px;">
        <div class="form-group">
            <label style="font-weight: bold; color: #2d3748;">Dirección / Área de Gobierno</label>
            <select name="id_direccion" required style="width: 100%; padding: 8px; margin-top: 5px;">
                <?php foreach ($direcciones as $dir): ?>
                    <option value="<?= $dir['id_direccion'] ?>" <?= $director_actual['id_direccion'] == $dir['id_direccion'] ? 'selected' : '' ?>><?= htmlspecialchars($dir['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label style="font-weight: bold; color: #2d3748;">Funcionario Designado</label>
            <select name="id_empleado" style="width: 100%; padding: 8px; margin-top: 5px;">
                <option value="">-- Dejar Vacante (Sin Director Asignado) --</option>
                <?php foreach ($empleados as $emp): ?>
                    <option value="<?= $emp['id_empleado'] ?>" <?= $director_actual['id_empleado'] == $emp['id_empleado'] ? 'selected' : '' ?>>[<?= htmlspecialchars($emp['cedula']) ?>] <?= htmlspecialchars($emp['nombre_completo']) ?></option>
                <?php endforeach; ?>
            </select>
            <small style="color: #718096; display:block; margin-top:4px;">Selecciona la opción vacante si necesitas remover a este empleado de la dirección para asignarlo como Jefe.</small>
        </div>

        <div class="form-group">
            <label style="font-weight: bold; color: #2d3748;">Estado de la Designación</label>
            <select name="estado" style="width: 100%; padding: 8px; margin-top: 5px;">
                <option value="ACTIVO" <?= $director_actual['estado'] === 'ACTIVO' ? 'selected' : '' ?>>ACTIVO (Ejerciendo)</option>
                <option value="INACTIVO" <?= $director_actual['estado'] === 'INACTIVO' ? 'selected' : '' ?>>INACTIVO (Liberado / Ex-Director)</option>
            </select>
            <small style="color: #718096; display:block; margin-top:4px;">Pasar el estado a 'INACTIVO' también libera al empleado ante las restricciones de la base de datos.</small>
        </div>

        <div style="display: flex; gap: 10px; margin-top: 10px;">
            <button type="submit" class="btn btn-success" style="flex: 1; padding: 10px; font-weight: bold; cursor: pointer;">💾 Guardar Cambios</button>
            <a href="index.php" class="btn btn-secondary" style="flex: 1; text-align:center; line-height:2.5; text-decoration:none;">Cancelar</a>
        </div>
    </form>
</div>
</body>
</html>