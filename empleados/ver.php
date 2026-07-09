<?php
// empleados/ver.php
session_start();

// Control de acceso: Si no hay sesión iniciada, redirige al login
if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Aseguramos la ruta absoluta al archivo de conexión usando __DIR__
require_once __DIR__ . '/../includes/conexion.php'; 

$mensaje = "";
$emp = null;

// Validar que se haya enviado un ID válido por la URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id_empleado = $_GET['id'];

try {
    // CONSULTA GENERAL: Extrae la información de todas las tablas asociadas al empleado
    $sql = "SELECT 
                e.*,
                CONCAT(e.primer_apellido, ' ', e.segundo_apellido, ' ', e.primer_nombre, ' ', e.segundo_nombre) AS nombre_completo,
                g.nombre AS genero,
                ec.nombre AS estado_civil,
                ts.nombre AS tipo_sangre,
                et.nombre AS etnia,
                cp.celular, cp.telefono, cp.correo_personal,
                ci.correo AS correo_institucional, ci.usuario AS usuario_sistema,
                prov.nombre AS provincia, c_mun.nombre AS ciudad, parr.nombre AS parroquia,
                ud.barrio, ud.calle_principal, ud.calle_secundaria, ud.numero_casa, ud.referencia,
                c.nombre AS cargo_actual,
                d.nombre AS direccion_laboral,
                j.nombre AS jefatura_laboral,
                hl.fecha_inicio AS fecha_ingreso_puesto,
                hl.id_tipo_nombramiento,
                disc.nombre AS tipo_discapacidad,
                ed.porcentaje, ed.numero_carnet, ed.observaciones AS observaciones_discapacidad
            FROM empleados e
            LEFT JOIN generos g ON e.id_genero = g.id_genero
            LEFT JOIN estados_civiles ec ON e.id_estado_civil = ec.id_estado_civil
            LEFT JOIN tipos_sangre ts ON e.id_tipo_sangre = ts.id_tipo_sangre
            LEFT JOIN etnias et ON e.id_etnia = et.id_etnia
            LEFT JOIN contacto_personal cp ON e.id_empleado = cp.id_empleado
            LEFT JOIN correo_institucional ci ON e.id_empleado = ci.id_empleado
            LEFT JOIN ubicacion_domiciliaria ud ON e.id_empleado = ud.id_empleado
            LEFT JOIN provincias prov ON ud.id_provincia = prov.id_provincia
            LEFT JOIN ciudades c_mun ON ud.id_ciudades = c_mun.id_ciudad
            LEFT JOIN parroquias parr ON ud.id_parroquia = parr.id_parroquia
            LEFT JOIN historial_laboral hl ON e.id_empleado = hl.id_empleado AND hl.activo = TRUE
            LEFT JOIN cargos c ON hl.id_cargo = c.id_cargo
            LEFT JOIN jefaturas j ON c.id_jefatura = j.id_jefatura
            LEFT JOIN direcciones d ON j.id_direccion = d.id_direccion
            LEFT JOIN empleado_discapacidad ed ON e.id_empleado = ed.id_empleado
            LEFT JOIN discapacidades disc ON ed.id_discapacidad = disc.id_discapacidad
            WHERE e.id_empleado = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_empleado]);
    $emp = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$emp) {
        $mensaje = "<div class='alert error'>El empleado con ID proporcionado no existe en el sistema.</div>";
    }

} catch (Exception $e) {
    $mensaje = "<div class='alert error'>Error al cargar el perfil del empleado: " . $e->getMessage() . "</div>";
}

