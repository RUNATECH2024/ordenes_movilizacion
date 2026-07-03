<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}
require_once '../includes/conexion.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID de orden inválido.");
}

try {
    // 1. Obtener los datos actuales de la orden de movilización
    $stmt = $pdo->prepare("SELECT * FROM ordenes_movilizacion WHERE id_orden = :id");
    $stmt->execute([':id' => $id]);
    $orden = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$orden) {
        die("La orden de movilización no existe.");
    }

    // 2. Cargar los catálogos con la misma estructura exacta de nueva_orden.php
    $choferes = $pdo->query("SELECT id_chofer, nombres || ' ' || apellidos AS nombre FROM choferes")->fetchAll(PDO::FETCH_ASSOC);
    $vehiculos = $pdo->query("SELECT id_vehiculo, placa || ' - ' || marca || ' ' || modelo AS descripcion FROM vehiculos")->fetchAll(PDO::FETCH_ASSOC);
    $directores = $pdo->query("SELECT id_director, nombres || ' ' || apellidos AS nombre FROM directores")->fetchAll(PDO::FETCH_ASSOC);
    $ubicaciones = $pdo->query("SELECT u.id_ubicacion, r.nombre AS recinto, p.nombre AS parroquia, c.nombre AS ciudad, pr.nombre AS provincia FROM ubicaciones u JOIN recintos r ON u.id_recinto = r.id_recinto JOIN parroquias p ON r.id_parroquia = p.id_parroquia JOIN ciudades c ON p.id_ciudad = c.id_ciudad JOIN provincias pr ON c.id_provincia = pr.id_provincia ORDER BY pr.nombre, c.nombre, p.nombre, r.nombre")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error al cargar los datos de la orden: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Orden de Movilización</title>
    <link rel="stylesheet" href="../assets/estilos.css">
</head>
<body>
<div class="container">
    <h1>Editar Orden de Movilización</h1>
    <form action="actualizar.php" method="POST" class="form-dos-columnas">
        <!-- ID Oculto para procesar la actualización -->
        <input type="hidden" name="id_orden" value="<?= $orden['id_orden'] ?>">

        <div class="form-group">
            <label>Número de Orden</label>
            <!-- CORRECCIÓN: Se añade readonly y estilo estático para evitar la alteración accidental del código de la orden -->
            <input type="text" name="numero_orden" value="<?= htmlspecialchars($orden['numero_orden']) ?>" readonly style="background:#f5f5f5; font-weight:bold;" required>
        </div>
        <div class="form-group">
            <label>Fecha de Emisión</label>
            <input type="date" id="fecha_emision" name="fecha_emision" value="<?= $orden['fecha_emision'] ?>" required onchange="generarDias()">
        </div>
        <div class="form-group">
            <label>Chofer</label>
            <select name="id_chofer" id="id_chofer" required onchange="checkNuevo(this,'../choferes/nuevo.php')">
                <option value="">Seleccione</option>
                <?php foreach($choferes as $c): ?>
                    <option value="<?= $c['id_chofer'] ?>" <?= $c['id_chofer'] == $orden['id_chofer'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['nombre']) ?>
                    </option>
                <?php endforeach; ?>
                <option value="nuevo">➕ Crear nuevo chofer</option>
            </select>
        </div>
        <div class="form-group">
            <label>Vehículo</label>
            <select name="id_vehiculo" id="id_vehiculo" required onchange="checkNuevo(this,'../vehiculos/nuevo.php')">
                <option value="">Seleccione</option>
                <?php foreach($vehiculos as $v): ?>
                    <option value="<?= $v['id_vehiculo'] ?>" <?= $v['id_vehiculo'] == $orden['id_vehiculo'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($v['descripcion']) ?>
                    </option>
                <?php endforeach; ?>
                <option value="nuevo">➕ Crear nuevo vehículo</option>
            </select>
        </div>
        <div class="form-group">
            <label>Ubicación</label>
            <select name="id_ubicacion" id="id_ubicacion" required onchange="checkNuevo(this,'../ubicaciones/nuevo.php')">
                <option value="">Seleccione</option>
                <?php foreach($ubicaciones as $u): ?>
                    <option value="<?= $u['id_ubicacion'] ?>" <?= $u['id_ubicacion'] == $orden['id_ubicacion'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['recinto']." / ".$u['parroquia']." / ".$u['ciudad']." / ".$u['provincia']) ?>
                    </option>
                <?php endforeach; ?>
                <option value="nuevo">➕ Crear nueva ubicación</option>
            </select>
        </div>
        <div class="form-group">
            <label>Objeto de movilización</label>
            <textarea name="objeto_movilizacion" rows="3" required><?= htmlspecialchars($orden['objeto_movilizacion']) ?></textarea>
        </div>
        <div class="form-group">
            <label>Días de Movilización</label>
            <select name="dias_movilizacion" id="dias_movilizacion" required onchange="generarDias()">
                <option value="">Seleccione</option>
                <?php for($i=1;$i<=10;$i++): ?>
                    <option value="<?= $i ?>" <?= $i == $orden['dias_movilizacion'] ? 'selected' : '' ?>>
                        <?= $i ?> día<?=($i>1?'s':'')?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Detalle de días</label>
            <textarea id="detalle_dias" name="detalle_dias" readonly style="background:#f5f5f5" rows="4"><?= htmlspecialchars($orden['detalle_dias']) ?></textarea>
        </div>
        <div class="form-group">
            <label>Director Responsable</label>
            <select name="id_director" id="id_director" required onchange="checkNuevo(this,'../directores/nuevo.php')">
                <option value="">Seleccione</option>
                <?php foreach($directores as $d): ?>
                    <option value="<?= $d['id_director'] ?>" <?= $d['id_director'] == $orden['id_director'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($d['nombre']) ?>
                    </option>
                <?php endforeach; ?>
                <option value="nuevo">➕ Crear nuevo director</option>
            </select>
        </div>
        <div class="form-buttons">
            <button type="submit">Actualizar Orden</button>
            <a href="index.php">Cancelar</a>
        </div>
    </form>
</div>
<script>
function checkNuevo(selectElem,urlNuevo){
    if(selectElem.value==="nuevo"){ window.location.href=urlNuevo; }
}
function generarDias(){
    const fecha = document.getElementById("fecha_emision").value;
    const dias = document.getElementById("dias_movilizacion").value;
    if(!fecha || !dias) return;
    const nombresDias=['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
    let resultado=[];
    let parts = fecha.split('-');
    let fechaBase = new Date(parts[0], parts[1] - 1, parts[2]);
    for(let i=0; i<dias; i++){
        let nuevaFecha = new Date(fechaBase);
        nuevaFecha.setDate(fechaBase.getDate() + i);
        let dia = nombresDias[nuevaFecha.getDay()];
        let numero = nuevaFecha.getDate();
        resultado.push(dia+" "+numero);
    }
    document.getElementById("detalle_dias").value = resultado.join(", ");
}
window.onload = generarDias;
</script>
</body>
</html>