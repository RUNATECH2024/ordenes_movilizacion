<?php
session_start();
require_once __DIR__ . '/../includes/conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $usuario = $_POST['usuario'];
    $clave   = $_POST['clave'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = :usuario AND estado = TRUE");
        $stmt->execute(['usuario' => $usuario]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // ⚠️ IMPORTANTE: si usas password_hash()
        if ($user && password_verify($clave, $user['password'])) {

            $_SESSION['usuario'] = $user['usuario'];
            $_SESSION['rol']     = $user['rol'];

            header("Location: ../panel_administracion.php");
            exit;

        } else {
            header("Location: login.php?error=Usuario o contraseña incorrectos");
            exit;
        }

    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
?>