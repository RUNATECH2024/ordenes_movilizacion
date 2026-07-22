<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Los choferes ya no se registran de forma independiente.
header('Location: ../empleados/crear.php');
exit;
