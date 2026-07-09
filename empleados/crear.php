<?php
// empleados/crear.php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . '/../includes/conexion.php'; 

$mensaje = "";

try {
    // 1. CARGAR CATÁLOGOS COMPLETOS PARA LOS SELECTS INITIALES
    $generos = $pdo->query("SELECT id_genero, nombre FROM generos ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    $estados_civiles = $pdo->query("SELECT id_estado_civil, nombre FROM estados_civiles ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    $tipos_sangre = $pdo->query("SELECT id_tipo_sangre, nombre FROM tipos_sangre ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    $etnias = $pdo->query("SELECT id_etnia, nombre FROM etnias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC); 
    $discapacidades = $pdo->query("SELECT id_discapacidad, nombre FROM discapacidades WHERE estado = 'ACTIVO' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    
    $provincias = $pdo->query("SELECT id_provincia, nombre FROM provincias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    $direcciones = $pdo->query("SELECT id_direccion, nombre FROM direcciones WHERE estado = 'ACTIVO' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $mensaje = "<div class='alert error'>Error crítico al cargar componentes: " . $e->getMessage() . "</div>";
}

// 2. PROCESAR EL REGISTRO (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $pdo->beginTransaction(); 

        // --- PROCESAMIENTO DE LA FOTO ---
        $nombre_foto = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $nombre_foto = $_POST['cedula'] . '_' . time() . '.' . $extension;
            $ruta_destino = '../uploads/' . $nombre_foto;
            
            if (!is_dir('../uploads/')) {
                mkdir('../uploads/', 0777, true);
            }
            move_uploaded_file($_FILES['foto']['tmp_name'], $ruta_destino);
        }

        // --- PASO 1: Insertar en la tabla empleados ---
        $sql_ins_emp = "INSERT INTO empleados (cedula, primer_nombre, segundo_nombre, primer_apellido, segundo_apellido, 
                                            fecha_nacimiento, id_genero, id_estado_civil, foto, estado, id_tipo_sangre, id_etnia)
                        VALUES (:cedula, :primer_nombre, :segundo_nombre, :primer_apellido, :segundo_apellido, 
                                :fecha_nacimiento, :id_genero, :id_estado_civil, :foto, :estado, :id_tipo_sangre, :id_etnia)
                        RETURNING id_empleado";
        
        $stmt_emp = $pdo->prepare($sql_ins_emp);
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
            ':id_etnia'         => $_POST['id_etnia'] ?: null
        ]);
        
        // Obtener el ID recién creado de forma segura con RETURNING (PostgreSQL)
        $id_empleado = $stmt_emp->fetchColumn();

        if (!$id_empleado) {
            throw new Exception("No se pudo generar el ID del nuevo empleado.");
        }

        // --- PASO 2: Insertar contacto_personal ---
        if (!empty($_POST['celular']) || !empty($_POST['telefono']) || !empty($_POST['correo_personal'])) {
            $sql_cont = "INSERT INTO contacto_personal (id_empleado, celular, telephone, correo_personal) VALUES (?, ?, ?, ?)";
            // Nota: Cambia 'telephone' por 'telefono' si es el nombre real de tu columna en esa tabla
            $sql_cont = "INSERT INTO contacto_personal (id_empleado, celular, telefono, correo_personal) VALUES (?, ?, ?, ?)";
            $pdo->prepare($sql_cont)->execute([$id_empleado, $_POST['celular'], $_POST['telefono'], $_POST['correo_personal']]);
        }

        // --- PASO 3: Insertar correo_institucional ---
        if (!empty($_POST['correo_inst'])) {
            $sql_corr = "INSERT INTO correo_institucional (id_empleado, correo, usuario, activo) VALUES (?, ?, ?, TRUE)";
            $pdo->prepare($sql_corr)->execute([$id_empleado, $_POST['correo_inst'], $_POST['usuario_inst']]);
        }

        // --- PASO 4: Insertar ubicacion_domiciliaria ---
        $sql_dom = "INSERT INTO ubicacion_domiciliaria (id_empleado, id_provincia, id_ciudades, id_parroquia, barrio, calle_principal, calle_secundaria, numero_casa, referencia) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql_dom)->execute([
            $id_empleado, $_POST['id_provincia'] ?: null, $_POST['id_ciudad'] ?: null, $_POST['id_parroquia'] ?: null, 
            $_POST['barrio'], $_POST['calle_principal'], $_POST['calle_secundaria'], $_POST['numero_casa'], $_POST['referencia']
        ]);

        // --- PASO 5: Historial Laboral y Sincronización Estructural ---
        if (!empty($_POST['id_cargo'])) {
            $id_cargo = (int)$_POST['id_cargo'];
            $id_direccion = (int)$_POST['id_direccion'];
            $id_jefatura = (int)$_POST['id_jefatura'];

            // Insertar el puesto inicial activo
            $sql_hist = "INSERT INTO historial_laboral (id_empleado, id_cargo, id_tipo_nombramiento, fecha_inicio, activo) 
                         VALUES (?, ?, ?, ?, TRUE)";
            $pdo->prepare($sql_hist)->execute([
                $id_empleado, $id_cargo, $_POST['id_tipo_nombramiento'] ?: null, $_POST['fecha_ingreso'] ?: date('Y-m-d')
            ]);

            // --- PASO 5.2: ASIGNACIÓN DE MANDOS AUTOMÁTICOS ---
            $stmt_chk = $pdo->prepare("SELECT nombre FROM cargos WHERE id_cargo = ?");
            $stmt_chk->execute([$id_cargo]);
            $cargo_info = $stmt_chk->fetch(PDO::FETCH_ASSOC);
            $nombre_cargo_upper = strtoupper($cargo_info['nombre'] ?? '');

            if (strpos($nombre_cargo_upper, 'DIRECTOR') !== false || strpos($nombre_cargo_upper, 'DIRECTORA') !== false) {
                $stmt_dir_exists = $pdo->prepare("SELECT id_director FROM directores WHERE id_direccion = ?");
                $stmt_dir_exists->execute([$id_direccion]);
                if ($stmt_dir_exists->fetch()) {
                    $pdo->prepare("UPDATE directores SET id_empleado = ?, estado = 'ACTIVO' WHERE id_direccion = ?")
                        ->execute([$id_empleado, $id_direccion]);
                } else {
                    $pdo->prepare("INSERT INTO directores (id_direccion, id_empleado, estado) VALUES (?, ?, 'ACTIVO')")
                        ->execute([$id_direccion, $id_empleado]);
                }
            }
            else if (strpos($nombre_cargo_upper, 'JEFE') !== false || strpos($nombre_cargo_upper, 'JEFA') !== false) {
                $pdo->prepare("UPDATE jefaturas SET id_empleado_jefe = ? WHERE id_jefatura = ?")
                    ->execute([$id_empleado, $id_jefatura]);
            }
        }

        // --- PASO 6: Discapacidad ---
        if (!empty($_POST['id_discapacidad'])) {
            $sql_disc = "INSERT INTO empleado_discapacidad (id_empleado, id_discapacidad, porcentaje, numero_carnet, observaciones) VALUES (?, ?, ?, ?, ?)";
            $pdo->prepare($sql_disc)->execute([
                $id_empleado, $_POST['id_discapacidad'], $_POST['porcentaje'] ?: 0, $_POST['numero_carnet'], $_POST['observaciones_disc']
            ]);
        }

        $pdo->commit(); 
        header("Location: index.php?success=1");
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack(); 
        if ($e->getCode() == 'P0001' || strpos($e->getMessage(), 'duplicar funciones') !== false) {
            $mensaje = "<div class='alert error'>⚠️ <strong>Restricción de Estructura:</strong> El cargo asignado es directivo y ya se encuentra ocupado por otro líder activo.</div>";
        } else {
            $mensaje = "<div class='alert error'>Error en la base de datos: " . $e->getMessage() . "</div>";
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
    <title>SGA - Registrar Nuevo Personal</title>
    <link rel="stylesheet" href="../assets/estilos.css?v=<?= time(); ?>">
</head>
<body>

<div class="container">
    <div class="main-header">
        <h2>Registrar Nuevo Expediente de Personal</h2>
        <a href="index.php" class="btn btn-secondary">Volver al Listado</a>
    </div>
    
    <?php echo $mensaje; ?>

    <form action="" method="POST" enctype="multipart/form-data">
        
        <h3>1. Datos Personales de Identidad</h3>
        <div class="grid-2">
            <div class="form-group">
                <label>Cédula de Identidad <span class="required">*</span></label>
                <input type="text" name="cedula" placeholder="Ej. 0201234567" maxlength="10" required>
            </div>
            <div class="form-group">
                <label>Fecha de Nacimiento <span class="required">*</span></label>
                <input type="date" name="fecha_nacimiento" required>
            </div>
            <div class="form-group">
                <label>Primer Nombre <span class="required">*</span></label>
                <input type="text" name="primer_nombre" required>
            </div>
            <div class="form-group">
                <label>Segundo Nombre</label>
                <input type="text" name="segundo_nombre">
            </div>
            <div class="form-group">
                <label>Primer Apellido <span class="required">*</span></label>
                <input type="text" name="primer_apellido" required>
            </div>
            <div class="form-group">
                <label>Segundo Apellido</label>
                <input type="text" name="segundo_apellido">
            </div>
            <div class="form-group">
                <label>Género</label>
                <select name="id_genero">
                    <option value="">-- Seleccione --</option>
                    <?php foreach($generos as $g): ?>
                        <option value="<?= $g['id_genero'] ?>"><?= htmlspecialchars($g['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Estado Civil</label>
                <select name="id_estado_civil">
                    <option value="">-- Seleccione --</option>
                    <?php foreach($estados_civiles as $e): ?>
                        <option value="<?= $e['id_estado_civil'] ?>"><?= htmlspecialchars($e['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Tipo de Sangre</label>
                <select name="id_tipo_sangre">
                    <option value="">-- Seleccione --</option>
                    <?php foreach($tipos_sangre as $t): ?>
                        <option value="<?= $t['id_tipo_sangre'] ?>"><?= htmlspecialchars($t['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Autoidentificación Étnica</label>
                <select name="id_etnia">
                    <option value="">-- Seleccione --</option>
                    <?php foreach($etnias as $et): ?>
                        <option value="<?= $et['id_etnia'] ?>"><?= htmlspecialchars($et['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Fotografía del Empleado</label>
                <input type="file" name="foto" accept="image/*">
            </div>
        </div>

        <h3>2. Información de Contacto</h3>
        <div class="grid-2">
            <div class="form-group">
                <label>Teléfono Celular</label>
                <input type="text" name="celular">
            </div>
            <div class="form-group">
                <label>Teléfono Fijo</label>
                <input type="text" name="telefono">
            </div>
            <div class="form-group">
                <label>Correo Electrónico Personal</label>
                <input type="email" name="correo_personal">
            </div>
            <div class="form-group">
                <label>Correo Institucional</label>
                <input type="email" name="correo_inst">
            </div>
            <div class="form-group">
                <label>Usuario del Sistema</label>
                <input type="text" name="usuario_inst">
            </div>
        </div>

        <h3>3. Ubicación Domiciliaria</h3>
        <div class="grid-2">
            <div class="form-group">
                <label>Provincia</label>
                <select name="id_provincia" id="id_provincia">
                    <option value="">-- Seleccione --</option>
                    <?php foreach($provincias as $p): ?>
                        <option value="<?= $p['id_provincia'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Cantón / Ciudad</label>
                <select name="id_ciudad" id="id_ciudad">
                    <option value="">-- Seleccione Provincia Primero --</option>
                </select>
            </div>
            <div class="form-group">
                <label>Parroquia</label>
                <select name="id_parroquia" id="id_parroquia">
                    <option value="">-- Seleccione Cantón Primero --</option>
                </select>
            </div>
            <div class="form-group">
                <label>Barrio / Sector</label>
                <input type="text" name="barrio">
            </div>
            <div class="form-group">
                <label>Calle Principal</label>
                <input type="text" name="calle_principal">
            </div>
            <div class="form-group">
                <label>Calle Secundaria</label>
                <input type="text" name="calle_secundaria">
            </div>
            <div class="form-group">
                <label>Número de Casa / Predio</label>
                <input type="text" name="numero_casa">
            </div>
            <div class="form-group">
                <label>Referencia Domiciliaria</label>
                <textarea name="referencia" rows="1"></textarea>
            </div>
        </div>

        <h3>4. Información Laboral Estructural</h3>
        <div class="grid-2">
            <div class="form-group">
                <label>Dirección Macro <span class="required">*</span></label>
                <select name="id_direccion" id="id_direccion" required>
                    <option value="">-- Seleccione Dirección --</option>
                    <?php foreach($direcciones as $dir): ?>
                        <option value="<?= $dir['id_direccion'] ?>"><?= htmlspecialchars($dir['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Jefatura / Departamento <span class="required">*</span></label>
                <select name="id_jefatura" id="id_jefatura" required>
                    <option value="">-- Seleccione Dirección Primero --</option>
                </select>
            </div>
            <div class="form-group">
                <label>Cargo Operativo o Jerárquico <span class="required">*</span></label>
                <select name="id_cargo" id="id_cargo" required>
                    <option value="">-- Seleccione Jefatura Primero --</option>
                </select>
            </div>
            <div class="form-group">
                <label>Tipo de Nombramiento <span class="required">*</span></label>
                <select name="id_tipo_nombramiento" required>
                    <option value="">-- Seleccione Nombramiento --</option>
                    <option value="1">Nombramiento Permanente</option>
                    <option value="2">Nombramiento Provisional</option>
                    <option value="3">Contrato Ocasional</option>
                </select>
            </div>
            <div class="form-group">
                <label>Fecha de Asignación / Ingreso <span class="required">*</span></label>
                <input type="date" name="fecha_ingreso" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="checkbox-group" style="margin-top: 25px;">
                <input type="checkbox" name="estado_laboral" id="estado_laboral" value="ACTIVO" checked>
                <label for="estado_laboral"><strong>Activo por defecto al crear</strong></label>
            </div>
        </div>

        <h3>5. Información de Condiciones de Discapacidad (Si aplica)</h3>
        <div class="grid-2">
            <div class="form-group">
                <label>Tipo de Discapacidad</label>
                <select name="id_discapacidad">
                    <option value="">Ninguna / No Aplica</option>
                    <?php foreach($discapacidades as $d): ?>
                        <option value="<?= $d['id_discapacidad'] ?>"><?= htmlspecialchars($d['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Porcentaje (%)</label>
                <input type="number" name="porcentaje" min="0" max="100" step="0.01" placeholder="0.00">
            </div>
            <div class="form-group">
                <label>Número de Carnet</label>
                <input type="text" name="numero_carnet">
            </div>
            <div class="form-group">
                <label>Observaciones Adicionales</label>
                <textarea name="observaciones_disc" rows="1"></textarea>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 16px; font-weight: bold; margin-top: 20px; cursor: pointer;">💾 Registrar Colaborador y Guardar Estructura</button>
    </form>
</div>

<script>
// Lógica AJAX unificada para encadenamiento estructural
document.getElementById('id_direccion').addEventListener('change', function() {
    var idDireccion = this.value;
    var selectJefatura = document.getElementById('id_jefatura');
    var selectCargo = document.getElementById('id_cargo');
    selectJefatura.innerHTML = '<option value="">Cargando...</option>';
    selectCargo.innerHTML = '<option value="">-- Seleccione Jefatura Primero --</option>';
    
    if(!idDireccion) {
        selectJefatura.innerHTML = '<option value="">-- Seleccione Dirección Primero --</option>';
        return;
    }
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
    
    if(!idJefatura) {
        selectCargo.innerHTML = '<option value="">-- Seleccione Jefatura Primero --</option>';
        return;
    }
    fetch('get_ajax.php?tipo=cargos&id=' + idJefatura)
        .then(response => response.json())
        .then(data => {
            selectCargo.innerHTML = '<option value="">-- Seleccione Cargo --</option>';
            data.forEach(item => {
                selectCargo.innerHTML += `<option value="${item.id_cargo}">${item.nombre}</option>`;
            });
        });
});

document.getElementById('id_provincia').addEventListener('change', function() {
    var idProv = this.value;
    var selectCiu = document.getElementById('id_ciudad');
    var selectPar = document.getElementById('id_parroquia');
    selectCiu.innerHTML = '<option value="">Cargando...</option>';
    selectPar.innerHTML = '<option value="">-- Seleccione Cantón Primero --</option>';
    if(!idProv) { selectCiu.innerHTML = '<option value="">-- Seleccione Provincia Primero --</option>'; return; }
    
    fetch('get_ajax.php?tipo=ciudades&id=' + idProv)
        .then(r => r.json()).then(data => {
            selectCiu.innerHTML = '<option value="">-- Seleccione Cantón --</option>';
            data.forEach(i => { selectCiu.innerHTML += `<option value="${i.id_ciudad}">${i.nombre}</option>`; });
        });
});

document.getElementById('id_ciudad').addEventListener('change', function() {
    var idCiu = this.value;
    var selectPar = document.getElementById('id_parroquia');
    selectPar.innerHTML = '<option value="">Cargando...</option>';
    if(!idCiu) { selectPar.innerHTML = '<option value="">-- Seleccione Cantón Primero --</option>'; return; }
    
    fetch('get_ajax.php?tipo=parroquias&id=' + idCiu)
        .then(r => r.json()).then(data => {
            selectPar.innerHTML = '<option value="">-- Seleccione Parroquia --</option>';
            data.forEach(i => { selectPar.innerHTML += `<option value="${i.id_parroquia}">${i.nombre}</option>`; });
        });
});
</script>

</body>
</html>