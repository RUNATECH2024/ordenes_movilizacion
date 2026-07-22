<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Alta, edición y eliminación de personas se realizan desde Empleados / Personal.
header('Location: index.php?mensaje=gestion_empleados');
exit;