// Mapeo manual del tipo de nombramiento guardado
$nombramiento_texto = "Sin Asignar";
if (isset($emp['id_tipo_nombramiento'])) {
    switch ($emp['id_tipo_nombramiento']) {
        case 1: $nombramiento_texto = "Nombramiento Permanente"; break;
        case 2: $nombramiento_texto = "Nombramiento Provisional"; break;
        case 3: $nombramiento_texto = "Contrato Ocasional"; break;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGA - Vista de Empleado</title>
    <link rel="stylesheet" href="../assets/estilos.css?v=<?= time(); ?>">
    <style>
        .perfil-container {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        .perfil-header {
            display: flex;
            align-items: center;
            gap: 25px;
            border-bottom: 2px solid #edf2f7;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .perfil-foto {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #3182ce;
        }
        .perfil-titulo h2 { margin: 0; color: #2d3748; }
        .perfil-titulo p { margin: 5px 0 0 0; color: #718096; font-size: 16px; }
        
        .seccion-perfil {
            margin-bottom: 25px;
        }
        .seccion-perfil h3 {
            border-left: 4px solid #3182ce;
            padding-left: 10px;
            color: #2d3748;
            margin-bottom: 15px;
            font-size: 18px;
        }
        .data-label { font-weight: bold; color: #4a5568; }
        .data-value { color: #1a202c; }
    </style>
</head>
<body>

<div class="container">
    <div class="main-header">
        <h2>Ficha de Datos del Empleado</h2>
        <div class="header-actions">
            <a href="index.php" class="btn btn-secondary">Volver al Listado</a>
            <?php if ($emp): ?>
                <a href="editar.php?id=<?= $emp['id_empleado'] ?>" class="btn btn-warning" style="color:white; text-decoration:none; font-weight:bold; padding:10px 15px; border-radius:4px;">Editar Datos</a>
                <a href="../reportes/imprimir_empleado.php?id=<?= $emp['id_empleado'] ?>" target="_blank" class="btn btn-primary" style="margin:0;">Imprimir Ficha 🖨</a>
            <?php endif; ?>
        </div>
    </div>

    <?php echo $mensaje; ?>

    <?php if ($emp): ?>
        <div class="perfil-container">
            
            <div class="perfil-header">
                <?php if (!empty($emp['foto']) && file_exists('../uploads/' . $emp['foto'])): ?>
                    <img src="../uploads/<?= htmlspecialchars($emp['foto']) ?>" class="perfil-foto" alt="Foto del Empleado">
                <?php else: ?>
                    <img src="../assets/img/default-avatar.png" class="perfil-foto" alt="Sin foto asignada">
                <?php endif; ?>
                <div class="perfil-titulo">
                    <h2><?= htmlspecialchars($emp['nombre_completo']) ?></h2>
                    <p><strong>Cargo:</strong> <?= htmlspecialchars($emp['cargo_actual'] ?? 'Sin Asignar') ?></p>
                    <p>
                        <span class="badge <?= strtoupper($emp['estado']) == 'ACTIVO' ? 'badge-success' : 'badge-danger' ?>">
                            <?= htmlspecialchars($emp['estado']) ?>
                        </span>
                    </p>
                </div>
            </div>

            <div class="seccion-perfil">
                <h3>1. Datos Personales de Identidad</h3>
                <div class="grid-2">
                    <div><span class="data-label">Cédula de Identidad:</span> <span class="data-value"><?= htmlspecialchars($emp['cedula']) ?></span></div>
                    <div><span class="data-label">Fecha de Nacimiento:</span> <span class="data-value"><?= htmlspecialchars($emp['fecha_nacimiento']) ?></span></div>
                    <div><span class="data-label">Género:</span> <span class="data-value"><?= htmlspecialchars($emp['genero'] ?? 'N/A') ?></span></div>
                    <div><span class="data-label">Estado Civil:</span> <span class="data-value"><?= htmlspecialchars($emp['estado_civil'] ?? 'N/A') ?></span></div>
                    <div><span class="data-label">Tipo de Sangre:</span> <span class="data-value"><?= htmlspecialchars($emp['tipo_sangre'] ?? 'N/A') ?></span></div>
                    <div><span class="data-label">Autoidentificación Étnica:</span> <span class="data-value"><?= htmlspecialchars($emp['etnia'] ?? 'N/A') ?></span></div>
                </div>
            </div>

            <div class="seccion-perfil">
                <h3>2. Información de Contacto</h3>
                <div class="grid-2">
                    <div><span class="data-label">Teléfono Celular:</span> <span class="data-value"><?= htmlspecialchars($emp['celular'] ?? 'N/A') ?></span></div>
                    <div><span class="data-label">Teléfono Fijo:</span> <span class="data-value"><?= htmlspecialchars($emp['telefono'] ?? 'N/A') ?></span></div>
                    <div><span class="data-label">Correo Personal:</span> <span class="data-value"><?= htmlspecialchars($emp['correo_personal'] ?? 'N/A') ?></span></div>
                    <div><span class="data-label">Correo Institucional:</span> <span class="data-value"><?= htmlspecialchars($emp['correo_institucional'] ?? 'N/A') ?></span></div>
                    <div><span class="data-label">Usuario del Sistema:</span> <span class="data-value"><?= htmlspecialchars($emp['usuario_sistema'] ?? 'N/A') ?></span></div>
                </div>
            </div>

            <div class="seccion-perfil">
                <h3>3. Ubicación Domiciliaria</h3>
                <div class="grid-2">
                    <div><span class="data-label">Provincia:</span> <span class="data-value"><?= htmlspecialchars($emp['provincia'] ?? 'N/A') ?></span></div>
                    <div><span class="data-label">Ciudad / Cantón:</span> <span class="data-value"><?= htmlspecialchars($emp['ciudad'] ?? 'N/A') ?></span></div>
                    <div><span class="data-label">Parroquia:</span> <span class="data-value"><?= htmlspecialchars($emp['parroquia'] ?? 'N/A') ?></span></div>
                    <div><span class="data-label">Barrio / Sector:</span> <span class="data-value"><?= htmlspecialchars($emp['barrio'] ?? 'N/A') ?></span></div>
                    <div><span class="data-label">Calle Principal:</span> <span class="data-value"><?= htmlspecialchars($emp['calle_principal'] ?? 'N/A') ?></span></div>
                    <div><span class="data-label">Calle Secundaria:</span> <span class="data-value"><?= htmlspecialchars($emp['calle_secondary'] ?? 'N/A') ?></span></div>
                    <div><span class="data-label">Número de Casa:</span> <span class="data-value"><?= htmlspecialchars($emp['numero_casa'] ?? 'N/A') ?></span></div>
                    <div><span class="data-label">Referencia:</span> <span class="data-value"><?= htmlspecialchars($emp['referencia'] ?? 'N/A') ?></span></div>
                </div>
            </div>

            <div class="seccion-perfil">
                <h3>4. Información Laboral Actual</h3>
                <div class="grid-2">
                    <div><span class="data-label">Dirección (Área):</span> <span class="data-value"><?= htmlspecialchars($emp['direccion_laboral'] ?? 'Sin Asignar') ?></span></div>
                    <div><span class="data-label">Jefatura:</span> <span class="data-value"><?= htmlspecialchars($emp['jefatura_laboral'] ?? 'Sin Asignar') ?></span></div>
                    <div><span class="data-label">Cargo Desempeñado:</span> <span class="data-value"><?= htmlspecialchars($emp['cargo_actual'] ?? 'Sin Asignar') ?></span></div>
                    <div><span class="data-label">Tipo de Nombramiento:</span> <span class="data-value"><strong><?= $nombramiento_texto ?></strong></span></div>
                    <div><span class="data-label">Fecha de Ingreso al Puesto:</span> <span class="data-value"><?= htmlspecialchars($emp['fecha_ingreso_puesto'] ?? 'N/A') ?></span></div>
                </div>
            </div>

            <div class="seccion-perfil">
                <h3>5. Condiciones de Discapacidad</h3>
                <div class="grid-2">
                    <div><span class="data-label">Posee Discapacidad:</span> <span class="data-value"><?= htmlspecialchars($emp['tipo_discapacidad'] ?? 'Ninguna / No Aplica') ?></span></div>
                    <div><span class="data-label">Porcentaje asignado:</span> <span class="data-value"><?= !empty($emp['porcentaje']) ? htmlspecialchars($emp['porcentaje'])."%" : '0.00%' ?></span></div>
                    <div><span class="data-label">Número de Carnet CONADIS/MSPE:</span> <span class="data-value"><?= htmlspecialchars($emp['numero_carnet'] ?? 'N/A') ?></span></div>
                    <div><span class="data-label">Observaciones de Condición:</span> <span class="data-value"><?= htmlspecialchars($emp['observaciones_discapacidad'] ?? 'Ninguna') ?></span></div>
                </div>
            </div>

        </div>
    <?php endif; ?>
</div>

</body>
</html>