<?php
// permisos/editar.php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . '/../includes/conexion.php'; 

$mensaje = "";
$id_permiso = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_permiso <= 0) {
    header("Location: index.php");
    exit;
}

try {
    // 1. Obtener los datos del permiso usando la columna correcta: id_permiso
    $queryPermiso = $pdo->prepare("
        SELECT p.*, c.id_jefatura, j.id_direccion 
        FROM permisos_ocasionales p
        INNER JOIN empleados e ON p.id_empleado = e.id_empleado
        INNER JOIN historial_laboral hl ON e.id_empleado = hl.id_empleado AND hl.activo = true
        INNER JOIN cargos c ON hl.id_cargo = c.id_cargo
        INNER JOIN jefaturas j ON c.id_jefatura = j.id_jefatura
        WHERE p.id_permiso = :id_permiso
        LIMIT 1
    ");
    $queryPermiso->execute([':id_permiso' => $id_permiso]);
    $permiso = $queryPermiso->fetch(PDO::FETCH_ASSOC);

    if (!$permiso) {
        die("Permiso no encontrado.");
    }

} catch (Exception $e) {
    $mensaje = "<div class='alert error'>Error al cargar datos: " . $e->getMessage() . "</div>";
}

// 2. Procesar la actualización
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $pdo->beginTransaction();

        $id_empleado = (int)$_POST['id_empleado'];
        $id_jefatura = (int)$_POST['id_jefatura'];
        $id_direccion = (int)$_POST['id_direccion'];

        // Obtener responsables (Jefe y Director)
        $queryJefe = $pdo->prepare("SELECT id_empleado_jefe FROM historial_jefaturas WHERE id_jefatura = :id_jefatura AND estado = 'ACTIVO' LIMIT 1");
        $queryJefe->execute([':id_jefatura' => $id_jefatura]);
        $id_jefe_valida = $queryJefe->fetchColumn() ?: null;

        $queryDirector = $pdo->prepare("SELECT id_empleado FROM directores WHERE id_direccion = :id_direccion AND estado = 'ACTIVO' LIMIT 1");
        $queryDirector->execute([':id_direccion' => $id_direccion]);
        $id_director_legaliza = $queryDirector->fetchColumn() ?: null;

        // Cálculo de horas
        $salida_completa = new DateTime($_POST['fecha_permiso'] . ' ' . $_POST['hora_salida']);
        $llegada_completa = new DateTime($_POST['fecha_permiso'] . ' ' . $_POST['hora_llegada']);
        $segundos_totales = $llegada_completa->getTimestamp() - $salida_completa->getTimestamp();
        
        $almuerzo_inicio = new DateTime($_POST['fecha_permiso'] . ' 12:00:00');
        $almuerzo_fin = new DateTime($_POST['fecha_permiso'] . ' 13:00:00');
        $interseccion_inicio = max($salida_completa, $almuerzo_inicio);
        $interseccion_fin = min($llegada_completa, $almuerzo_fin);
        $segundos_almuerzo = ($interseccion_inicio < $interseccion_fin) ? ($interseccion_fin->getTimestamp() - $interseccion_inicio->getTimestamp()) : 0;
        $total_horas = max(0, ($segundos_totales - $segundos_almuerzo) / 3600);

        // Update usando la columna correcta: id_permiso
        $sql_upd = "UPDATE permisos_ocasionales SET 
                        numero_permiso = :numero_permiso, id_empleado = :id_empleado, 
                        id_clase_permiso = :id_clase_permiso, id_condicion = :id_condicion, 
                        fecha_permiso = :fecha_permiso, hora_salida = :hora_salida, 
                        hora_llegada = :hora_llegada, total_dias = :total_dias, 
                        total_horas = :total_horas, observaciones = :observaciones,
                        id_jefe_valida = :id_jefe_valida, id_director_legaliza = :id_director_legaliza
                    WHERE id_permiso = :id_permiso";
        
        $stmt = $pdo->prepare($sql_upd);
        $stmt->execute([
            ':numero_permiso' => $_POST['numero_permiso'], ':id_empleado' => $id_empleado,
            ':id_clase_permiso' => (int)$_POST['id_clase_permiso'], ':id_condicion' => (int)$_POST['id_condicion'],
            ':fecha_permiso' => $_POST['fecha_permiso'], ':hora_salida' => $_POST['hora_salida'],
            ':hora_llegada' => $_POST['hora_llegada'], ':total_dias' => (int)$_POST['total_dias'] ?: 0,
            ':total_horas' => $total_horas, ':observaciones' => $_POST['observaciones'] ?: null,
            ':id_jefe_valida' => $id_jefe_valida, ':id_director_legaliza' => $id_director_legaliza,
            ':id_permiso' => $id_permiso
        ]);

        $pdo->commit();
        $queryPermiso->execute([':id_permiso' => $id_permiso]);
        $permiso = $queryPermiso->fetch(PDO::FETCH_ASSOC);
        $mensaje = "<div class='alert success'>¡Actualizado correctamente! Horas: " . number_format($total_horas, 2) . "</div>";
    } catch (Exception $e) {
        $pdo->rollBack();
        $mensaje = "<div class='alert error'>Error: " . $e->getMessage() . "</div>";
    }
}

