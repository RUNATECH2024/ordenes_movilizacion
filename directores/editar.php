<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../includes/conexion.php';

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    die("ID de director no válido");
}

$stmt = $pdo->prepare("SELECT * FROM directores WHERE id_director = ?");
$stmt->execute([$id]);
$director = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$director) {
    die("Director no encontrado");
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $foto = $director['foto'] ?? null;

        // Subir nueva foto
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

        $update = $pdo->prepare("
            UPDATE directores SET
                nombres = :nombres,
                apellidos = :apellidos,
                cedula = :cedula,
                cargo = :cargo,
                telefono = :telefono,
                correo = :correo,
                direccion = :direccion,
                fecha_nacimiento = :fecha_nacimiento,
                fecha_ingreso = :fecha_ingreso,
                departamento = :departamento,
                foto = :foto,
                estado = :estado,
                observaciones = :observaciones
            WHERE id_director = :id
        ");

        $update->execute([
            ':nombres' => $_POST['nombres'],
            ':apellidos' => $_POST['apellidos'],
            ':cedula' => $_POST['cedula'],
            ':cargo' => $_POST['cargo'],
            ':telefono' => $_POST['telefono'] ?: null,
            ':correo' => $_POST['correo'] ?: null,
            ':direccion' => $_POST['direccion'] ?: null,
            ':fecha_nacimiento' => !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : null,
            ':fecha_ingreso' => !empty($_POST['fecha_ingreso']) ? $_POST['fecha_ingreso'] : null,
            ':departamento' => $_POST['departamento'] ?: null,
            ':foto' => $foto,
            ':estado' => $_POST['estado'],
            ':observaciones' => $_POST['observaciones'] ?: null,
            ':id' => $id
        ]);

        header("Location: index.php?actualizado=1");
        exit;

    } catch (PDOException $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar Director</title>
<link rel="stylesheet" href="../assets/estilos.css">
</head>
<body>

<header>
    <h2>✏️ Editar Director</h2>
    <nav>
        <a href="index.php">← Regresar</a>
    </nav>
</header>

<?php if (!empty($error)): ?>
<p style="color:red;">
    <?= htmlspecialchars($error) ?>
</p>
<?php endif; ?>

<form class="form-dos-columnas" method="POST" enctype="multipart/form-data">

    <input type="hidden" name="id"
           value="<?= htmlspecialchars($director['id_director']) ?>">

    <div class="form-group">
        <label>Nombres</label>
        <input type="text"
               name="nombres"
               value="<?= htmlspecialchars($director['nombres']) ?>"
               required>
    </div>

    <div class="form-group">
        <label>Apellidos</label>
        <input type="text"
               name="apellidos"
               value="<?= htmlspecialchars($director['apellidos']) ?>"
               required>
    </div>

    <div class="form-group">
        <label>Cédula</label>
        <input type="text"
               name="cedula"
               maxlength="10"
               value="<?= htmlspecialchars($director['cedula']) ?>"
               required>
    </div>

    <div class="form-group">
        <label>Cargo</label>
        <input type="text"
               name="cargo"
               value="<?= htmlspecialchars($director['cargo']) ?>">
    </div>

    <div class="form-group">
        <label>Teléfono</label>
        <input type="text"
               name="telefono"
               value="<?= htmlspecialchars($director['telefono'] ?? '') ?>">
    </div>

    <div class="form-group">
        <label>Correo</label>
        <input type="email"
               name="correo"
               value="<?= htmlspecialchars($director['correo'] ?? '') ?>">
    </div>

    <div class="form-group">
        <label>Fecha Nacimiento</label>
        <input type="date"
               name="fecha_nacimiento"
               value="<?= $director['fecha_nacimiento'] ?? '' ?>">
    </div>

    <div class="form-group">
        <label>Fecha Ingreso</label>
        <input type="date"
               name="fecha_ingreso"
               value="<?= $director['fecha_ingreso'] ?? '' ?>">
    </div>

    <div class="form-group">
        <label>Departamento</label>
        <input type="text"
               name="departamento"
               value="<?= htmlspecialchars($director['departamento'] ?? '') ?>">
    </div>

    <div class="form-group">
        <label>Estado</label>
        <select name="estado">
            <option value="ACTIVO"
                <?= ($director['estado'] ?? '') == 'ACTIVO' ? 'selected' : '' ?>>
                ACTIVO
            </option>

            <option value="INACTIVO"
                <?= ($director['estado'] ?? '') == 'INACTIVO' ? 'selected' : '' ?>>
                INACTIVO
            </option>
        </select>
    </div>

    <div class="form-group ancho-completo">
        <label>Dirección</label>
        <textarea name="direccion" rows="3"><?= htmlspecialchars($director['direccion'] ?? '') ?></textarea>
    </div>

    <div class="form-group ancho-completo">
        <label>Observaciones</label>
        <textarea name="observaciones" rows="4"><?= htmlspecialchars($director['observaciones'] ?? '') ?></textarea>
    </div>

    <div class="form-group ancho-completo">

        <label>Fotografía</label>

        <?php if (!empty($director['foto'])): ?>
            <br><br>
            <img src="../uploads/directores/<?= htmlspecialchars($director['foto']) ?>"
                 width="150"
                 style="border:1px solid #ccc;border-radius:10px;padding:5px;">
            <br><br>
        <?php endif; ?>

        <input type="file" name="foto" accept="image/*">

    </div>

    <div class="form-buttons ancho-completo">
        <button type="submit">💾 Guardar Cambios</button>
        <a href="index.php">❌ Cancelar</a>
    </div>

</form>

</body>
</html>