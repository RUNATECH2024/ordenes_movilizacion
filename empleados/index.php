<?php
// empleados/index.php
session_start();

// Control de acceso: Si no hay sesión iniciada, redirige al login
if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Aseguramos la ruta absoluta al archivo de conexión usando __DIR__
require_once __DIR__ . '/../includes/conexion.php'; 

$mensaje = "";

// LÓGICA PARA CAMBIAR ESTADO A INACTIVO
if (isset($_GET['action']) && $_GET['action'] == 'desactivar' && isset($_GET['id'])) {
    try {
        $id_emp = $_GET['id'];
        $sql_delete = "UPDATE empleados SET estado = 'INACTIVO' WHERE id_empleado = ?";
        $pdo->prepare($sql_delete)->execute([$id_emp]);
        $mensaje = "<div class='alert success'>Empleado desactivado correctamente.</div>";
    } catch (Exception $e) {
        $mensaje = "<div class='alert error'>Error al desactivar: " . $e->getMessage() . "</div>";
    }
}

// CONSULTA PRINCIPAL: Incluye los datos clave, la foto y la dirección (departamento) laboral
try {
    $sql = "SELECT 
                e.id_empleado,
                e.cedula,
                e.foto,
                CONCAT(e.primer_apellido, ' ', e.segundo_apellido, ' ', e.primer_nombre, ' ', e.segundo_nombre) AS nombre_completo,
                c.nombre AS cargo_actual,
                d.nombre AS direccion_laboral,
                ci.correo AS correo_institucional,
                e.estado
            FROM empleados e
            LEFT JOIN historial_laboral hl ON e.id_empleado = hl.id_empleado AND hl.activo = TRUE
            LEFT JOIN cargos c ON hl.id_cargo = c.id_cargo
            -- Conectamos con jefaturas y luego direcciones para obtener la locación exacta de trabajo
            LEFT JOIN jefaturas j ON c.id_jefatura = j.id_jefatura
            LEFT JOIN direcciones d ON j.id_direccion = d.id_direccion
            LEFT JOIN correo_institucional ci ON e.id_empleado = ci.id_empleado
            ORDER BY e.primer_apellido ASC, e.primer_nombre ASC";

    $stmt = $pdo->query($sql);
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $mensaje = "<div class='alert error'>Error al consultar los empleados: " . $e->getMessage() . "</div>";
    $empleados = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGA - Listado de Empleados</title>
    <link rel="stylesheet" href="../assets/estilos.css?v=<?= time(); ?>">
    <style>
        .avatar-mini {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ddd;
            display: block;
            margin: 0 auto;
        }
        .text-center { text-align: center; }
        
        /* Controles de la cabecera y botones */
        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn-panel {
            background-color: #4A5568;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            transition: background 0.2s;
        }
        .btn-panel:hover {
            background-color: #2D3748;
        }

        /* Contenedor del buscador */
        .search-container {
            margin: 20px 0;
            display: flex;
            gap: 10px;
            max-width: 600px;
        }
        .search-input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="main-header">
        <h2>Listado de Personal Registrado</h2>
        <div class="header-actions">
            <a href="dashboard.php" class="btn btn-primary">Dashboard</a>
            <a href="../panel_administracion.php" class="btn-panel">← Panel Administrativo</a>
            <a href="crear.php" class="btn btn-primary">Registrar Nuevo Empleado</a>
        </div>
    </div>
    
    <?php echo $mensaje; ?>

    <div class="search-container">
        <input type="text" id="inputBuscar" class="search-input" placeholder="Buscar por Cédula, Nombre o Dirección de trabajo...">
        <button type="button" id="btnBuscar" class="btn btn-primary" style="margin:0;">Buscar</button>
    </div>

    <div class="table-responsive">
        <table class="table" id="tablaEmpleados">
            <thead>
                <tr>
                    <th>ID</th>
                    <th class="text-center">Foto</th>
                    <th>Cédula</th>
                    <th>Apellidos y Nombres</th>
                    <th>Cargo Actual</th>
                    <th>Dirección (Área)</th>
                    <th>Correo Inst.</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($empleados) > 0): ?>
                    <?php foreach ($empleados as $emp): ?>
                        <tr>
                            <td><?= $emp['id_empleado'] ?></td>
                            <td class="text-center">
                                <?php if (!empty($emp['foto']) && file_exists('../uploads/' . $emp['foto'])): ?>
                                    <img src="../uploads/<?= htmlspecialchars($emp['foto']) ?>" class="avatar-mini" alt="Foto">
                                <?php else: ?>
                                    <img src="../assets/img/default-avatar.png" class="avatar-mini" alt="Sin foto">
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($emp['cedula'] ?? '') ?></td>
                            <td><strong><?= htmlspecialchars($emp['nombre_completo'] ?? 'Sin Nombre') ?></strong></td>
                            <td><?= htmlspecialchars($emp['cargo_actual'] ?? 'Sin Asignar') ?></td>
                            <td><span style="color: #555;"><?= htmlspecialchars($emp['direccion_laboral'] ?? 'Sin Asignar') ?></span></td>
                            <td><?= htmlspecialchars($emp['correo_institucional'] ?? 'N/A') ?></td>
                            <td>
                                <?php 
                                    $estado = $emp['estado'] ?? 'INACTIVO';
                                    $clase_badge = (strtoupper($estado) == 'ACTIVO') ? 'badge-success' : 'badge-danger';
                                ?>
                                <span class="badge <?= $clase_badge ?>">
                                    <?= htmlspecialchars($estado) ?>
                                </span>
                            </td>
                            <td data-label="Acciones">
                                <div class="acciones">
                                    <a class="btn btn-info" href="ver.php?id=<?= $emp['id_empleado'] ?>" title="Ver Detalles">👁</a>
                                    <a class="btn btn-warning" href="editar.php?id=<?= $emp['id_empleado'] ?>" title="Editar">✏️</a>
                                        <?php if (strtoupper($emp['estado'] ?? '') == 'ACTIVO'): ?>
                                            <a class="btn btn-danger" href="index.php?action=desactivar&id=<?= $emp['id_empleado'] ?>" onclick="return confirm('¿Desea cambiar el estado a INACTIVO para este empleado?')" title="Desactivar">❌</a>
                                        <?php else: ?>
                                            <a class="btn btn-danger" style="opacity: 0.4; cursor: not-allowed;" href="#" onclick="return false;" title="Ya está inactivo">❌</a>
                                        <?php endif; ?>
                                        <a class="btn btn-primary" href="../reportes/imprimir_empleado.php?id=<?= $emp['id_empleado'] ?>" target="_blank" title="Imprimir Ficha">🖨</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align: center;">No se encontraron empleados registrados en el sistema.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function ejecutarBusqueda() {
    var input = document.getElementById("inputBuscar");
    var filter = input.value.toUpperCase();
    var table = document.getElementById("tablaEmpleados");
    var tr = table.getElementsByTagName("tr");

    for (var i = 1; i < tr.length; i++) {
        var tdCedula    = tr[i].getElementsByTagName("td")[2]; // Columna Cédula
        var tdNombre    = tr[i].getElementsByTagName("td")[3]; // Columna Nombre completo
        var tdDireccion = tr[i].getElementsByTagName("td")[5]; // Columna Dirección laboral
        
        if (tdCedula || tdNombre || tdDireccion) {
            var txtCedula    = tdCedula.textContent || tdCedula.innerText;
            var txtNombre    = tdNombre.textContent || tdNombre.innerText;
            var txtDireccion = tdDireccion.textContent || tdDireccion.innerText;
            
            if (
                txtCedula.toUpperCase().indexOf(filter) > -1 || 
                txtNombre.toUpperCase().indexOf(filter) > -1 || 
                txtDireccion.toUpperCase().indexOf(filter) > -1
            ) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }       
    }
}

document.getElementById("btnBuscar").addEventListener("click", ejecutarBusqueda);
document.getElementById("inputBuscar").addEventListener("keyup", ejecutarBusqueda);
</script>

</body>
</html>