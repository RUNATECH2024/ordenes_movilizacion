<?php
// permisos/crear.php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . '/../includes/conexion.php'; 

$mensaje = "";

try {
    // Cargar catálogos normalizados para el formulario
    $empleados = $pdo->query("SELECT id_empleado, cedula, primer_apellido, primer_nombre FROM empleados WHERE estado = 'ACTIVO' ORDER BY primer_apellido ASC")->fetchAll(PDO::FETCH_ASSOC);
    $clases = $pdo->query("SELECT id_clase_permiso, nombre FROM clases_permiso WHERE estado = 'ACTIVO' ORDER BY id_clase_permiso ASC")->fetchAll(PDO::FETCH_ASSOC);
    $condiciones = $pdo->query("SELECT id_condicion, nombre FROM condiciones_concesion WHERE estado = 'ACTIVO' ORDER BY id_condicion ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $mensaje = "<div class='alert error'>Error al cargar catálogos: " . $e->getMessage() . "</div>";
}

// Procesar el envío del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $pdo->beginTransaction();

        // Cálculo automatizado del tiempo en horas (Base decimal para el kárdex)
        $h_salida = new DateTime($_POST['hora_salida']);
        $h_llegada = new DateTime($_POST['hora_llegada']);
        $intervalo = $h_salida->diff($h_llegada);
        $total_horas = $intervalo->h + ($intervalo->i / 60);

        $sql_ins = "INSERT INTO permisos_ocasionales (
                        numero_permiso, id_empleado, id_clase_permiso, id_condicion, 
                        fecha_permiso, hora_salida, hora_llegada, total_dias, total_horas, observaciones
                    ) VALUES (
                        :numero_permiso, :id_empleado, :id_clase_permiso, :id_condicion, 
                        :fecha_permiso, :hora_salida, :hora_llegada, :total_dias, :total_horas, :observaciones
                    )";
        
        $stmt = $pdo->prepare($sql_ins);
        $stmt->execute([
            ':numero_permiso'   => $_POST['numero_permiso'],
            ':id_empleado'      => (int)$_POST['id_empleado'],
            ':id_clase_permiso' => (int)$_POST['id_clase_permiso'],
            ':id_condicion'     => (int)$_POST['id_condicion'],
            ':fecha_permiso'    => $_POST['fecha_permiso'],
            ':hora_salida'      => $_POST['hora_salida'],
            ':hora_llegada'     => $_POST['hora_llegada'],
            ':total_dias'       => (int)$_POST['total_dias'] ?: 0,
            ':total_horas'      => $total_horas,
            ':observaciones'    => $_POST['observaciones'] ?: null
        ]);

        $pdo->commit();
        $mensaje = "<div class='alert success'>¡Permiso Ocasional Nº " . htmlspecialchars($_POST['numero_permiso']) . " guardado. Pendiente de firmas estructurales!</div>";
    } catch (PDOException $e) {
        $pdo->rollBack();
        if ($e->getCode() == '23505') {
            $mensaje = "<div class='alert error'>⚠️ El número de permiso ya se encuentra registrado en el sistema.</div>";
        } else {
            $mensaje = "<div class='alert error'>Error de base de datos: " . $e->getMessage() . "</div>";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $mensaje = "<div class='alert error'>Error general: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGA - Registrar Permiso Ocasional</title>
    <link rel="stylesheet" href="../assets/estilos.css?v=<?= time(); ?>">
    <style>
        .ticket-header { background: #f0f4f8; border-left: 5px solid #0056b3; padding: 15px; margin-bottom: 20px; }
        .radio-group { display: flex; gap: 20px; flex-wrap: wrap; background: #fafafa; padding: 15px; border: 1px solid #ddd; border-radius: 4px; }
        .radio-item { display: flex; align-items: center; gap: 8px; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <div class="main-header">
        <h2>PREFECTURA DE BOLÍVAR - RECURSOS HUMANOS</h2>
        <a href="index.php" class="btn btn-secondary">Ver Historial</a>
    </div>

    <div class="ticket-header">
        <strong>NUEVA SOLICITUD DE PERMISO OCASIONAL</strong>
    </div>
    
    <?php echo $mensaje; ?>

    <form action="" method="POST">
        <div class="grid-2">
            <div class="form-group">
                <label>Número de Permiso (Físico) <span class="required">*</span></label>
                <input type="text" name="numero_permiso" placeholder="Ej. 0009447" required>
            </div>
            <div class="form-group">
                <label>Funcionario / Empleado <span class="required">*</span></label>
                <select name="id_empleado" required>
                    <option value="">-- Seleccione Colaborador --</option>
                    <?php foreach($empleados as $emp): ?>
                        <option value="<?= $emp['id_empleado'] ?>"><?= htmlspecialchars($emp['primer_apellido'] . ' ' . $emp['primer_nombre'] . ' (' . $emp['cedula'] . ')') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <h3>Clase de Permiso (Casillas Superiores)</h3>
        <div class="form-group">
            <div class="radio-group">
                <?php foreach($clases as $cl): ?>
                    <div class="radio-item">
                        <input type="radio" name="id_clase_permiso" value="<?= $cl['id_clase_permiso'] ?>" id="clase_<?= $cl['id_clase_permiso'] ?>" required>
                        <label for="clase_<?= $cl['id_clase_permiso'] ?>"><?= htmlspecialchars($cl['nombre']) ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <h3>Tiempo del Permiso</h3>
        <div class="grid-2">
            <div class="form-group">
                <label>Fecha del Permiso <span class="required">*</span></label>
                <input type="date" name="fecha_permiso" required>
            </div>
            <div class="form-group">
                <label>Días Totales <span class="required">*</span></label>
                <input type="number" name="total_dias" value="1" min="0" required>
            </div>
            <div class="form-group">
                <label>Hora de Salida <span class="required">*</span></label>
                <input type="time" name="hora_salida" required>
            </div>
            <div class="form-group">
                <label>Hora de Llegada <span class="required">*</span></label>
                <input type="time" name="hora_llegada" required>
            </div>
        </div>

        <h3>Concesión del Permiso (Resolución del Jefe)</h3>
        <div class="form-group">
            <div class="radio-group">
                <?php foreach($condiciones as $cond): ?>
                    <div class="radio-item">
                        <input type="radio" name="id_condicion" value="<?= $cond['id_condicion'] ?>" id="cond_<?= $cond['id_condicion'] ?>" required>
                        <label for="cond_<?= $cond['id_condicion'] ?>"><?= htmlspecialchars($cond['nombre']) ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <h3>Observaciones y Justificaciones (Oficina de Personal)</h3>
        <div class="form-group">
            <textarea name="observaciones" rows="3" placeholder="Ej. Compensación por el día lunes trabajado..."></textarea>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-weight: bold; font-size: 16px; cursor: pointer;">💾 Guardar Registro y Pasar a Firmas</button>
    </form>
</div>

</body>
</html>