<?php
// reportes/imprimir_empleado.php
session_start();

// Control de acceso: Si no hay sesión iniciada, redirige al login
if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Aseguramos la ruta absoluta al archivo de conexión usando __DIR__
require_once __DIR__ . '/../includes/conexion.php'; 

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "ID de empleado no válido.";
    exit;
}

$id_empleado = $_GET['id'];

try {
    // CONSULTA GENERAL: Extrae toda la información estructurada del empleado
    $sql = "SELECT 
                e.*,
                CONCAT(e.primer_apellido, ' ', e.segundo_apellido, ' ', e.primer_nombre, ' ', e.segundo_nombre) AS nombre_completo,
                g.nombre AS genero,
                ec.nombre AS estado_civil,
                ts.nombre AS tipo_sangre,
                et.nombre AS etnia,
                cp.celular, cp.telefono, cp.correo_personal,
                ci.correo AS correo_institucional,
                prov.nombre AS provincia, c_mun.nombre AS ciudad, parr.nombre AS parroquia,
                ud.barrio, ud.calle_principal, ud.calle_secundaria, ud.numero_casa, ud.referencia,
                c.nombre AS cargo_actual,
                d.nombre AS direccion_laboral,
                j.nombre AS jefatura_laboral,
                hl.fecha_inicio AS fecha_ingreso_puesto,
                hl.id_tipo_nombramiento,
                disc.nombre AS tipo_discapacidad,
                ed.porcentaje, ed.numero_carnet
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
        echo "El empleado solicitado no existe.";
        exit;
    }

} catch (Exception $e) {
    echo "Error al generar la ficha de impresión: " . $e->getMessage();
    exit;
}

