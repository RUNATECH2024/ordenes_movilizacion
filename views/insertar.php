<?php
require_once '../includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Capturar datos
    $numero_orden = $_POST['numero_orden'];
    $fecha_emision = $_POST['fecha_emision'];
    $id_chofer = $_POST['id_chofer'];
    $id_vehiculo = $_POST['id_vehiculo'];
    $id_ubicacion = $_POST['id_ubicacion'];
    $objeto_movilizacion = $_POST['objeto_movilizacion'];
    $dias_movilizacion = $_POST['dias_movilizacion'];
    $id_director = $_POST['id_director'];

    // Generar detalle de días automáticamente
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

    $fecha = new DateTime($fecha_emision);

    for ($i = 0; $i < $dias_movilizacion; $i++) {

        $fechaTemp = clone $fecha;

        if ($i > 0) {
            $fechaTemp->modify("+$i day");
        }

        $numeroDia = $fechaTemp->format('w');

        $nombreDia = $diasSemana[$numeroDia];

        $detalleDias[] =
            $nombreDia . " " .
            $fechaTemp->format('d');
    }

    $detalle_dias = implode(", ", $detalleDias);

    try {

        $stmt = $pdo->prepare("
            INSERT INTO ordenes_movilizacion
            (
                numero_orden,
                fecha_emision,
                id_chofer,
                id_vehiculo,
                id_ubicacion,
                objeto_movilizacion,
                dias_movilizacion,
                detalle_dias,
                id_director
            )
            VALUES
            (
                :numero_orden,
                :fecha_emision,
                :id_chofer,
                :id_vehiculo,
                :id_ubicacion,
                :objeto_movilizacion,
                :dias_movilizacion,
                :detalle_dias,
                :id_director
            )
        ");

        $stmt->execute([
            ':numero_orden' => $numero_orden,
            ':fecha_emision' => $fecha_emision,
            ':id_chofer' => $id_chofer,
            ':id_vehiculo' => $id_vehiculo,
            ':id_ubicacion' => $id_ubicacion,
            ':objeto_movilizacion' => $objeto_movilizacion,
            ':dias_movilizacion' => $dias_movilizacion,
            ':detalle_dias' => $detalle_dias,
            ':id_director' => $id_director
        ]);

        header('Location: index.php?exito=1');
        exit();

    } catch(PDOException $e) {

        echo "Error: " . $e->getMessage();

    }

} else {

    header('Location:index.php');
    exit();

}
?>