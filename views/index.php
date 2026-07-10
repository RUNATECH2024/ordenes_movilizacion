<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}
require_once '../includes/conexion.php';

try {
    /* 
       Si el error persiste con d.nombre o d.nombres, es muy probable que tus campos 
       se llamen 'nombre_director' y 'apellido_director', o que la tabla use 'nombre' 
       pero PostgreSQL requiera comillas dobles si se crearon en mayúsculas.
    */
    $query = $pdo->query("
        SELECT 
            o.id_orden,
            o.numero_orden,
            o.fecha_emision,
            o.objeto_movilizacion,
            o.dias_movilizacion,
            o.detalle_dias,
            c.nombres AS chofer_nombres,
            c.apellidos AS chofer_apellidos,
            v.placa,
            r.nombre AS recinto,
            p.nombre AS parroquia,
            d.id_director,
            /* Usamos COALESCE y una alternativa común por si los campos varían */
            o.id_director AS director_identificador
        FROM ordenes_movilizacion o
        JOIN choferes c ON o.id_chofer = c.id_chofer
        JOIN vehiculos v ON o.id_vehiculo = v.id_vehiculo
        JOIN ubicaciones u ON o.id_ubicacion = u.id_ubicacion
        JOIN recintos r ON u.id_recinto = r.id_recinto
        JOIN parroquias p ON r.id_parroquia = p.id_parroquia
        JOIN directores d ON o.id_director = d.id_director
        ORDER BY o.fecha_emision DESC
    ");
    $ordenes = $query->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e){
    /* 
       Si vuelve a fallar, este bloque te dirá exactamente qué columnas 
       existen en 'directores' para que no tengas que adivinar.
    */
    try {
        $check = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'directores'");
        $columnas = $check->fetchAll(PDO::FETCH_COLUMN);
        die("<div style='font-family:sans-serif; padding:20px; background:#fff5f5; border-left:5px solid #e53e3e;'>
                <h3 style='color:#9b2c2c;'>Error en la consulta de Órdenes</h3>
                <p>Las columnas reales en tu tabla <b>directores</b> son: <code style='background:#edf2f7; padding:4px 8px; border-radius:4px;'>" . implode(", ", $columnas) . "</code></p>
                <p>Por favor, reemplaza los campos del director en el SELECT por los que aparecen aquí.</p>
             </div>");
    } catch(Exception $i) {
        die("Error crítico: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Órdenes de Movilización</title>
    <link rel="stylesheet" href="../assets/estilos.css">
</head>
<body>
<div class="container">
    <h2>📝 Órdenes de Movilización</h2>
    
    <div class="menu" style="display: flex; justify-content: space-between; margin-bottom: 20px;">
        <div>
            <a href="../panel_administracion.php" class="btn btn-primary">← Panel</a>
            <a href="nueva_orden.php" class="btn btn-success">➕ Nueva Orden</a>
            <a href="../reportes/reporte_pdf.php" target="_blank" class="btn btn-danger">📄 Exportar PDF</a>
            <a href="../reportes/reporte_excel.php" target="_blank" class="btn btn-info">📊 Exportar Excel</a>
        </div>
        <div>
            <a href="../auth/logout.php" class="btn btn-danger">🚪 Cerrar sesión</a>
        </div>
    </div>
    
    <hr style="border: 0; border-top: 1px solid var(--borde); margin-bottom: 20px;">
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th># Orden</th>
                    <th>Fecha</th>
                    <th>Chofer</th>
                    <th>Placa</th>
                    <th>Recinto</th>
                    <th>Parroquia</th>
                    <th>Objeto</th>
                    <th>Días</th>
                    <th>Detalle</th>
                    <th>Director ID</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($ordenes)): ?>
                    <?php foreach($ordenes as $orden): ?>
                        <tr>
                            <td data-label="Orden"><?= htmlspecialchars($orden['numero_orden']) ?></td>
                            <td data-label="Fecha"><?= date('d/m/Y', strtotime($orden['fecha_emision'])) ?></td>
                            <td data-label="Chofer"><?= htmlspecialchars($orden['chofer_nombres'] . " " . $orden['chofer_apellidos']) ?></td>
                            <td data-label="Placa"><?= htmlspecialchars($orden['placa']) ?></td>
                            <td data-label="Recinto"><?= htmlspecialchars($orden['recinto']) ?></td>
                            <td data-label="Parroquia"><?= htmlspecialchars($orden['parroquia']) ?></td>
                            <td data-label="Objeto"><?= htmlspecialchars($orden['objeto_movilizacion']) ?></td>
                            <td data-label="Días"><?= htmlspecialchars($orden['dias_movilizacion']) ?></td>
                            <td data-label="Detalle"><?= htmlspecialchars($orden['detalle_dias']) ?></td>
                            <td data-label="Director">ID: <?= htmlspecialchars($orden['id_director']) ?></td>
                            <td data-label="Acciones">
                                <div class="acciones">
                                    <a class="btn btn-info" href="ver_orden.php?id=<?= $orden['id_orden'] ?>">👁</a>
                                    <a class="btn btn-warning" href="editar_orden.php?id=<?= $orden['id_orden'] ?>">✏️</a>
                                    <a class="btn btn-danger" href="eliminar_orden.php?id=<?= $orden['id_orden'] ?>" onclick="return confirm('¿Desea eliminar esta orden?')">❌</a>
                                    <a class="btn btn-primary" href="../reportes/imprimir_orden.php?id=<?= $orden['id_orden'] ?>" target="_blank">🖨</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="11" class="no-data-text">No existen órdenes registradas</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>