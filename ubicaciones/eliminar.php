<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../includes/conexion.php';

if (isset($_GET['id'])) {

    $id_ubicacion = $_GET['id'];

    try {

        $stmt = $pdo->prepare("
            DELETE FROM ubicaciones
            WHERE id_ubicacion = ?
        ");

        $stmt->execute([$id_ubicacion]);

        header("Location: index.php?mensaje=eliminado");
        exit();

    } catch (PDOException $e) {

        if ($e->getCode() == '23503') {

            header("Location: index.php?mensaje=referenciada");
            exit();

        } else {

            die("Error al eliminar ubicación: " . $e->getMessage());

        }
    }

} else {

    die("ID de ubicación no proporcionado.");

}
?>