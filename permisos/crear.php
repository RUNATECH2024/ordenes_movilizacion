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
    // Catálogos iniciales normalizados
    $direcciones = $pdo->query("SELECT id_direccion, nombre FROM direcciones WHERE estado = 'ACTIVO' ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
    $clases = $pdo->query("SELECT id_clase_permiso, nombre FROM clases_permiso WHERE estado = 'ACTIVO' ORDER BY id_clase_permiso ASC")->fetchAll(PDO::FETCH_ASSOC);
    $condiciones = $pdo->query("SELECT id_condicion, nombre FROM condiciones_concesion WHERE estado = 'ACTIVO' ORDER BY id_condicion ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $mensaje = "<div class='alert error'>Error al cargar catálogos: " . $e->getMessage() . "</div>";
}

// Procesar el envío del formulario
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

        // Cálculo automatizado del tiempo en horas
        $h_salida = new DateTime($_POST['hora_salida']);
        $h_llegada = new DateTime($_POST['hora_llegada']);
        $intervalo = $h_salida->diff($h_llegada);
        $total_horas = $intervalo->h + ($intervalo->i / 60);

        $sql_ins = "INSERT INTO permisos_ocasionales (
                        numero_permiso, id_empleado, id_clase_permiso, id_condicion, 
                        fecha_permiso, hora_salida, hora_llegada, total_dias, total_horas, observaciones,
                        id_jefe_valida, id_director_legaliza, firma_empleado_estado, firma_jefe_estado, firma_director_estado, estado_legalizacion
                    ) VALUES (
                        :numero_permiso, :id_empleado, :id_clase_permiso, :id_condicion, 
                        :fecha_permiso, :hora_salida, :hora_llegada, :total_dias, :total_horas, :observaciones,
                        :id_jefe_valida, :id_director_legaliza, 'PENDIENTE', 'PENDIENTE', 'PENDIENTE', 'PENDIENTE'
                    )";
        
        $stmt = $pdo->prepare($sql_ins);
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
            ':id_director_legaliza' => $id_director_legaliza
        ]);

        $pdo->commit();
        $mensaje = "<div class='alert success'>¡Permiso Ocasional Nº " . htmlspecialchars($_POST['numero_permiso']) . " guardado. Autoridades asignadas automáticamente!</div>";
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
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="container">
    <div class="main-header">
        <h2>PREFECTURA DE BOLÍVAR - RECURSOS HUMANOS</h2>
        <a href="index.php" class="btn btn-secondary">Ver Historial</a>
    </div>

    <div class="ticket-header">
        <strong>NUEVA SOLICITUD DE PERMISO OCASIONAL DINÁMICO</strong>
    </div>
    
    <?php echo $mensaje; ?>

    <form action="" method="POST">
        <div class="form-group" style="margin-bottom: 15px;">
            <label>Número de Permiso (Físico) <span class="required">*</span></label>
            <input type="text" name="numero_permiso" placeholder="Ej. 0009447" required>
        </div>

        <div class="grid-3">
            <div class="form-group">
                <label>Dirección Institucional <span class="required">*</span></label>
                <select name="id_direccion" id="id_direccion" required>
                    <option value="">-- Seleccione Dirección --</option>
                    <?php foreach($direcciones as $dir): ?>
                        <option value="<?= $dir['id_direccion'] ?>"><?= htmlspecialchars($dir['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Jefatura / Departamento <span class="required">*</span></label>
                <select name="id_jefatura" id="id_jefatura" required disabled>
                    <option value="">-- Seleccione Primero Dirección --</option>
                </select>
            </div>

            <div class="form-group">
                <label>Funcionario / Empleado <span class="required">*</span></label>
                <select name="id_empleado" id="id_empleado" required disabled>
                    <option value="">-- Seleccione Primero Jefatura --</option>
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

        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-weight: bold; font-size: 16px; cursor: pointer;">💾 Guardar Registro y Asignar Firmas</button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectDireccion = document.getElementById('id_direccion');
    const selectJefatura = document.getElementById('id_jefatura');
    const selectEmpleado = document.getElementById('id_empleado');

    // Cambios en Dirección cargan Jefaturas
    selectDireccion.addEventListener('change', function() {
        const idDir = this.value;
        selectJefatura.innerHTML = '<option value=\"\">-- Seleccione Jefatura --</option>';
        selectEmpleado.innerHTML = '<option value=\"\">-- Seleccione Primero Jefatura --</option>';
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
        selectEmpleado.innerHTML = '<option value=\"\">-- Seleccione Colaborador --</option>';
        selectEmpleado.disabled = true;

        if(idJef) {
            fetch(`get_datos_dinamicos.php?action=get_empleados&id_jefatura=${idJef}`)
                .then(res => res.json())
                .then(data => {
                    if(data.length === 0) {
                        selectEmpleado.innerHTML = '<option value=\"\">No hay empleados en este departamento</option>';
                    } else {
                        data.forEach(emp => {
                            selectEmpleado.innerHTML += `<option value="${emp.id_empleado}">${emp.primer_apellido} ${emp.primer_nombre} (${emp.cedula})</option>`;
                        });
                        selectEmpleado.disabled = false;
                    }
                });
        }
    });
});
</script>

</body>
</html>