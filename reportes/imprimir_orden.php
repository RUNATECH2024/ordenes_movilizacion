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

// Verificar conexión
if (!isset($pdo)) {
    die("ERROR: La variable \$pdo no está definida");
}

// Obtener ID
$id = $_GET['id'] ?? null;

if (!$id) {
    die("ID no válido");
}

// Obtener datos
$query = $pdo->prepare("
    SELECT 
        o.*,

        c.nombres AS chofer_nombres,
        c.apellidos AS chofer_apellidos,
        c.cedula AS chofer_cedula,

        v.placa,
        v.modelo,
        v.color,
        v.matricula,

        r.nombre AS recinto,
        p.nombre AS parroquia,
        ci.nombre AS ciudad,
        pr.nombre AS provincia,

        d.nombres AS director_nombres,
        d.apellidos AS director_apellidos,
        d.cedula AS director_cedula,
        d.cargo

    FROM ordenes_movilizacion o

    JOIN choferes c
    ON o.id_chofer=c.id_chofer

    JOIN vehiculos v
    ON o.id_vehiculo=v.id_vehiculo

    JOIN ubicaciones u
    ON o.id_ubicacion=u.id_ubicacion

    JOIN recintos r
    ON u.id_recinto=r.id_recinto

    JOIN parroquias p
    ON r.id_parroquia=p.id_parroquia

    JOIN ciudades ci
    ON p.id_ciudad=ci.id_ciudad

    JOIN provincias pr
    ON ci.id_provincia=pr.id_provincia

    JOIN directores d
    ON o.id_director=d.id_director

    WHERE o.id_orden=:id
");

$query->execute([
    ':id'=>$id
]);

$orden = $query->fetch(PDO::FETCH_ASSOC);

if (!$orden) {
    die("Orden no encontrada");
}

// Logo
$logoPath = realpath(
    __DIR__ .
    "/../assets/img/logo.png"
);

// Formato fecha
$fechaFormateada = date(
    "d/m/Y",
    strtotime(
        $orden['fecha_emision']
    )
);

// HTML PDF
$html = "

<style>

body{
font-family:Arial,sans-serif;
font-size:12px;
line-height:1.6;
margin:30px;
}

.encabezado{
width:100%;
border-bottom:1px solid black;
padding-bottom:10px;
margin-bottom:20px;
}

.encabezado td{
vertical-align:top;
}

.logo{
width:80px;
}

.institucion{
text-align:center;
font-size:14px;
font-weight:bold;
}

.subtitulo{
font-size:12px;
}

.datos{
text-align:right;
font-size:12px;
}

.titulo{

text-align:center;
font-size:15px;
font-weight:bold;

margin-top:15px;
margin-bottom:20px;

}

.justificado{

text-align:justify;

border:1px solid #000;

padding:10px;

}

.detalle{

margin-top:15px;

border:1px solid #000;

padding:10px;

background:#f5f5f5;

}

.firma{

margin-top:80px;

text-align:center;

}

</style>


<table class='encabezado'>

<tr>

<td width='90'>

<img
src='file://{$logoPath}'
class='logo'>

</td>

<td class='institucion'>

GOBIERNO AUTÓNOMO DESCENTRALIZADO
<br>

DE LA PROVINCIA BOLÍVAR

<br>

<span class='subtitulo'>

SECRETARÍA DE VIALIDAD

</span>

</td>

<td class='datos'>

<strong>N° Orden:</strong>

".htmlspecialchars(
$orden['numero_orden']
)."

<br>

<strong>Fecha:</strong>

".$fechaFormateada."

</td>

</tr>

</table>


<div class='titulo'>

ORDEN DE MOVILIZACIÓN Y/O TRABAJO

</div>


<p>

<strong>

Guaranda,
".$fechaFormateada."

</strong>

</p>


<div class='justificado'>

Señor:

<strong>

".htmlspecialchars(
$orden['chofer_nombres']
." ".
$orden['chofer_apellidos']
)."

</strong>

con cédula:

<strong>

".htmlspecialchars(
$orden['chofer_cedula']
)."

</strong>

en calidad de conductor, se autoriza la movilización utilizando el vehículo:

<strong>

".htmlspecialchars(
$orden['modelo']
)."

</strong>

color:

<strong>

".htmlspecialchars(
$orden['color']
)."

</strong>

placa:

<strong>

".htmlspecialchars(
$orden['placa']
)."

</strong>

matrícula:

<strong>

".htmlspecialchars(
$orden['matricula']
)."

</strong>

con destino a:

<strong>

".htmlspecialchars(

$orden['recinto'].
", ".
$orden['parroquia'].
", ".
$orden['ciudad'].
", ".
$orden['provincia']

)."

</strong>

para cumplir con el siguiente objeto:

<strong>

".htmlspecialchars(
$orden['objeto_movilizacion']
)."

</strong>

</div>


<div class='detalle'>

<strong>
Días autorizados:
</strong>

".htmlspecialchars(
$orden['dias_movilizacion']
)." día(s)

<br><br>

<strong>
Detalle de días:
</strong>

".htmlspecialchars(
$orden['detalle_dias']
)."

</div>


<div class='firma'>

_________________________________

<br><br>

<strong>

".htmlspecialchars(
$orden['director_nombres']
." ".
$orden['director_apellidos']
)."

</strong>

<br>

C.I.:

".htmlspecialchars(
$orden['director_cedula']
)."

<br>

".htmlspecialchars(
$orden['cargo']
)."

<br>

Firma y sello

</div>

";

// Configuración PDF

$options = new Options();

$options->set(
'defaultFont',
'Arial'
);

$options->set(
'isRemoteEnabled',
true
);

$dompdf = new Dompdf(
$options
);

$dompdf->loadHtml(
$html
);

$dompdf->setPaper(
'A4',
'portrait'
);

$dompdf->render();

$dompdf->stream(
"orden_".$orden['numero_orden'].".pdf",
[
'Attachment'=>false
]
);

exit;

?>