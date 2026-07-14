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
    // 1. Obtener los datos del permiso actual a editar
    $queryPermiso = $pdo->prepare("
        SELECT p.*, e.id_jefatura, j.id_direccion 
        FROM permisos_ocasionales p
        INNER JOIN empleados e ON p.id_empleado = e.id_empleado
        INNER JOIN jefaturas j ON e.id_jefatura = j.id_jefatura
        WHERE p.id_permiso_ocasional = :id_permiso
        LIMIT 1
    ");
    $queryPermiso->execute([':id_permiso' => $id_permiso]);
    $permiso = $queryPermiso->fetch(PDO::FETCH_ASSOC);

    if (!$permiso) {
        die("Permiso no encontrado.");
    }

    // 2. Catálogos iniciales normalizados
    $direcciones = $pdo->query("SELECT id_direccion, nombre FROM direcciones WHERE estado = 'ACTIVO' ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
    $clases = $pdo->query("SELECT id_clase_permiso, nombre FROM clases_permiso WHERE estado = 'ACTIVO' ORDER BY id_clase_permiso ASC")->fetchAll(PDO::FETCH_ASSOC);
    $condiciones = $pdo->query("SELECT id_condicion, nombre FROM condiciones_concesion WHERE estado = 'ACTIVO' ORDER BY id_condicion ASC")->fetchAll(PDO::FETCH_ASSOC);

    // Cargar catálogos dependientes del registro actual para que aparezcan seleccionados al cargar
    $jefaturas_actuales = [];
    if ($permiso['id_direccion']) {
        $stmtJef = $pdo->prepare("SELECT id_jefatura, nombre FROM jefaturas WHERE id_direccion = :id_dir AND estado = 'ACTIVO' ORDER BY nombre ASC");
        $stmtJef->execute([':id_dir' => $permiso['id_direccion']]);
        $jefaturas_actuales = $stmtJef->fetchAll(PDO::FETCH_ASSOC);
    }

    $empleados_actuales = [];
    if ($permiso['id_jefatura']) {
        $stmtEmp = $pdo->prepare("SELECT id_empleado, primer_nombre, primer_apellido, cedula FROM empleados WHERE id_jefatura = :id_jef ORDER BY primer_apellido ASC");
        $stmtEmp->execute([':id_jef' => $permiso['id_jefatura']]);
        $empleados_actuales = $stmtEmp->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (Exception $e) {
    $mensaje = "<div class='alert error'>Error al cargar datos: " . $e->getMessage() . "</div>";
}

// Procesar la actualización del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $pdo->beginTransaction();

        $id_empleado = (int)$_POST['id_empleado'];
        $id_jefatura = (int)$_POST['id_jefatura'];
        $id_direccion = (int)$_POST['id_direccion'];

        // 1. OBTENER EL JEFE INMEDIATO ACTIVO DE LA JEFATURA SELECCIONADA
        $queryJefe = $pdo->prepare("
            SELECT id_empleado_jefe FROM historial_jefaturas 
            WHERE id_jefatura = :id_jefatura AND estado = 'ACTIVO' LIMIT 1
        ");
        $queryJefe->execute([':id_jefatura' => $id_jefatura]);
        $jefeRes = $queryJefe->fetch(PDO::FETCH_ASSOC);
        $id_jefe_valida = $jefeRes['id_empleado_jefe'] ?? null;

        // 2. OBTENER EL DIRECTOR ACTIVO DE LA DIRECCIÓN SELECCIONADA
        $queryDirector = $pdo->prepare("
            SELECT id_empleado FROM directores 
            WHERE id_direccion = :id_direccion AND estado = 'ACTIVO' LIMIT 1
        ");
        $queryDirector->execute([':id_direccion' => $id_direccion]);
        $dirRes = $queryDirector->fetch(PDO::FETCH_ASSOC);
        $id_director_legaliza = $dirRes['id_empleado'] ?? null;

        // --- CÁLCULO DE HORAS DESCONTANDO JORNADA DE ALMUERZO (12:00 a 13:00) ---
        $fecha_permiso_str = $_POST['fecha_permiso'];
        
        $salida_completa = new DateTime($fecha_permiso_str . ' ' . $_POST['hora_salida']);
        $llegada_completa = new DateTime($fecha_permiso_str . ' ' . $_POST['hora_llegada']);

        $segundos_totales = $llegada_completa->getTimestamp() - $salida_completa->getTimestamp();

        if ($segundos_totales < 0) {
            throw new Exception("La hora de llegada no puede ser anterior a la hora de salida.");
        }

        // Definir límites de la hora de almuerzo para ese día
        $almuerzo_inicio = new DateTime($fecha_permiso_str . ' 12:00:00');
        $almuerzo_fin = new DateTime($fecha_permiso_str . ' 13:00:00');

        // Calcular si existe intersección (solapamiento) con el almuerzo
        $interseccion_inicio = max($salida_completa, $almuerzo_inicio);
        $interseccion_fin = min($llegada_completa, $almuerzo_fin);

        $segundos_almuerzo = 0;
        if ($interseccion_inicio < $interseccion_fin) {
            // El permiso coincide total o parcialmente con el almuerzo, restamos esta diferencia
            $segundos_almuerzo = $interseccion_fin->getTimestamp() - $interseccion_inicio->getTimestamp();
        }

        $segundos_efectivos = $segundos_totales - $segundos_almuerzo;
        $total_horas = max(0, $segundos_efectivos / 3600);
        // ------------------------------------------------------------------------

        $sql_upd = "UPDATE permisos_ocasionales SET 
                        numero_permiso = :numero_permiso, 
                        id_empleado = :id_empleado, 
                        id_clase_permiso = :id_clase_permiso, 
                        id_condicion = :id_condicion, 
                        fecha_permiso = :fecha_permiso, 
                        hora_salida = :hora_salida, 
                        hora_llegada = :hora_llegada, 
                        total_dias = :total_dias, 
                        total_horas = :total_horas, 
                        observaciones = :observaciones,
                        id_jefe_valida = :id_jefe_valida, 
                        id_director_legaliza = :id_director_legaliza
                    WHERE id_permiso_ocasional = :id_permiso";
        
        $stmt = $pdo->prepare($sql_upd);
        $stmt->execute([
            ':numero_permiso'       => $_POST['numero_permiso'],
            ':id_empleado'          => $id_empleado,
            ':id_clase_permiso'     => (int)$_POST['id_clase_permiso'],
            ':id_condicion'         => (int)$_POST['id_condicion'],
            ':fecha_permiso'        => $_POST['fecha_permiso'],
            ':hora_salida'          => $_POST['hora_salida'],
            ':hora_llegada'         => $_POST['hora_llegada'],
            ':total_dias'           => (int)$_POST['total_dias'] ?: 0,
            ':total_horas'          => $total_horas,
            ':observaciones'        => $_POST['observaciones'] ?: null,
            ':id_jefe_valida'       => $id_jefe_valida,
            ':id_director_legaliza' => $id_director_legaliza,
            ':id_permiso'           => $id_permiso
        ]);

        $pdo->commit();
        
        // Recargar el permiso actualizado en pantalla
        $queryPermiso->execute([':id_permiso' => $id_permiso]);
        $permiso = $queryPermiso->fetch(PDO::FETCH_ASSOC);

        $mensaje = "<div class='alert success'>¡Permiso Ocasional Nº " . htmlspecialchars($_POST['numero_permiso']) . " actualizado correctamente! Horas calculadas (sin almuerzo): " . number_format($total_horas, 2) . " hrs.</div>";
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
            <input type="text" name="numero_permiso" value="<?= htmlspecialchars($permiso['numero_permiso']) ?>" required>
        </div>

        <div class="grid-3">
            <div class="form-group">
                <label>Dirección Institucional <span class="required">*</span></label>
                <select name="id_direccion" id="id_direccion" required>
                    <option value="">-- Seleccione Dirección --</option>
                    <?php foreach($direcciones as $dir): ?>
                        <option value="<?= $dir['id_direccion'] ?>" <?= ($dir['id_direccion'] == $permiso['id_direccion']) ? 'selected' : '' ?>>
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
                        <option value="<?= $jef['id_jefatura'] ?>" <?= ($jef['id_jefatura'] == $permiso['id_jefatura']) ? 'selected' : '' ?>>
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
                        <option value="<?= $emp['id_empleado'] ?>" <?= ($emp['id_empleado'] == $permiso['id_empleado']) ? 'selected' : '' ?>>
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
                        <input type="radio" name="id_clase_permiso" value="<?= $cl['id_clase_permiso'] ?>" id="clase_<?= $cl['id_clase_permiso'] ?>" <?= ($cl['id_clase_permiso'] == $permiso['id_clase_permiso']) ? 'checked' : '' ?> required>
                        <label for="clase_<?= $cl['id_clase_permiso'] ?>"><?= htmlspecialchars($cl['nombre']) ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <h3>Tiempo del Permiso</h3>
        <div class="grid-2">
            <div class="form-group">
                <label>Fecha del Permiso <span class="required">*</span></label>
                <input type="date" name="fecha_permiso" value="<?= htmlspecialchars($permiso['fecha_permiso']) ?>" required>
            </div>
            <div class="form-group">
                <label>Días Totales <span class="required">*</span></label>
                <input type="number" name="total_dias" value="<?= htmlspecialchars($permiso['total_dias']) ?>" min="0" required>
            </div>
            <div class="form-group">
                <label>Hora de Salida <span class="required">*</span></label>
                <input type="time" name="hora_salida" value="<?= htmlspecialchars(substr($permiso['hora_salida'], 0, 5)) ?>" required>
            </div>
            <div class="form-group">
                <label>Hora de Llegada <span class="required">*</span></label>
                <input type="time" name="hora_llegada" value="<?= htmlspecialchars(substr($permiso['hora_llegada'], 0, 5)) ?>" required>
            </div>
        </div>

        <h3>Concesión del Permiso (Resolución del Jefe)</h3>
        <div class="form-group">
            <div class="radio-group">
                <?php foreach($condiciones as $cond): ?>
                    <div class="radio-item">
                        <input type="radio" name="id_condicion" value="<?= $cond['id_condicion'] ?>" id="cond_<?= $cond['id_condicion'] ?>" <?= ($cond['id_condicion'] == $permiso['id_condicion']) ? 'checked' : '' ?> required>
                        <label for="cond_<?= $cond['id_condicion'] ?>"><?= htmlspecialchars($cond['nombre']) ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <h3>Observaciones y Justificaciones (Oficina de Personal)</h3>
        <div class="form-group">
            <textarea name="observaciones" rows="3" placeholder="Ej. Compensación por el día lunes trabajado..."><?= htmlspecialchars($permiso['observaciones'] ?? '') ?></textarea>
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