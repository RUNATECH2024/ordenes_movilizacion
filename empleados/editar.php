<?php
// empleados/editar.php
session_start();

// Control de acceso
if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . '/../includes/conexion.php'; 

$mensaje = "";
$emp = null;

// Validar ID recibido por la URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id_empleado = $_GET['id'];

// 1. CARGAR CATÁLOGOS PARA LOS SELECTS
try {
    $generos = $pdo->query("SELECT id_genero, nombre FROM generos ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    $estados_civiles = $pdo->query("SELECT id_estado_civil, nombre FROM estados_civiles ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    $tipos_sangre = $pdo->query("SELECT id_tipo_sangre, nombre FROM tipos_sangre ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    $etnias = $pdo->query("SELECT id_etnia, nombre FROM etnias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC); 
    $discapacidades = $pdo->query("SELECT id_discapacidad, nombre FROM discapacidades WHERE estado = 'ACTIVO' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    $provincias = $pdo->query("SELECT id_provincia, nombre FROM provincias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    $direcciones = $pdo->query("SELECT id_direccion, nombre FROM direcciones WHERE estado = 'ACTIVO' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $mensaje = "<div class='alert error'>Error al cargar catálogos: " . $e->getMessage() . "</div>";
}

// 2. PROCESAR LA ACTUALIZACIÓN (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $pdo->beginTransaction();

        // --- PROCESAMIENTO DE LA FOTO (Opcional en edición) ---
        $nombre_foto = $_POST['foto_actual'];
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $nombre_foto = $_POST['cedula'] . '_' . time() . '.' . $extension;
            $ruta_destino = '../uploads/' . $nombre_foto;
            
            if (!is_dir('../uploads/')) {
                mkdir('../uploads/', 0777, true);
            }
            move_uploaded_file($_FILES['foto']['tmp_name'], $ruta_destino);
        }

        // --- ACTALIZAR TABLA: empleados ---
        $sql_emp = "UPDATE empleados SET 
                        cedula = :cedula, primer_nombre = :primer_nombre, segundo_nombre = :segundo_nombre, 
                        primer_apellido = :primer_apellido, segundo_apellido = :segundo_apellido, 
                        fecha_nacimiento = :fecha_nacimiento, id_genero = :id_genero, id_estado_civil = :id_estado_civil, 
                        foto = :foto, estado = :estado, id_tipo_sangre = :id_tipo_sangre, id_etnia = :id_etnia
                    WHERE id_empleado = :id_empleado";
        
        $stmt_emp = $pdo->prepare($sql_emp);
        $stmt_emp->execute([
            ':cedula'           => $_POST['cedula'],
            ':primer_nombre'    => $_POST['primer_nombre'],
            ':segundo_nombre'   => $_POST['segundo_nombre'] ?: null,
            ':primer_apellido'  => $_POST['primer_apellido'],
            ':segundo_apellido' => $_POST['segundo_apellido'] ?: null,
            ':fecha_nacimiento' => $_POST['fecha_nacimiento'],
            ':id_genero'        => $_POST['id_genero'] ?: null,
            ':id_estado_civil'  => $_POST['id_estado_civil'] ?: null,
            ':foto'             => $nombre_foto,
            ':estado'           => isset($_POST['estado_laboral']) ? 'ACTIVO' : 'INACTIVO',
            ':id_tipo_sangre'   => $_POST['id_tipo_sangre'] ?: null,
            ':id_etnia'         => $_POST['id_etnia'] ?: null,
            ':id_empleado'      => $id_empleado
        ]);

        // --- ACTUALIZAR TABLA: contacto_personal (UPSERT básico) ---
        $pdo->prepare("DELETE FROM contacto_personal WHERE id_empleado = ?")->execute([$id_empleado]);
        $sql_cont = "INSERT INTO contacto_personal (id_empleado, celular, telefono, correo_personal) VALUES (?, ?, ?, ?)";
        $pdo->prepare($sql_cont)->execute([$id_empleado, $_POST['celular'], $_POST['telefono'], $_POST['correo_personal']]);

        // --- ACTUALIZAR TABLA: correo_institucional ---
        $pdo->prepare("DELETE FROM correo_institucional WHERE id_empleado = ?")->execute([$id_empleado]);
        if (!empty($_POST['correo_inst'])) {
            $sql_corr = "INSERT INTO correo_institucional (id_empleado, correo, usuario, activo) VALUES (?, ?, ?, TRUE)";
            $pdo->prepare($sql_corr)->execute([$id_empleado, $_POST['correo_inst'], $_POST['usuario_inst']]);
        }

        // --- ACTUALIZAR TABLA: ubicacion_domiciliaria ---
        $pdo->prepare("DELETE FROM ubicacion_domiciliaria WHERE id_empleado = ?")->execute([$id_empleado]);
        $sql_dom = "INSERT INTO ubicacion_domiciliaria (id_empleado, id_provincia, id_ciudades, id_parroquia, barrio, calle_principal, calle_secundaria, numero_casa, referencia) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql_dom)->execute([
            $id_empleado, $_POST['id_provincia'] ?: null, $_POST['id_ciudad'] ?: null, $_POST['id_parroquia'] ?: null, 
            $_POST['barrio'], $_POST['calle_principal'], $_POST['calle_secundaria'], $_POST['numero_casa'], $_POST['referencia']
        ]);

        // --- ACTUALIZAR TABLA: historial_laboral ---
        if (!empty($_POST['id_cargo'])) {
            // Desactivar cargos anteriores para que solo el nuevo quede activo
            $pdo->prepare("UPDATE historial_laboral SET activo = FALSE WHERE id_empleado = ?")->execute([$id_empleado]);
            
            $sql_hist = "INSERT INTO historial_laboral (id_empleado, id_cargo, id_tipo_nombramiento, fecha_inicio, activo) 
                         VALUES (?, ?, ?, ?, TRUE)";
            $pdo->prepare($sql_hist)->execute([
                $id_empleado, $_POST['id_cargo'], $_POST['id_tipo_nombramiento'] ?: null, $_POST['fecha_ingreso']
            ]);
        }

        // --- ACTUALIZAR TABLA: empleado_discapacidad ---
        $pdo->prepare("DELETE FROM empleado_discapacidad WHERE id_empleado = ?")->execute([$id_empleado]);
        if (!empty($_POST['id_discapacidad'])) {
            $sql_disc = "INSERT INTO empleado_discapacidad (id_empleado, id_discapacidad, porcentaje, numero_carnet, observaciones) VALUES (?, ?, ?, ?, ?)";
            $pdo->prepare($sql_disc)->execute([
                $id_empleado, $_POST['id_discapacidad'], $_POST['porcentaje'] ?: 0, $_POST['numero_carnet'], $_POST['observaciones_disc']
            ]);
        }

        $pdo->commit();
        $mensaje = "<div class='alert success'>¡Datos del empleado actualizados correctamente!</div>";
    } catch (Exception $e) {
        $pdo->rollBack();
        $mensaje = "<div class='alert error'>Error al actualizar: " . $e->getMessage() . "</div>";
    }
}