// Variables para formulario
$valores_formulario = [
    'id_direccion' => $_POST['id_direccion'] ?? $permiso['id_direccion'] ?? '',
    'id_jefatura' => $_POST['id_jefatura'] ?? $permiso['id_jefatura'] ?? '',
    'id_empleado' => $_POST['id_empleado'] ?? $permiso['id_empleado'] ?? '',
    'numero_permiso' => $_POST['numero_permiso'] ?? $permiso['numero_permiso'] ?? '',
    'id_clase_permiso' => $_POST['id_clase_permiso'] ?? $permiso['id_clase_permiso'] ?? '',
    'fecha_permiso' => $_POST['fecha_permiso'] ?? $permiso['fecha_permiso'] ?? '',
    'total_dias' => $_POST['total_dias'] ?? $permiso['total_dias'] ?? '0',
    'hora_salida' => $_POST['hora_salida'] ?? $permiso['hora_salida'] ?? '',
    'hora_llegada' => $_POST['hora_llegada'] ?? $permiso['hora_llegada'] ?? '',
    'id_condicion' => $_POST['id_condicion'] ?? $permiso['id_condicion'] ?? '',
    'observaciones' => $_POST['observaciones'] ?? $permiso['observaciones'] ?? ''
];

// Carga catálogos... (resto del código igual)

