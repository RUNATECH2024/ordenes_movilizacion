<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . '/../includes/conexion.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($pdo)) {
    die("ERROR: La variable \$pdo no está definida");
}

$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID no válido");
}

try {
    // Obtener datos completos de la orden y sus relaciones usando la tabla empleados
    $query = $pdo->prepare("
        SELECT o.*,
               c.nombres AS chofer_nombres, c.apellidos AS chofer_apellidos, c.cedula AS chofer_cedula,
               v.placa, v.modelo, v.color, v.matricula,
               r.nombre AS recinto, p.nombre AS parroquia, ci.nombre AS ciudad, pr.nombre AS provincia,
               e.primer_nombre || ' ' || COALESCE(e.segundo_nombre, '') || ' ' || e.primer_apellido || ' ' || COALESCE(e.segundo_apellido, '') AS director_nombre_completo,
               e.cedula AS director_cedula
        FROM ordenes_movilizacion o
        JOIN choferes c ON o.id_chofer = c.id_chofer
        JOIN vehiculos v ON o.id_vehiculo = v.id_vehiculo
        JOIN ubicaciones u ON o.id_ubicacion = u.id_ubicacion
        JOIN recintos r ON u.id_recinto = r.id_recinto
        JOIN parroquias p ON r.id_parroquia = p.id_parroquia
        JOIN ciudades ci ON p.id_ciudad = ci.id_ciudad
        JOIN provincias pr ON ci.id_provincia = pr.id_provincia
        JOIN directores d ON o.id_director = d.id_director
        JOIN empleados e ON d.id_empleado = e.id_empleado
        WHERE o.id_orden = :id
    ");
    $query->execute([':id' => $id]);
    $orden = $query->fetch(PDO::FETCH_ASSOC);

    if (!$orden) {
        die("Orden no encontrada");
    }
    
    // Definimos el cargo de manera estática para evitar el error de columna inexistente
    $orden['director_cargo'] = "Director Responsable";

} catch (PDOException $e) {
    die("Error en la base de datos: " . $e->getMessage());
}

// Ruta del logotipo institucional
$logoPath = realpath(__DIR__ . "/../assets/img/logo.png");

// Formatear fechas de manera limpia
$fechaFormateada = date("d/m/Y", strtotime($orden['fecha_emision']));

// Construcción de la plantilla HTML/CSS para Dompdf
$html = "
<style>
    body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; line-height: 1.5; color: #333; margin: 20px; }
    .encabezado { width: 100%; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
    .encabezado td { vertical-align: middle; }
    .logo { width: 75px; height: auto; }
    .institucion { text-align: center; font-size: 13px; font-weight: bold; line-height: 1.3; }
    .subtitulo { font-size: 11px; font-weight: normal; letter-spacing: 1px; color: #555; }
    .datos-orden { text-align: right; font-size: 11px; line-height: 1.4; }
    .titulo-orden { text-align: center; font-size: 16px; font-weight: bold; margin: 25px 0; letter-spacing: 0.5px; text-decoration: underline; }
    .fecha-bloque { margin-bottom: 15px; font-size: 12px; }
    .bloque-texto { text-align: justify; border: 1px solid #000; padding: 15px; background-color: #fff; margin-bottom: 15px; }
    .bloque-detalle { border: 1px solid #000; padding: 12px; background-color: #f9f9f9; margin-bottom: 20px; }
    .firma-contenedor { margin-top: 70px; text-align: center; width: 100%; }
    .linea-firma { width: 250px; border-bottom: 1px solid #000; margin: 0 auto 10px auto; }
    .cargo-director { font-size: 10px; color: #666; text-transform: uppercase; }
</style>

<table class='encabezado'>
    <tr>
        <td width='15%'>
            " . ($logoPath ? "<img src='file://{$logoPath}' class='logo'>" : "") . "
        </td>
        <td width='55%' class='institucion'>
            GOBIERNO AUTÓNOMO DESCENTRALIZADO<br>DE LA PROVINCIA BOLÍVAR<br>
            <span class='subtitulo'>SECRETARÍA DE VIALIDAD</span>
        </td>
        <td width='30%' class='datos-orden'>
            <strong>N° Orden:</strong> " . htmlspecialchars($orden['numero_orden']) . "<br>
            <strong>Fecha Emisión:</strong> " . $fechaFormateada . "
        </td>
    </tr>
</table>

<div class='titulo-orden'>ORDEN DE MOVILIZACIÓN Y/O TRABAJO</div>

<div class='fecha-bloque'>
    <strong>Guaranda, " . $fechaFormateada . "</strong>
</div>

<div class='bloque-texto'>
    Señor: <strong>" . htmlspecialchars($orden['chofer_nombres'] . " " . $orden['chofer_apellidos']) . "</strong>, 
    con cédula de ciudadanía N° <strong>" . htmlspecialchars($orden['chofer_cedula']) . "</strong>, en calidad de conductor, 
    se autoriza la movilización utilizando el vehículo tipo/modelo <strong>" . htmlspecialchars($orden['modelo']) . "</strong>, 
    color <strong>" . htmlspecialchars($orden['color']) . "</strong>, con placas de identificación <strong>" . htmlspecialchars($orden['placa']) . "</strong> 
    y matrícula <strong>" . htmlspecialchars($orden['matricula']) . "</strong>; con destino hacia: 
    <strong>" . htmlspecialchars($orden['recinto'] . ", " . $orden['parroquia'] . ", " . $orden['ciudad'] . ", " . $orden['provincia']) . "</strong>, 
    con el objeto de cumplir la siguiente comisión/actividad: <strong>" . htmlspecialchars($orden['objeto_movilizacion']) . "</strong>.
</div>

<div class='bloque-detalle'>
    <strong>Días autorizados de movilización:</strong> " . htmlspecialchars($orden['dias_movilizacion']) . " día(s).<br>
    <strong>Detalle cronológico de días:</strong> " . htmlspecialchars($orden['detalle_dias']) . ".
</div>

<table class='firma-contenedor'>
    <tr>
        <td>
            <div class='linea-firma'></div>
            <strong>" . htmlspecialchars($orden['director_nombre_completo']) . "</strong><br>
            C.I. " . htmlspecialchars($orden['director_cedula']) . "<br>
            <span class='cargo-director'>" . htmlspecialchars($orden['director_cargo']) . "</span><br>
            <strong>Firma y Sello Autorizado</strong>
        </td>
    </tr>
</table>
";

// Inicializar y configurar las opciones de Dompdf
$options = new Options();
$options->set('defaultFont', 'Helvetica');
$options->set('isRemoteEnabled', true); // Permite cargar recursos locales mediante rutas completas

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Transmitir el archivo directamente al navegador sin forzar descarga automática (inline)
$dompdf->stream("orden_" . $orden['numero_orden'] . ".pdf", ["Attachment" => false]);
exit;
?>