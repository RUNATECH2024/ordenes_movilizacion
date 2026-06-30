<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: auth/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administración</title>
    <link rel="stylesheet" href="assets/panel_admin.css">
</head>
<body>

    <h1>🔧 Panel de Administración</h1>
    
    <div class="panel">

        <p>👤 Bienvenido: <strong><?= htmlspecialchars($_SESSION['usuario']) ?></strong></p>

        <!-- Sección de Ubicaciones -->
        <div class="card">
            <h2>📍 Ubicaciones</h2>
            <a href="ubicaciones/index.php">🔹🌍 Administrar Ubicaciones</a>
        </div>

        <!-- Sección de Órdenes -->
        <div class="card">
            <h2>📝 Órdenes de Movilización</h2>
            <a href="views/index.php">🔹 Gestionar Órdenes</a>
        </div>

        <!-- Sección de Vehículos -->
        <div class="card">
            <h2>🚚 Vehículos</h2>
            <a href="vehiculos/index.php">🔹 Administrar Vehículos</a>
        </div>

        <!-- Sección de Choferes -->
        <div class="card">
            <h2>👷 Choferes</h2>
            <a href="choferes/index.php">🔹 Administrar Choferes</a>
        </div>

        <!-- Sección de Directores -->
        <div class="card">
            <h2>📊 Directores</h2>
            <a href="directores/index.php">🔹 Administrar Directores</a>
        </div>

        <!-- 🆕 SECCIÓN DE DIRECCIONES -->
        <div class="card">
            <h2>🏢 Direcciones</h2>
            <a href="direcciones/index.php">🔹 Administrar Direcciones</a>
        </div>

        <!-- Sección de Reportes -->
        <div class="card">
            <h2>📊 Reportes</h2>
            <a href="reportes/index.php">🔹 Generar Reportes</a>
        </div>

        <!-- Botón de Cerrar Sesión -->
        <a href="auth/logout.php" class="logout-btn">Cerrar sesión</a>

    </div>

</body>
</html>