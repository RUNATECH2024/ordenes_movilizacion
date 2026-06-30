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

try {
    $stmt = $pdo->prepare("SELECT * FROM directores WHERE id_director = ?");
    $stmt->execute([$id]);
    $director = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$director) {
        die("Director no encontrado");
    }

    $stmt_direcciones = $pdo->query("SELECT id_direccion, nombre FROM direcciones WHERE estado = 'ACTIVO' ORDER BY nombre ASC");
    $direcciones = $stmt_direcciones->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error en la base de datos: " . $e->getMessage());
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $foto = $director['foto'] ?? null;

        if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] == 0) {
            $directorio = "../uploads/directores/";

            if (!file_exists($directorio)) {
                mkdir($directorio, 0777, true);
            }

            if (!empty($foto) && file_exists($directorio . $foto)) {
                unlink($directorio . $foto);
            }

            $extension = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            $nombreArchivo = time() . "_" . uniqid("DIRECTOR_") . "." . $extension;
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
                foto = :foto,
                estado = :estado,
                observaciones = :observaciones,
                id_direccion = :id_direccion
            WHERE id_director = :id
        ");

        $update->execute([
            ':nombres'          => !empty($_POST['nombres']) ? trim($_POST['nombres']) : null,
            ':apellidos'        => !empty($_POST['apellidos']) ? trim($_POST['apellidos']) : null,
            ':cedula'           => !empty($_POST['cedula']) ? trim($_POST['cedula']) : null,
            ':cargo'            => !empty($_POST['cargo']) ? trim($_POST['cargo']) : null,
            ':telefono'         => !empty($_POST['telefono']) ? trim($_POST['telefono']) : null,
            ':correo'           => !empty($_POST['correo']) ? trim($_POST['correo']) : null,
            ':direccion'        => !empty($_POST['direccion']) ? trim($_POST['direccion']) : null,
            ':fecha_nacimiento' => !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : null,
            ':fecha_ingreso'    => !empty($_POST['fecha_ingreso']) ? $_POST['fecha_ingreso'] : null,
            ':foto'             => $foto,
            ':estado'           => $_POST['estado'] ?? 'ACTIVO',
            ':observaciones'    => !empty($_POST['observaciones']) ? trim($_POST['observaciones']) : null,
            ':id_direccion'     => !empty($_POST['id_direccion']) ? (int)$_POST['id_direccion'] : null,
            ':id'               => $id
        ]);

        header("Location: index.php?update=1");
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

<div class="container">
    <header>
        <h2>✏️ Editar Director</h2>
        <nav>
            <a href="index.php" class="btn btn-secondary">← Regresar</a>
        </nav>
    </header>

    <?php if (!empty($error)): ?>
        <p style="color:red; background-color: #ffeaea; padding: 10px; border-radius: 5px; border: 1px solid red;">
            <?= htmlspecialchars($error) ?>
        </p>
    <?php endif; ?>

    <form class="form-dos-columnas" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= htmlspecialchars($director['id_director']) ?>">

        <div class="form-group">
            <label>Nombres</label>
            <input type="text" name="nombres" value="<?= htmlspecialchars($director['nombres'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label>Apellidos</label>
            <input type="text" name="apellidos" value="<?= htmlspecialchars($director['apellidos'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label>Cédula</label>
            <input type="text" name="cedula" maxlength="10" value="<?= htmlspecialchars($director['cedula'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label>Cargo</label>
            <input type="text" name="cargo" value="<?= htmlspecialchars($director['cargo'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label>Teléfono</label>
            <input type="text" name="telefono" value="<?= htmlspecialchars($director['telefono'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Correo</label>
            <input type="email" name="correo" value="<?= htmlspecialchars($director['correo'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Fecha Nacimiento</label>
            <input type="date" name="fecha_nacimiento" value="<?= $director['fecha_nacimiento'] ?? '' ?>">
        </div>

        <div class="form-group">
            <label>Fecha Ingreso</label>
            <input type="date" name="fecha_ingreso" value="<?= $director['fecha_ingreso'] ?? '' ?>">
        </div>

        <div class="form-group">
            <label>Dirección Institucional</label>
            <select name="id_direccion" required>
                <option value="">Seleccione una Dirección</option>
                <?php foreach ($direcciones as $dir): ?>
                    <?php $selectedDir = (($director['id_direccion'] ?? null) == $dir['id_direccion']) ? 'selected' : ''; ?>
                    <option value="<?= $dir['id_direccion'] ?>" <?= $selectedDir ?>>
                        <?= htmlspecialchars($dir['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Estado</label>
            <select name="estado">
                <option value="ACTIVO" <?= (($director['estado'] ?? '') == 'ACTIVO') ? 'selected' : '' ?>>ACTIVO</option>
                <option value="INACTIVO" <?= (($director['estado'] ?? '') == 'INACTIVO') ? 'selected' : '' ?>>INACTIVO</option>
            </select>
        </div>

        <div class="form-group ancho-completo">
            <label>Dirección de Domicilio</label>
            <textarea name="direccion" rows="3"><?= htmlspecialchars($director['direccion'] ?? '') ?></textarea>
        </div>

        <div class="form-group ancho-completo">
            <label>Observaciones</label>
            <textarea name="observaciones" rows="4"><?= htmlspecialchars($director['observaciones'] ?? '') ?></textarea>
        </div>

        <div class="form-group ancho-completo">
            <label>Fotografía</label>
            <?php if (!empty($director['foto']) && file_exists("../uploads/directores/" . $director['foto'])): ?>
                <div style="margin: 10px 0;">
                    <img src="../uploads/directores/<?= htmlspecialchars($director['foto']) ?>" width="150" style="border:1px solid #ccc; border-radius:10px; padding:5px; object-fit: cover;">
                </div>
            <?php endif; ?>
            <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp">
        </div>

        <div class="form-buttons ancho-completo">
            <button type="submit">💾 Guardar Cambios</button>
            <a href="index.php" class="btn btn-secondary">❌ Cancelar</a>
        </div>
    </form>
</div>

</body>
</html>