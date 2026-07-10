<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}
require_once __DIR__ . '/../includes/conexion.php';

if (!isset($pdo)) {
    die("ERROR: La variable \$pdo no está definida");
}

$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID inválido.");
}

try {
    // SOLUCIÓN: Eliminamos la columna problemática del cargo y su JOIN para que la consulta no falle
    $query = $pdo->prepare("
        SELECT o.*, 
               c.nombres AS chofer_nombres, c.apellidos AS chofer_apellidos,
               v.placa, v.modelo, v.marca,
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
        die("Orden no encontrada.");
    }

    // Definimos el cargo como "Director" por defecto de manera estática en PHP
    $orden['director_cargo'] = "Director Responsable";

} catch (PDOException $e) {
    die("Error en la base de datos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de Orden de Movilización</title>
    <link rel="stylesheet" href="../assets/estilos.css">
</head>
<body>
<div class="container">
    <h2>Orden de Movilización #<?= htmlspecialchars($orden['numero_orden']) ?></h2>
    
    <ul class="detalle-lista">
        <li><strong>Fecha de emisión:</strong> <?= date('d/m/Y', strtotime($orden['fecha_emision'])) ?></li>
        <li><strong>Chofer:</strong> <?= htmlspecialchars($orden['chofer_nombres'] . " " . $orden['chofer_apellidos']) ?></li>
        <li><strong>Vehículo:</strong> <?= htmlspecialchars($orden['placa'] . " - " . ($orden['marca'] ?? '') . " " . $orden['modelo']) ?></li>
        <li><strong>Ubicación:</strong> <?= htmlspecialchars($orden['recinto'] . ", " . $orden['parroquia'] . ", " . $orden['ciudad'] . ", " . $orden['provincia']) ?></li>
        <li><strong>Objeto de movilización:</strong> <?= htmlspecialchars($orden['objeto_movilizacion']) ?></li>
        <li><strong>Cantidad de días:</strong> <?= htmlspecialchars($orden['dias_movilizacion']) ?> día<?= ($orden['dias_movilizacion'] > 1 ? 's' : '') ?></li>
        <li><strong>Detalle de días:</strong> <?= htmlspecialchars($orden['detalle_dias']) ?></li>
        <li><strong>Director Responsable:</strong> <?= htmlspecialchars($orden['director_nombre_completo']) ?> (CI: <?= htmlspecialchars($orden['director_cedula']) ?>) - <em><?= htmlspecialchars($orden['director_cargo']) ?></em></li>
    </ul>

    <div class="form-buttons" style="margin-top: 20px;">
        <a href="index.php" class="btn btn-secondary">← Volver al Listado</a>
        <a href="../reportes/imprimir_orden.php?id=<?= $orden['id_orden'] ?>" target="_blank" class="btn btn-primary">🖨 Imprimir Orden</a>
    </div>
</div>
</body>
</html>