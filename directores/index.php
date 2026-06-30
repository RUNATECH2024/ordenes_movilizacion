<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . '/../includes/conexion.php';

$busqueda = $_GET['busqueda'] ?? '';
$estado = $_GET['estado'] ?? '';
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$porPagina = 10;
$offset = ($pagina - 1) * $porPagina;

$where = "WHERE 1=1";
$params = [];

if ($busqueda != '') {
    $where .= " AND (d.nombres ILIKE :busqueda OR d.apellidos ILIKE :busqueda OR d.cedula ILIKE :busqueda)";
    $params[':busqueda'] = "%$busqueda%";
}

if ($estado != '') {
    $where .= " AND d.estado = :estado";
    $params[':estado'] = $estado;
}

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM directores d $where");
$totalStmt->execute($params);
$totalRegistros = $totalStmt->fetchColumn();
$totalPaginas = ceil($totalRegistros / $porPagina);

$sql = "
    SELECT 
        d.*,
        dir.nombre AS direccion_institucional,
        dir.codigo AS direccion_codigo
    FROM directores d
    LEFT JOIN direcciones dir ON d.id_direccion = dir.id_direccion
    $where
    ORDER BY d.apellidos, d.nombres
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);

foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$directores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Directores</title>
    <link rel="stylesheet" href="../assets/estilos.css">
</head>
<body>

<div class="container">
    <h2>👨‍💼 Directores</h2>

    <div class="menu">
        <div>
            <a href="nuevo.php" class="btn btn-success">➕ Nuevo Director</a>
            <a href="../panel_administracion.php" class="btn btn-primary">← Panel</a>
        </div>
    </div>

    <hr>

    <form method="GET" class="form-dos-columnas">
        <div class="form-group">
            <label>Buscar</label>
            <input type="text" name="busqueda" placeholder="Nombre o cédula" value="<?= htmlspecialchars($busqueda) ?>">
        </div>

        <div class="form-group">
            <label>Estado</label>
            <select name="estado">
                <option value="">Todos</option>
                <option value="ACTIVO" <?= $estado == "ACTIVO" ? "selected" : "" ?>>ACTIVO</option>
                <option value="INACTIVO" <?= $estado == "INACTIVO" ? "selected" : "" ?>>INACTIVO</option>
            </select>
        </div>

        <div class="form-buttons">
            <button type="submit" class="btn btn-info">🔎 Buscar</button>
            <a href="index.php" class="btn btn-danger">Limpiar</a>
        </div>
    </form>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Nombre Completo</th>
                    <th>Cédula</th>
                    <th>Cargo</th>
                    <th>Dirección Institucional</th>
                    <th>Contacto</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($directores)): ?>
                    <?php foreach($directores as $d): ?>
                        <tr>
                            <td data-label="Foto">
                                <?php if(!empty($d['foto']) && file_exists("../uploads/directores/" . $d['foto'])): ?>
                                    <img src="../uploads/directores/<?= htmlspecialchars($d['foto']) ?>" width="50" height="50" style="border-radius:50%; object-fit:cover; border:1px solid #ccc;">
                                <?php else: ?>
                                    <span style="color: #aaa; font-style: italic; font-size: 11px;">Sin foto</span>
                                <?php endif; ?>
                            </td>

                            <td data-label="Nombre">
                                <strong><?= htmlspecialchars($d['apellidos'] . ' ' . $d['nombres']) ?></strong>
                            </td>

                            <td data-label="Cédula">
                                <?= htmlspecialchars($d['cedula']) ?>
                            </td>

                            <td data-label="Cargo">
                                <?= htmlspecialchars($d['cargo'] ?? '-') ?>
                            </td>

                            <td data-label="Dirección Institucional">
                                <?php if(!empty($d['direccion_institucional'])): ?>
                                    <span><?= htmlspecialchars($d['direccion_institucional']) ?></span>
                                    <small style="display:block; color:#718096;">(<?= htmlspecialchars($d['direccion_codigo']) ?>)</small>
                                <?php else: ?>
                                    <span style="color: #a0aec0; font-style: italic;">Sin asignar</span>
                                <?php endif; ?>
                            </td>

                            <td data-label="Contacto">
                                <span style="display:block; font-size:12px;">📞 <?= htmlspecialchars($d['telefono'] ?? '-') ?></span>
                                <span style="display:block; font-size:11px; color:#4a5568;">✉️ <?= htmlspecialchars($d['correo'] ?? '-') ?></span>
                            </td>

                            <td data-label="Estado">
                                <?php if($d['estado'] == "ACTIVO"): ?>
                                    <span class="estado-activo">ACTIVO</span>
                                <?php else: ?>
                                    <span class="estado-inactivo"><?= htmlspecialchars($d['estado'] ?? 'INACTIVO') ?></span>
                                <?php endif; ?>
                            </td>

                            <td data-label="Acciones">
                                <div class="acciones">
                                    <a href="editar.php?id=<?= $d['id_director'] ?>" class="btn btn-warning">✏️</a>
                                    <a href="eliminar.php?id=<?= $d['id_director'] ?>" class="btn btn-danger btn-delete">❌</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: #718096; padding: 20px;">No se encontraron directores registrados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPaginas > 1): ?>
        <div class="paginacion">
            <?php for($i = 1; $i <= $totalPaginas; $i++): ?>
                <a href="?pagina=<?= $i ?>&busqueda=<?= urlencode($busqueda) ?>&estado=<?= urlencode($estado) ?>" class="<?= $pagina == $i ? 'activa' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

    <div id="confirmModal" class="modal" style="display:none;">
        <div class="modal-content">
            <h3>Confirmar eliminación</h3>
            <p>¿Está seguro de que desea eliminar este director?</p>
            <br>
            <button id="btnConfirm" class="btn btn-danger">Sí, eliminar</button>
            <button id="btnCancel" class="btn btn-primary">Cancelar</button>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.btn-delete').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        let url = this.getAttribute('href');
        let modal = document.getElementById('confirmModal');
        modal.style.display = 'flex';

        document.getElementById('btnConfirm').onclick = function() {
            window.location.href = url;
        };
        document.getElementById('btnCancel').onclick = function() {
            modal.style.display = 'none';
        };
    });
});
</script>
</body>
</html>