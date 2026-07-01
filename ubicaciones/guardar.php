<?php
require_once '../includes/conexion.php';
try {
    $pdo->beginTransaction();

    // PROVINCIA
    if ($_POST['id_provincia'] === 'nuevo') {
        $nombre = trim($_POST['nueva_provincia']);
        $stmt = $pdo->prepare("SELECT id_provincia FROM provincias WHERE nombre = :nombre");
        $stmt->execute(['nombre'=>$nombre]);
        $prov = $stmt->fetch(PDO::FETCH_ASSOC);
        $id_provincia = $prov ? $prov['id_provincia'] : null;
        if (!$id_provincia) {
            $stmt = $pdo->prepare("INSERT INTO provincias (nombre) VALUES (:nombre)");
            $stmt->execute(['nombre'=>$nombre]);
            $id_provincia = $pdo->lastInsertId();
        }
    } else {
        $id_provincia = $_POST['id_provincia'];
    }

    // CIUDAD
    if ($_POST['id_ciudad'] === 'nuevo') {
        $nombre = trim($_POST['nueva_ciudad']);
        $stmt = $pdo->prepare("SELECT id_ciudad FROM ciudades WHERE nombre=:nombre AND id_provincia=:id_provincia");
        $stmt->execute(['nombre'=>$nombre,'id_provincia'=>$id_provincia]);
        $ciudad = $stmt->fetch(PDO::FETCH_ASSOC);
        $id_ciudad = $ciudad ? $ciudad['id_ciudad'] : null;
        if (!$id_ciudad) {
            $stmt = $pdo->prepare("INSERT INTO ciudades (nombre, id_provincia) VALUES (:nombre, :id_provincia)");
            $stmt->execute(['nombre'=>$nombre,'id_provincia'=>$id_provincia]);
            $id_ciudad = $pdo->lastInsertId();
        }
    } else {
        $id_ciudad = $_POST['id_ciudad'];
    }

    // PARROQUIA
    if ($_POST['id_parroquia'] === 'nuevo') {
        $nombre = trim($_POST['nueva_parroquia']);
        $stmt = $pdo->prepare("SELECT id_parroquia FROM parroquias WHERE nombre=:nombre AND id_ciudad=:id_ciudad");
        $stmt->execute(['nombre'=>$nombre,'id_ciudad'=>$id_ciudad]);
        $parroquia = $stmt->fetch(PDO::FETCH_ASSOC);
        $id_parroquia = $parroquia ? $parroquia['id_parroquia'] : null;
        if (!$id_parroquia) {
            // Nota: Si tu tabla 'parroquias' requiere la columna 'tipo', añádela aquí.
            $stmt = $pdo->prepare("INSERT INTO parroquias (nombre, id_ciudad) VALUES (:nombre, :id_ciudad)");
            $stmt->execute(['nombre'=>$nombre,'id_ciudad'=>$id_ciudad]);
            $id_parroquia = $pdo->lastInsertId();
        }
    } else {
        $id_parroquia = $_POST['id_parroquia'];
    }

    // RECINTO
    if ($_POST['id_recinto'] === 'nuevo') {
        $nombre = trim($_POST['nuevo_recinto']);
        $stmt = $pdo->prepare("SELECT id_recinto FROM recintos WHERE nombre=:nombre AND id_parroquia=:id_parroquia");
        $stmt->execute(['nombre'=>$nombre,'id_parroquia'=>$id_parroquia]);
        $recinto = $stmt->fetch(PDO::FETCH_ASSOC);
        $id_recinto = $recinto ? $recinto['id_recinto'] : null;
        if (!$id_recinto) {
            $stmt = $pdo->prepare("INSERT INTO recintos (nombre, id_parroquia) VALUES (:nombre, :id_parroquia)");
            $stmt->execute(['nombre'=>$nombre,'id_parroquia'=>$id_parroquia]);
            $id_recinto = $pdo->lastInsertId();
        }
    } else {
        $id_recinto = $_POST['id_recinto'];
    }

    // CAMPOS DE LA UBICACIÓN EXTRACCIÓN (CORREGIDOS)
    $barrio_comunidad = trim($_POST['barrio_comunidad'] ?? '');
    $punto_referencia = trim($_POST['punto_referencia'] ?? '');

    // Guardar en tabla ubicaciones incluyendo barrio_comunidad y punto_referencia
    $stmt = $pdo->prepare("INSERT INTO ubicaciones (id_recinto, barrio_comunidad, punto_referencia) VALUES (:id_recinto, :barrio_comunidad, :punto_referencia)");
    $stmt->execute([
        'id_recinto' => $id_recinto,
        'barrio_comunidad' => $barrio_comunidad,
        'punto_referencia' => $punto_referencia
    ]);

    $pdo->commit();
    header("Location: index.php?guardado=1");
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("❌ Error al guardar: " . $e->getMessage());
}
?>