// Mapeo manual del tipo de nombramiento
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
    <title>Ficha_Empleado_<?= htmlspecialchars($emp['cedula']) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.4;
            margin: 20px;
        }
        .header-print {
            text-align: center;
            border-bottom: 3px double #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header-print h1 { margin: 0; font-size: 20px; text-transform: uppercase; }
        .header-print p { margin: 5px 0 0 0; font-size: 13px; color: #666; }
        
        .ficha-contenedor {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .foto-bloque {
            text-align: center;
            width: 130px;
        }
        .foto-print {
            width: 120px;
            height: 140px;
            object-fit: cover;
            border: 1px solid #000;
            margin-bottom: 5px;
        }
        .datos-bloque {
            flex: 1;
        }
        
        .titulo-seccion {
            background-color: #f2f2f2;
            padding: 5px 10px;
            font-weight: bold;
            font-size: 13px;
            border-left: 5px solid #333;
            margin-top: 15px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        table td {
            padding: 5px;
            vertical-align: top;
            border-bottom: 1px dashed #ddd;
        }
        .label {
            font-weight: bold;
            width: 25%;
        }
        .valor {
            width: 25%;
        }
        
        /* Estilos específicos para la hoja de impresión */
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }
        
        .btn-imprimir {
            background-color: #4A5568;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

    <div class="no-print" style="text-align: right;">
        <button onclick="window.print();" class="btn-imprimir">Confirmar Impresión 🖨</button>
        <button onclick="window.close();" class="btn-imprimir" style="background-color:#e53e3e;">Cerrar Pestaña</button>
    </div>

    <div class="header-print">
        <h1>Sistema de Gestión Administrativa</h1>
        <p>Ficha Completa de Registro de Personal Técnico / Administrativo</p>
    </div>

    <div class="ficha-contenedor">
        <div class="foto-bloque">
            <?php if (!empty($emp['foto']) && file_exists('../uploads/' . $emp['foto'])): ?>
                <img src="../uploads/<?= htmlspecialchars($emp['foto']) ?>" class="foto-print" alt="Foto">
            <?php else: ?>
                <img src="../assets/img/default-avatar.png" class="foto-print" alt="Sin foto">
            <?php endif; ?>
            <div><strong>ID:</strong> <?= $emp['id_empleado'] ?></div>
            <div style="margin-top:5px;"><span style="border: 1px solid #000; padding: 2px 5px; font-weight:bold;"><?= htmlspecialchars($emp['estado']) ?></span></div>
        </div>

        <div class="datos-bloque">
            <div class="titulo-seccion">1. Datos Personales y de Identidad</div>
            <table>
                <tr>
                    <td class="label">Apellidos y Nombres:</td>
                    <td class="valor" colspan="3"><strong><?= htmlspecialchars($emp['nombre_completo']) ?></strong></td>
                </tr>
                <tr>
                    <td class="label">Cédula de Identidad:</td>
                    <td class="valor"><?= htmlspecialchars($emp['cedula']) ?></td>
                    <td class="label">Fecha Nacimiento:</td>
                    <td class="valor"><?= htmlspecialchars($emp['fecha_nacimiento']) ?></td>
                </tr>
                <tr>
                    <td class="label">Género:</td>
                    <td class="valor"><?= htmlspecialchars($emp['genero'] ?? 'N/A') ?></td>
                    <td class="label">Estado Civil:</td>
                    <td class="valor"><?= htmlspecialchars($emp['estado_civil'] ?? 'N/A') ?></td>
                </tr>
                <tr>
                    <td class="label">Tipo de Sangre:</td>
                    <td class="valor"><?= htmlspecialchars($emp['tipo_sangre'] ?? 'N/A') ?></td>
                    <td class="label">Etnia:</td>
                    <td class="valor"><?= htmlspecialchars($emp['etnia'] ?? 'N/A') ?></td>
                </tr>
            </table>
        </div>
    </div>

    <div class="titulo-seccion">2. Información Laboral e Institucional</div>
    <table>
        <tr>
            <td class="label">Dirección / Área:</td>
            <td class="valor" colspan="3"><?= htmlspecialchars($emp['direccion_laboral'] ?? 'Sin Asignar') ?></td>
        </tr>
        <tr>
            <td class="label">Jefatura Inmediata:</td>
            <td class="valor" colspan="3"><?= htmlspecialchars($emp['jefatura_laboral'] ?? 'Sin Asignar') ?></td>
        </tr>
        <tr>
            <td class="label">Cargo Funcional:</td>
            <td class="valor"><?= htmlspecialchars($emp['cargo_actual'] ?? 'Sin Asignar') ?></td>
            <td class="label">Tipo Nombramiento:</td>
            <td class="valor"><?= $nombramiento_texto ?></td>
        </tr>
        <tr>
            <td class="label">Fecha Ingreso Puesto:</td>
            <td class="valor"><?= htmlspecialchars($emp['fecha_ingreso_puesto'] ?? 'N/A') ?></td>
            <td class="label">Correo Institucional:</td>
            <td class="valor"><?= htmlspecialchars($emp['correo_institucional'] ?? 'N/A') ?></td>
        </tr>
    </table>

    <div class="titulo-seccion">3. Medios de Contacto y Ubicación Domiciliaria</div>
    <table>
        <tr>
            <td class="label">Teléfono Celular:</td>
            <td class="valor"><?= htmlspecialchars($emp['celular'] ?? 'N/A') ?></td>
            <td class="label">Teléfono Fijo:</td>
            <td class="valor"><?= htmlspecialchars($emp['telefono'] ?? 'N/A') ?></td>
        </tr>
        <tr>
            <td class="label">Correo Personal:</td>
            <td class="valor" colspan="3"><?= htmlspecialchars($emp['correo_personal'] ?? 'N/A') ?></td>
        </tr>
        <tr>
            <td class="label">Provincia / Cantón:</td>
            <td class="valor"><?= htmlspecialchars($emp['provincia'] ?? 'N/A') ?> / <?= htmlspecialchars($emp['ciudad'] ?? 'N/A') ?></td>
            <td class="label">Parroquia / Barrio:</td>
            <td class="valor"><?= htmlspecialchars($emp['parroquia'] ?? 'N/A') ?> / <?= htmlspecialchars($emp['barrio'] ?? 'N/A') ?></td>
        </tr>
        <tr>
            <td class="label">Dirección Domiciliaria:</td>
            <td class="valor" colspan="3">
                Calle Principal: <?= htmlspecialchars($emp['calle_principal'] ?? 'N/A') ?> 
                e/ Calle Sec: <?= htmlspecialchars($emp['calle_secundaria'] ?? 'N/A') ?> 
                Nº: <?= htmlspecialchars($emp['numero_casa'] ?? 'N/A') ?>
            </td>
        </tr>
        <tr>
            <td class="label">Referencia:</td>
            <td class="valor" colspan="3"><?= htmlspecialchars($emp['referencia'] ?? 'Ninguna') ?></td>
        </tr>
    </table>

    <div class="titulo-seccion">4. Declaración de Vulnerabilidades y Discapacidad</div>
    <table>
        <tr>
            <td class="label">Tipo Discapacidad:</td>
            <td class="valor"><?= htmlspecialchars($emp['tipo_discapacidad'] ?? 'Ninguna / No Aplica') ?></td>
            <td class="label">Porcentaje / Carnet:</td>
            <td class="valor">
                <?= !empty($emp['porcentaje']) ? htmlspecialchars($emp['porcentaje'])."%" : '0%' ?> 
                <?= !empty($emp['numero_carnet']) ? ' - Reg. Nº '.htmlspecialchars($emp['numero_carnet']) : '' ?>
            </td>
        </tr>
    </table>

    <div style="margin-top: 60px; display: flex; justify-content: space-around; text-align: center;">
        <div style="width: 200px; border-top: 1px solid #000; padding-top: 5px;">
            Firma del Servidor / Empleado
        </div>
        <div style="width: 200px; border-top: 1px solid #000; padding-top: 5px;">
            Responsable de Talento Humano
        </div>
    </div>

    <script>
        window.onload = function() {
            // Descomenta la línea de abajo si quieres que se abra el cuadro automáticamente
            // window.print();
        }
    </script>
</body>
</html>