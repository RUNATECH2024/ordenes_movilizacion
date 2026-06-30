<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../includes/conexion.php';

$error = '';

try {
    // Obtener lista de direcciones institucionales activas
    $stmt_direcciones = $pdo->query("SELECT id_direccion, nombre FROM direcciones WHERE estado = 'ACTIVO' ORDER BY nombre ASC");
    $direcciones = $stmt_direcciones->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al cargar las direcciones institucionales: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $foto = null;

    // Subir fotografía
    if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] == 0) {
        $directorio = "../uploads/directores/";

        if (!file_exists($directorio)) {
            mkdir($directorio, 0777, true);
        }

        $extension = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $nombreArchivo = time() . "_" . uniqid("DIRECTOR_") . "." . $extension;
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
                foto,
                estado,
                observaciones,
                id_direccion
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
                :foto,
                :estado,
                :observaciones,
                :id_direccion
            )
        ");

        $stmt->execute([
            ':nombres'            => !empty($_POST['nombres']) ? trim($_POST['nombres']) : null,
            ':apellidos'          => !empty($_POST['apellidos']) ? trim($_POST['apellidos']) : null,
            ':cedula'             => !empty($_POST['cedula']) ? trim($_POST['cedula']) : null,
            ':cargo'              => !empty($_POST['cargo']) ? trim($_POST['cargo']) : null,
            ':telefono'           => !empty($_POST['telefono']) ? trim($_POST['telefono']) : null,
            ':correo'             => !empty($_POST['correo']) ? trim($_POST['correo']) : null,
            ':direccion'          => !empty($_POST['direccion']) ? trim($_POST['direccion']) : null,
            ':fecha_nacimiento'   => !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : null,
            ':fecha_ingreso'      => !empty($_POST['fecha_ingreso']) ? $_POST['fecha_ingreso'] : null,
            ':foto'               => $foto,
            ':estado'             => $_POST['estado'] ?? 'ACTIVO',
            ':observaciones'      => !empty($_POST['observaciones']) ? trim($_POST['observaciones']) : null,
            ':id_direccion'       => !empty($_POST['id_direccion']) ? (int)$_POST['id_direccion'] : null
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

<div class="container">
    <h2>👨‍💼 Registrar Director</h2>

    <?php if($error): ?>
        <p style="color:red; background-color: #ffeaea; padding: 10px; border-radius: 5px; border: 1px solid red;"><?= htmlspecialchars($error) ?></p>
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
            <label>Dirección Institucional</label>
            <select name="id_direccion" required>
                <option value="">Seleccione una Dirección</option>
                <?php foreach ($direcciones as $dir): ?>
                    <option value="<?= $dir['id_direccion'] ?>">
                        <?= htmlspecialchars($dir['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Estado</label>
            <select name="estado">
                <option value="ACTIVO">ACTIVO</option>
                <option value="INACTIVO">INACTIVO</option>
            </select>
        </div>

        <div class="form-group ancho-completo">
            <label>Dirección de Domicilio</label>
            <textarea name="direccion" rows="2"></textarea>
        </div>

        <div class="form-group ancho-completo">
            <label>Observaciones</label>
            <textarea name="observaciones" rows="3"></textarea>
        </div>

        <div class="form-group ancho-completo">
            <label>Fotografía</label>
            <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp">
        </div>

        <div class="form-buttons ancho-completo">
            <button type="submit">💾 Guardar Director</button>
            <a href="index.php" class="btn btn-secondary">❌ Cancelar</a>
        </div>

    </form>
</div>

</body>
</html>