// 3. OBTENER DATOS ACTUALES DEL EMPLEADO PARA LLENAR EL FORMULARIO
try {
    $sql = "SELECT e.*, cp.celular, cp.telefono, cp.correo_personal, ci.correo AS correo_inst, ci.usuario AS usuario_inst,
                   ud.id_provincia, ud.id_ciudades AS id_ciudad, ud.id_parroquia, ud.barrio, ud.calle_principal, ud.calle_secundaria, ud.numero_casa, ud.referencia,
                   hl.id_cargo, hl.id_tipo_nombramiento, hl.fecha_inicio AS fecha_ingreso, c.id_jefatura, j.id_direccion,
                   ed.id_discapacidad, ed.porcentaje, ed.numero_carnet, ed.observaciones AS observaciones_disc
            FROM empleados e
            LEFT JOIN contacto_personal cp ON e.id_empleado = cp.id_empleado
            LEFT JOIN correo_institucional ci ON e.id_empleado = ci.id_empleado
            LEFT JOIN ubicacion_domiciliaria ud ON e.id_empleado = ud.id_empleado
            LEFT JOIN historial_laboral hl ON e.id_empleado = hl.id_empleado AND hl.activo = TRUE
            LEFT JOIN cargos c ON hl.id_cargo = c.id_cargo
            LEFT JOIN jefaturas j ON c.id_jefatura = j.id_jefatura
            LEFT JOIN empleado_discapacidad ed ON e.id_empleado = ed.id_empleado
            WHERE e.id_empleado = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_empleado]);
    $emp = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$emp) {
        header("Location: index.php");
        exit;
    }
} catch (Exception $e) {
    $mensaje = "<div class='alert error'>Error crítico al cargar información: " . $e->getMessage() . "</div>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGA - Editar Empleado</title>
    <link rel="stylesheet" href="../assets/estilos.css?v=<?= time(); ?>">
</head>
<body>

<div class="container">
    <div class="main-header">
        <h2>Modificar Registro de Personal</h2>
        <a href="index.php" class="btn btn-secondary">Volver al Listado</a>
    </div>
    
    <?php echo $mensaje; ?>

    <form action="" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="foto_actual" value="<?= htmlspecialchars($emp['foto'] ?? '') ?>">
        
        <h3>1. Datos Personales de Identidad</h3>
        <div class="grid-2">
            <div class="form-group">
                <label>Cédula de Identidad <span class="required">*</span></label>
                <input type="text" name="cedula" value="<?= htmlspecialchars($emp['cedula']) ?>" maxlength="10" required>
            </div>
            <div class="form-group">
                <label>Fecha de Nacimiento <span class="required">*</span></label>
                <input type="date" name="fecha_nacimiento" value="<?= $emp['fecha_nacimiento'] ?>" required>
            </div>
            <div class="form-group">
                <label>Primer Nombre <span class="required">*</span></label>
                <input type="text" name="primer_nombre" value="<?= htmlspecialchars($emp['primer_nombre']) ?>" required>
            </div>
            <div class="form-group">
                <label>Segundo Nombre</label>
                <input type="text" name="segundo_nombre" value="<?= htmlspecialchars($emp['segundo_nombre'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Primer Apellido <span class="required">*</span></label>
                <input type="text" name="primer_apellido" value="<?= htmlspecialchars($emp['primer_apellido']) ?>" required>
            </div>
            <div class="form-group">
                <label>Segundo Apellido</label>
                <input type="text" name="segundo_apellido" value="<?= htmlspecialchars($emp['segundo_apellido'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Género</label>
                <select name="id_genero">
                    <option value="">-- Seleccione --</option>
                    <?php foreach($generos as $g): ?>
                        <option value="<?= $g['id_genero'] ?>" <?= $emp['id_genero'] == $g['id_genero'] ? 'selected' : '' ?>><?= htmlspecialchars($g['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Estado Civil</label>
                <select name="id_estado_civil">
                    <option value="">-- Seleccione --</option>
                    <?php foreach($estados_civiles as $e): ?>
                        <option value="<?= $e['id_estado_civil'] ?>" <?= $emp['id_estado_civil'] == $e['id_estado_civil'] ? 'selected' : '' ?>><?= htmlspecialchars($e['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Tipo de Sangre</label>
                <select name="id_tipo_sangre">
                    <option value="">-- Seleccione --</option>
                    <?php foreach($tipos_sangre as $t): ?>
                        <option value="<?= $t['id_tipo_sangre'] ?>" <?= $emp['id_tipo_sangre'] == $t['id_tipo_sangre'] ? 'selected' : '' ?>><?= htmlspecialchars($t['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Autoidentificación Étnica</label>
                <select name="id_etnia">
                    <option value="">-- Seleccione --</option>
                    <?php foreach($etnias as $et): ?>
                        <option value="<?= $et['id_etnia'] ?>" <?= $emp['id_etnia'] == $et['id_etnia'] ? 'selected' : '' ?>><?= htmlspecialchars($et['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Fotografía (Dejar vacío para conservar actual)</label>
                <input type="file" name="foto" accept="image/*">
            </div>
        </div>

        <h3>2. Información de Contacto</h3>
        <div class="grid-2">
            <div class="form-group">
                <label>Teléfono Celular</label>
                <input type="text" name="celular" value="<?= htmlspecialchars($emp['celular'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Teléfono Fijo</label>
                <input type="text" name="telefono" value="<?= htmlspecialchars($emp['telefono'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Correo Electrónico Personal</label>
                <input type="email" name="correo_personal" value="<?= htmlspecialchars($emp['correo_personal'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Correo Institucional</label>
                <input type="email" name="correo_inst" value="<?= htmlspecialchars($emp['correo_inst'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Usuario del Sistema</label>
                <input type="text" name="usuario_inst" value="<?= htmlspecialchars($emp['usuario_inst'] ?? '') ?>">
            </div>
        </div>

        <h3>3. Ubicación Domiciliaria</h3>
        <div class="grid-2">
            <div class="form-group">
                <label>Provincia</label>
                <select name="id_provincia" id="id_provincia">
                    <option value="">-- Seleccione --</option>
                    <?php foreach($provincias as $p): ?>
                        <option value="<?= $p['id_provincia'] ?>" <?= $emp['id_provincia'] == $p['id_provincia'] ? 'selected' : '' ?>><?= htmlspecialchars($p['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Cantón / Ciudad</label>
                <select name="id_ciudad" id="id_ciudad">
                    <option value="<?= $emp['id_ciudad'] ?>"><?= $emp['id_ciudad'] ? 'Conservar Selección Actual' : '-- Seleccione --' ?></option>
                </select>
            </div>
            <div class="form-group">
                <label>Parroquia</label>
                <select name="id_parroquia" id="id_parroquia">
                    <option value="<?= $emp['id_parroquia'] ?>"><?= $emp['id_parroquia'] ? 'Conservar Selección Actual' : '-- Seleccione --' ?></option>
                </select>
            </div>
            <div class="form-group">
                <label>Barrio / Sector</label>
                <input type="text" name="barrio" value="<?= htmlspecialchars($emp['barrio'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Calle Principal</label>
                <input type="text" name="calle_principal" value="<?= htmlspecialchars($emp['calle_principal'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Calle Secundaria</label>
                <input type="text" name="calle_secundaria" value="<?= htmlspecialchars($emp['calle_secundaria'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Número de Casa / Predio</label>
                <input type="text" name="numero_casa" value="<?= htmlspecialchars($emp['numero_casa'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Referencia Domiciliaria</label>
                <textarea name="referencia" rows="1"><?= htmlspecialchars($emp['referencia'] ?? '') ?></textarea>
            </div>
        </div>

        <h3>4. Información Laboral</h3>
        <div class="grid-2">
            <div class="form-group">
                <label>Dirección <span class="required">*</span></label>
                <select name="id_direccion" id="id_direccion" required>
                    <option value="">-- Seleccione Dirección --</option>
                    <?php foreach($direcciones as $dir): ?>
                        <option value="<?= $dir['id_direccion'] ?>" <?= $emp['id_direccion'] == $dir['id_direccion'] ? 'selected' : '' ?>><?= htmlspecialchars($dir['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Jefatura <span class="required">*</span></label>
                <select name="id_jefatura" id="id_jefatura" required>
                    <option value="<?= $emp['id_jefatura'] ?>"><?= $emp['id_jefatura'] ? 'Conservar Selección Actual' : '-- Seleccione Primero Dirección --' ?></option>
                </select>
            </div>
            <div class="form-group">
                <label>Cargo <span class="required">*</span></label>
                <select name="id_cargo" id="id_cargo" required>
                    <option value="<?= $emp['id_cargo'] ?>"><?= $emp['id_cargo'] ? 'Conservar Selección Actual' : '-- Seleccione Primero Jefatura --' ?></option>
                </select>
            </div>
            <div class="form-group">
                <label>Tipo de Nombramiento <span class="required">*</span></label>
                <select name="id_tipo_nombramiento" required>
                    <option value="">-- Seleccione Nombramiento --</option>
                    <option value="1" <?= $emp['id_tipo_nombramiento'] == 1 ? 'selected' : '' ?>>Nombramiento Permanente</option>
                    <option value="2" <?= $emp['id_tipo_nombramiento'] == 2 ? 'selected' : '' ?>>Nombramiento Provisional</option>
                    <option value="3" <?= $emp['id_tipo_nombramiento'] == 3 ? 'selected' : '' ?>>Contrato Ocasional</option>
                </select>
            </div>
            <div class="form-group">
                <label>Fecha de Ingreso / Cambio <span class="required">*</span></label>
                <input type="date" name="fecha_ingreso" value="<?= $emp['fecha_ingreso'] ?? date('Y-m-d') ?>" required>
            </div>
            <div class="checkbox-group">
                <input type="checkbox" name="estado_laboral" id="estado_laboral" value="ACTIVO" <?= strtoupper($emp['estado']) == 'ACTIVO' ? 'checked' : '' ?>>
                <label for="estado_laboral">Activo en la empresa</label>
            </div>
        </div>

        <h3>5. Información de Condiciones de Discapacidad (Si aplica)</h3>
        <div class="grid-2">
            <div class="form-group">
                <label>Tipo de Discapacidad</label>
                <select name="id_discapacidad">
                    <option value="">Ninguna / No Aplica</option>
                    <?php foreach($discapacidades as $d): ?>
                        <option value="<?= $d['id_discapacidad'] ?>" <?= $emp['id_discapacidad'] == $d['id_discapacidad'] ? 'selected' : '' ?>><?= htmlspecialchars($d['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Porcentaje (%)</label>
                <input type="number" name="porcentaje" min="0" max="100" step="0.01" value="<?= htmlspecialchars($emp['porcentaje'] ?? '') ?>" placeholder="0.00">
            </div>
            <div class="form-group">
                <label>Número de Carnet</label>
                <input type="text" name="numero_carnet" value="<?= htmlspecialchars($emp['numero_carnet'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Observaciones Adicionales</label>
                <textarea name="observaciones_disc" rows="1"><?= htmlspecialchars($emp['observaciones_disc'] ?? '') ?></textarea>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top:20px; width:100%; padding:12px;">Guardar Cambios Actualizados</button>
    </form>
</div>

<script>
document.getElementById('id_direccion').addEventListener('change', function() {
    var idDireccion = this.value;
    var selectJefatura = document.getElementById('id_jefatura');
    selectJefatura.innerHTML = '<option value="">Cargando...</option>';
    
    if(!idDireccion) return;

    fetch('get_ajax.php?tipo=jefaturas&id=' + idDireccion)
        .then(response => response.json())
        .then(data => {
            selectJefatura.innerHTML = '<option value="">-- Seleccione Jefatura --</option>';
            data.forEach(item => {
                selectJefatura.innerHTML += `<option value="${item.id_jefatura}">${item.nombre}</option>`;
            });
        });
});

document.getElementById('id_jefatura').addEventListener('change', function() {
    var idJefatura = this.value;
    var selectCargo = document.getElementById('id_cargo');
    selectCargo.innerHTML = '<option value="">Cargando...</option>';
    
    if(!idJefatura) return;

    fetch('get_ajax.php?tipo=cargos&id=' + idJefatura)
        .then(response => response.json())
        .then(data => {
            selectCargo.innerHTML = '<option value="">-- Seleccione Cargo --</option>';
            data.forEach(item => {
                selectCargo.innerHTML += `<option value="${item.id_cargo}">${item.nombre}</option>`;
            });
        });
});
</script>

</body>
</html>