// 4. Carga de catálogos basada en los valores determinados en el paso anterior
try {
    $direcciones = $pdo->query("SELECT id_direccion, nombre FROM direcciones WHERE estado = 'ACTIVO' ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
    $clases = $pdo->query("SELECT id_clase_permiso, nombre FROM clases_permiso WHERE estado = 'ACTIVO' ORDER BY id_clase_permiso ASC")->fetchAll(PDO::FETCH_ASSOC);
    $condiciones = $pdo->query("SELECT id_condicion, nombre FROM condiciones_concesion WHERE estado = 'ACTIVO' ORDER BY id_condicion ASC")->fetchAll(PDO::FETCH_ASSOC);

    $jefaturas_actuales = [];
    if (!empty($valores_formulario['id_direccion'])) {
        $stmtJef = $pdo->prepare("SELECT id_jefatura, nombre FROM jefaturas WHERE id_direccion = :id_dir AND estado = 'ACTIVO' ORDER BY nombre ASC");
        $stmtJef->execute([':id_dir' => $valores_formulario['id_direccion']]);
        $jefaturas_actuales = $stmtJef->fetchAll(PDO::FETCH_ASSOC);
    }

    $empleados_actuales = [];
    if (!empty($valores_formulario['id_jefatura'])) {
        // Obtenemos los empleados que pertenecen a esta jefatura a través de su historial laboral y cargo actual
        $stmtEmp = $pdo->prepare("
            SELECT e.id_empleado, e.primer_nombre, e.primer_apellido, e.cedula 
            FROM empleados e
            INNER JOIN historial_laboral hl ON e.id_empleado = hl.id_empleado AND hl.activo = true
            INNER JOIN cargos c ON hl.id_cargo = c.id_cargo
            WHERE c.id_jefatura = :id_jef
            ORDER BY e.primer_apellido ASC
        ");
        $stmtEmp->execute([':id_jef' => $valores_formulario['id_jefatura']]);
        $empleados_actuales = $stmtEmp->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $mensaje .= "<div class='alert error'>Error al estructurar catálogos: " . $e->getMessage() . "</div>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGA - Editar Permiso Ocasional</title>
    <link rel="stylesheet" href="../assets/estilos.css?v=<?= time(); ?>">
    <style>
        .ticket-header { background: #fff3cd; border-left: 5px solid #ffc107; padding: 15px; margin-bottom: 20px; }
        .radio-group { display: flex; gap: 20px; flex-wrap: wrap; background: #fafafa; padding: 15px; border: 1px solid #ddd; border-radius: 4px; }
        .radio-item { display: flex; align-items: center; gap: 8px; font-weight: bold; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 15px; }
        .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 15px; }
        .required { color: red; }
    </style>
</head>
<body>

<div class="container">
    <div class="main-header">
        <h2>PREFECTURA DE BOLÍVAR - RECURSOS HUMANOS</h2>
        <a href="index.php" class="btn btn-secondary">Ver Historial</a>
    </div>

    <div class="ticket-header">
        <strong>EDITAR SOLICITUD DE PERMISO OCASIONAL (ID: <?= $id_permiso ?>)</strong>
    </div>
    
    <?php echo $mensaje; ?>

    <form action="" method="POST" id="form-permiso">
        <div class="form-group" style="margin-bottom: 15px;">
            <label>Número de Permiso (Físico) <span class="required">*</span></label>
            <input type="text" name="numero_permiso" value="<?= htmlspecialchars($valores_formulario['numero_permiso']) ?>" required>
        </div>

        <div class="grid-3">
            <div class="form-group">
                <label>Dirección Institucional <span class="required">*</span></label>
                <select name="id_direccion" id="id_direccion" required>
                    <option value="">-- Seleccione Dirección --</option>
                    <?php foreach($direcciones as $dir): ?>
                        <option value="<?= $dir['id_direccion'] ?>" <?= ($dir['id_direccion'] == $valores_formulario['id_direccion']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dir['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Jefatura / Departamento <span class="required">*</span></label>
                <select name="id_jefatura" id="id_jefatura" required>
                    <option value="">-- Seleccione Jefatura --</option>
                    <?php foreach($jefaturas_actuales as $jef): ?>
                        <option value="<?= $jef['id_jefatura'] ?>" <?= ($jef['id_jefatura'] == $valores_formulario['id_jefatura']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($jef['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Funcionario / Empleado <span class="required">*</span></label>
                <select name="id_empleado" id="id_empleado" required>
                    <option value="">-- Seleccione Colaborador --</option>
                    <?php foreach($empleados_actuales as $emp): ?>
                        <option value="<?= $emp['id_empleado'] ?>" <?= ($emp['id_empleado'] == $valores_formulario['id_empleado']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($emp['primer_apellido'] . ' ' . $emp['primer_nombre'] . ' (' . $emp['cedula'] . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <h3>Clase de Permiso (Casillas Superiores)</h3>
        <div class="form-group">
            <div class="radio-group">
                <?php foreach($clases as $cl): ?>
                    <div class="radio-item">
                        <input type="radio" name="id_clase_permiso" value="<?= $cl['id_clase_permiso'] ?>" id="clase_<?= $cl['id_clase_permiso'] ?>" <?= ($cl['id_clase_permiso'] == $valores_formulario['id_clase_permiso']) ? 'checked' : '' ?> required>
                        <label for="clase_<?= $cl['id_clase_permiso'] ?>"><?= htmlspecialchars($cl['nombre']) ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <h3>Tiempo del Permiso</h3>
        <div class="grid-2">
            <div class="form-group">
                <label>Fecha del Permiso <span class="required">*</span></label>
                <input type="date" name="fecha_permiso" value="<?= htmlspecialchars($valores_formulario['fecha_permiso']) ?>" required>
            </div>
            <div class="form-group">
                <label>Días Totales <span class="required">*</span></label>
                <input type="number" name="total_dias" value="<?= htmlspecialchars($valores_formulario['total_dias']) ?>" min="0" required>
            </div>
            <div class="form-group">
                <label>Hora de Salida <span class="required">*</span></label>
                <input type="time" name="hora_salida" value="<?= htmlspecialchars(substr($valores_formulario['hora_salida'], 0, 5)) ?>" required>
            </div>
            <div class="form-group">
                <label>Hora de Llegada <span class="required">*</span></label>
                <input type="time" name="hora_llegada" value="<?= htmlspecialchars(substr($valores_formulario['hora_llegada'], 0, 5)) ?>" required>
            </div>
        </div>

        <h3>Concesión del Permiso (Resolución del Jefe)</h3>
        <div class="form-group">
            <div class="radio-group">
                <?php foreach($condiciones as $cond): ?>
                    <div class="radio-item">
                        <input type="radio" name="id_condicion" value="<?= $cond['id_condicion'] ?>" id="cond_<?= $cond['id_condicion'] ?>" <?= ($cond['id_condicion'] == $valores_formulario['id_condicion']) ? 'checked' : '' ?> required>
                        <label for="cond_<?= $cond['id_condicion'] ?>"><?= htmlspecialchars($cond['nombre']) ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <h3>Observaciones y Justificaciones (Oficina de Personal)</h3>
        <div class="form-group">
            <textarea name="observaciones" rows="3" placeholder="Ej. Compensación por el día lunes trabajado..."><?= htmlspecialchars($valores_formulario['observaciones'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-weight: bold; font-size: 16px; cursor: pointer; background-color: #ffc107; color: #212529; border: none; border-radius: 4px;">💾 Actualizar Registro y Recalcular</button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectDireccion = document.getElementById('id_direccion');
    const selectJefatura = document.getElementById('id_jefatura');
    const selectEmpleado = document.getElementById('id_empleado');
    const form = document.getElementById('form-permiso');

    // Cambios en Dirección cargan Jefaturas
    selectDireccion.addEventListener('change', function() {
        const idDir = this.value;
        selectJefatura.innerHTML = '<option value="">-- Seleccione Jefatura --</option>';
        selectEmpleado.innerHTML = '<option value="">-- Seleccione Primero Jefatura --</option>';
        selectJefatura.disabled = true;
        selectEmpleado.disabled = true;

        if(idDir) {
            fetch(`get_datos_dinamicos.php?action=get_jefaturas&id_direccion=${idDir}`)
                .then(res => res.json())
                .then(data => {
                    data.forEach(jef => {
                        selectJefatura.innerHTML += `<option value="${jef.id_jefatura}">${jef.nombre}</option>`;
                    });
                    selectJefatura.disabled = false;
                });
        }
    });

    // Cambios en Jefatura cargan Empleados
    selectJefatura.addEventListener('change', function() {
        const idJef = this.value;
        selectEmpleado.innerHTML = '<option value="">-- Seleccione Colaborador --</option>';
        selectEmpleado.disabled = true;

        if(idJef) {
            fetch(`get_datos_dinamicos.php?action=get_empleados&id_jefatura=${idJef}`)
                .then(res => res.json())
                .then(data => {
                    if(data.length === 0) {
                        selectEmpleado.innerHTML = '<option value="">No hay empleados en este departamento</option>';
                    } else {
                        data.forEach(emp => {
                            selectEmpleado.innerHTML += `<option value="${emp.id_empleado}">${emp.primer_apellido} ${emp.primer_nombre} (${emp.cedula})</option>`;
                        });
                        selectEmpleado.disabled = false;
                    }
                });
        }
    });

    // Validación del lado del cliente (Frontend) para las horas ingresadas
    form.addEventListener('submit', function(e) {
        const horaSalida = document.querySelector('input[name="hora_salida"]').value;
        const horaLlegada = document.querySelector('input[name="hora_llegada"]').value;

        if (horaSalida && horaLlegada) {
            if (horaLlegada <= horaSalida) {
                e.preventDefault();
                alert('⚠️ Error: La hora de llegada debe ser posterior a la hora de salida.');
            }
        }
    });
});
</script>

</body>
</html>