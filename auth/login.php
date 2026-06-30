<?php
session_start();

// Si ya está logueado
if (isset($_SESSION['usuario'])) {
    header("Location: ../panel_administracion.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Iniciar Sesión</title>

<link rel="stylesheet" href="../assets/css/login.css">

</head>

<body>

<div class="login-container">

<div class="login-card">

<div class="logo">

<img src="../assets/img/logo.png"
alt="Logo">

</div>

<h2>🔐 Iniciar Sesión</h2>

<p class="subtitulo">
Sistema de Órdenes de Movilización
</p>

<?php if(isset($_GET['error'])): ?>

<div class="mensaje-error">

⚠️ <?= htmlspecialchars($_GET['error']) ?>

</div>

<?php endif; ?>

<form action="validar_login.php"
method="POST">

<div class="input-group">

<label>
👤 Usuario
</label>

<input
type="text"
name="usuario"
placeholder="Ingrese usuario"
required>

</div>


<div class="input-group">

<label>
🔑 Contraseña
</label>

<input
type="password"
name="clave"
placeholder="Ingrese contraseña"
required>

</div>

<button
type="submit"
class="btn-login">

Ingresar

</button>

</form>

<div class="footer">

Gobierno Autónomo Descentralizado Provincial Bolívar

</div>

</div>

</div>

</body>
</html>