<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../includes/conexion.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $foto = null;

    // Subir fotografía
    if (!empty($_FILES['foto']['name'])) {

        $directorio = "../uploads/directores/";

        if (!file_exists($directorio)) {
            mkdir($directorio, 0777, true);
        }

        $nombreArchivo = time() . "_" . basename($_FILES['foto']['name']);
        $rutaDestino = $directorio . $nombreArchivo;

        if (move_uploaded_file($_FILES['foto']['tmp_name'], $rutaDestino)) {
            $foto = $nombreArchivo;
        }
    }

    try {

        $stmt = $pdo->prepare("
            INSERT INTO directores (
                nombres,
                apellidos,
                cedula,
                cargo,
                telefono,
                correo,
                direccion,
                fecha_nacimiento,
                fecha_ingreso,
                departamento,
                foto,
                estado,
                observaciones
            )
            VALUES (
                :nombres,
                :apellidos,
                :cedula,
                :cargo,
                :telefono,
                :correo,
                :direccion,
                :fecha_nacimiento,
                :fecha_ingreso,
                :departamento,
                :foto,
                :estado,
                :observaciones
            )
        ");

        $stmt->execute([
            ':nombres' => $_POST['nombres'],
            ':apellidos' => $_POST['apellidos'],
            ':cedula' => $_POST['cedula'],
            ':cargo' => $_POST['cargo'],
            ':telefono' => $_POST['telefono'],
            ':correo' => $_POST['correo'],
            ':direccion' => $_POST['direccion'],
            ':fecha_nacimiento' => !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : null,
            ':fecha_ingreso' => !empty($_POST['fecha_ingreso']) ? $_POST['fecha_ingreso'] : null,
            ':departamento' => $_POST['departamento'],
            ':foto' => $foto,
            ':estado' => $_POST['estado'],
            ':observaciones' => $_POST['observaciones']
        ]);

        header("Location: index.php?ok=1");
        exit;

    } catch(PDOException $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Nuevo Director</title>
<link rel="stylesheet" href="../assets/estilos.css">
</head>
<body>

<h2>👨‍💼 Registrar Director</h2>

<?php if($error): ?>
<p style="color:red"><?= $error ?></p>
<?php endif; ?>

<form class="form-dos-columnas" method="POST" enctype="multipart/form-data">

<div class="form-group">
    <label>Nombres</label>
    <input type="text" name="nombres" required>
</div>

<div class="form-group">
    <label>Apellidos</label>
    <input type="text" name="apellidos" required>
</div>

<div class="form-group">
    <label>Cédula</label>
    <input type="text" name="cedula" maxlength="10" required>
</div>

<div class="form-group">
    <label>Cargo</label>
    <input type="text" name="cargo" required>
</div>

<div class="form-group">
    <label>Teléfono</label>
    <input type="text" name="telefono">
</div>

<div class="form-group">
    <label>Correo</label>
    <input type="email" name="correo">
</div>

<div class="form-group">
    <label>Fecha Nacimiento</label>
    <input type="date" name="fecha_nacimiento">
</div>

<div class="form-group">
    <label>Fecha Ingreso</label>
    <input type="date" name="fecha_ingreso">
</div>

<div class="form-group">
    <label>Departamento</label>
    <input type="text" name="departamento">
</div>

<div class="form-group">
    <label>Estado</label>
    <select name="estado">
        <option value="ACTIVO">ACTIVO</option>
        <option value="INACTIVO">INACTIVO</option>
    </select>
</div>

<div class="form-group ancho-completo">
    <label>Dirección</label>
    <textarea name="direccion" rows="2"></textarea>
</div>

<div class="form-group ancho-completo">
    <label>Observaciones</label>
    <textarea name="observaciones" rows="3"></textarea>
</div>

<div class="form-group ancho-completo">
    <label>Fotografía</label>
    <input type="file" name="foto" accept="image/*">
</div>

<div class="form-buttons ancho-completo">
    <button type="submit">💾 Guardar Director</button>
    <a href="index.php">❌ Cancelar</a>
</div>

</form>

</body>
</html>