<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . '/../includes/conexion.php';

/* =========================
   FILTROS
========================= */

$busqueda = $_GET['busqueda'] ?? '';
$estado = $_GET['estado'] ?? '';

$pagina = isset($_GET['pagina'])
? (int)$_GET['pagina']
: 1;

$porPagina = 10;

$offset = ($pagina - 1) * $porPagina;


/* =========================
   WHERE
========================= */

$where = "WHERE 1=1";

$params=[];

if($busqueda!=''){

$where.=" AND (
nombres ILIKE :busqueda
OR apellidos ILIKE :busqueda
OR cedula ILIKE :busqueda
)";

$params[':busqueda']="%$busqueda%";

}

if($estado!=''){

$where.=" AND estado=:estado";

$params[':estado']=$estado;

}


/* =========================
TOTAL REGISTROS
========================= */

$totalStmt=$pdo->prepare("
SELECT COUNT(*)
FROM directores
$where
");

$totalStmt->execute($params);

$totalRegistros=
$totalStmt->fetchColumn();

$totalPaginas=
ceil(
$totalRegistros/$porPagina
);


/* =========================
DATOS
========================= */

$sql="

SELECT *
FROM directores

$where

ORDER BY
apellidos,
nombres

LIMIT :limit
OFFSET :offset

";

$stmt=$pdo->prepare($sql);

foreach($params as $k=>$v){

$stmt->bindValue(
$k,
$v
);

}

$stmt->bindValue(
':limit',
$porPagina,
PDO::PARAM_INT
);

$stmt->bindValue(
':offset',
$offset,
PDO::PARAM_INT
);

$stmt->execute();

$directores=
$stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<title>
Directores
</title>

<link rel="stylesheet"
href="../assets/estilos.css">

</head>

<body>

<div class="container">

<h2>
👨‍💼 Directores
</h2>


<div class="menu">

<div>

<a href="nuevo.php"
class="btn btn-success">

➕ Nuevo

</a>

<a href="../panel_administracion.php"
class="btn btn-primary">

← Panel

</a>

</div>

</div>

<hr>


<!-- FILTROS -->

<form method="GET"
class="form-dos-columnas">

<div class="form-group">

<label>
Buscar
</label>

<input
type="text"
name="busqueda"
placeholder="Nombre o cédula"
value="<?= htmlspecialchars($busqueda) ?>"
>

</div>


<div class="form-group">

<label>
Estado
</label>

<select name="estado">

<option value="">
Todos
</option>

<option
value="ACTIVO"
<?= $estado=="ACTIVO" ? "selected" : "" ?>
>

ACTIVO

</option>

<option
value="INACTIVO"
<?= $estado=="INACTIVO" ? "selected" : "" ?>
>

INACTIVO

</option>

</select>

</div>


<div class="form-buttons">

<button
type="submit"
class="btn btn-info"
>

🔎 Buscar

</button>

<a
href="index.php"
class="btn btn-danger"
>

Limpiar

</a>

</div>

</form>


<div class="table-container">

<table>

<thead>

<tr>

<th>#</th>
<th>Nombre</th>
<th>Cédula</th>
<th>Cargo</th>
<th>Departamento</th>
<th>Teléfono</th>
<th>Correo</th>
<th>Estado</th>
<th>Acciones</th>

</tr>

</thead>


<tbody>

<?php foreach($directores as $d): ?>

<tr>

<td data-label="#">

<?= $d['id_director'] ?>

</td>


<td data-label="Nombre">

<?= htmlspecialchars(
$d['apellidos']
.' '.
$d['nombres']
) ?>

</td>


<td data-label="Cédula">

<?= htmlspecialchars(
$d['cedula']
) ?>

</td>


<td data-label="Cargo">

<?= htmlspecialchars(
$d['cargo']
) ?>

</td>


<td data-label="Departamento">

<?= htmlspecialchars(
$d['departamento']
?? '-'
) ?>

</td>


<td data-label="Teléfono">

<?= htmlspecialchars(
$d['telefono']
?? '-'
) ?>

</td>


<td data-label="Correo">

<?= htmlspecialchars(
$d['correo']
?? '-'
) ?>

</td>


<td data-label="Estado">

<?php if(
$d['estado']=="ACTIVO"
): ?>

<span class="estado-activo">

ACTIVO

</span>

<?php else: ?>

<span class="estado-inactivo">

<?= htmlspecialchars(
$d['estado']
?? 'INACTIVO'
) ?>

</span>

<?php endif; ?>

</td>


<td data-label="Acciones">

<div class="acciones">

<a
href="editar.php?id=<?= $d['id_director'] ?>"
class="btn btn-warning"
>

✏️

</a>

<a
href="eliminar.php?id=<?= $d['id_director'] ?>"
class="btn btn-danger btn-delete"
>

❌

</a>

</div>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>


<!-- PAGINACIÓN -->

<div class="paginacion">

<?php for($i=1;$i<=$totalPaginas;$i++): ?>

<a href="?pagina=<?= $i ?>&busqueda=<?= $busqueda ?>&estado=<?= $estado ?>">

<?= $i ?>

</a>

<?php endfor; ?>

</div>


<!-- MODAL -->

<div id="confirmModal"
class="modal"
style="display:none;">

<div class="modal-content">

<h3>
Confirmar eliminación
</h3>

<p>
¿Eliminar este director?
</p>

<br>

<button
id="btnConfirm"
class="btn btn-danger"
>

Sí

</button>

<button
id="btnCancel"
class="btn btn-primary"
>

No

</button>

</div>

</div>

</div>


<script>

document.querySelectorAll(
'.btn-delete'
).forEach(link=>{

link.addEventListener(
'click',
function(e){

e.preventDefault();

let url=
this.getAttribute(
'href'
);

let modal=
document.getElementById(
'confirmModal'
);

modal.style.display='flex';

document.getElementById(
'btnConfirm'
).onclick=function(){

window.location.href=url;

};

document.getElementById(
'btnCancel'
).onclick=function(){

modal.style.display='none';

};

});

});

</script>

</body>
</html>