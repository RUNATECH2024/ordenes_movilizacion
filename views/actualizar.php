<?php
require_once '../includes/conexion.php';

// Validar acceso
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Acceso no permitido");
}

// Capturar datos
$id = $_POST['id_orden'] ?? null;
$fecha = $_POST['fecha_emision'] ?? null;
$chofer = $_POST['id_chofer'] ?? null;
$vehiculo = $_POST['id_vehiculo'] ?? null;
$ubicacion = $_POST['id_ubicacion'] ?? null;
$objeto = $_POST['objeto_movilizacion'] ?? null;
$dias = $_POST['dias_movilizacion'] ?? null;
$director = $_POST['id_director'] ?? null;

// Validación
if (
    !$id ||
    !$fecha ||
    !$chofer ||
    !$vehiculo ||
    !$ubicacion ||
    !$objeto ||
    !$dias ||
    !$director
) {
    die("Faltan datos obligatorios.");
}

/*
Generar automáticamente detalle_dias
Ejemplo:
Miércoles 24, Jueves 25, Viernes 26
*/

$diasSemana = [
    'Domingo',
    'Lunes',
    'Martes',
    'Miércoles',
    'Jueves',
    'Viernes',
    'Sábado'
];

$detalleDias = [];

$fechaBase = new DateTime($fecha);

for ($i = 0; $i < $dias; $i++) {

    $fechaTemp = clone $fechaBase;

    if ($i > 0) {
        $fechaTemp->modify("+$i day");
    }

    $nombreDia = $diasSemana[
        $fechaTemp->format('w')
    ];

    $detalleDias[] =
        $nombreDia . " " .
        $fechaTemp->format('d');
}

$detalle_dias = implode(", ", $detalleDias);

try {

    $query = $pdo->prepare("
        UPDATE ordenes_movilizacion SET

            fecha_emision = :fecha,
            id_chofer = :chofer,
            id_vehiculo = :vehiculo,
            id_ubicacion = :ubicacion,
            objeto_movilizacion = :objeto,
            dias_movilizacion = :dias,
            detalle_dias = :detalle_dias,
            id_director = :director

        WHERE id_orden = :id
    ");

    $query->execute([

        ':fecha' => $fecha,
        ':chofer' => $chofer,
        ':vehiculo' => $vehiculo,
        ':ubicacion' => $ubicacion,
        ':objeto' => $objeto,
        ':dias' => $dias,
        ':detalle_dias' => $detalle_dias,
        ':director' => $director,
        ':id' => $id

    ]);

    header("Location:index.php?mensaje=actualizado");
    exit;

} catch (PDOException $e) {

    die(
        "Error al actualizar: " .
        $e->getMessage()
    );
}
?>