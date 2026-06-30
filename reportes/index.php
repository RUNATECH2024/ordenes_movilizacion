<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Módulo de Reportes</title>
    <link rel="stylesheet" href="../assets/estilos.css">
</head>

<body>

<header>
    <h2>📊 Módulo de Reportes</h2>

    <nav>
        <a href="../panel_administracion.php">🏠 Panel</a>
        <a href="../auth/logout.php" class="logout">🚪 Cerrar sesión</a>
    </nav>
</header>

<main>

    <div class="container">

        <h3>Seleccione el reporte que desea generar</h3>

        <table>

            <thead>
                <tr>
                    <th>Reporte</th>
                    <th>Descripción</th>
                    <th>Acción</th>
                </tr>
            </thead>

            <tbody>

                <tr>
                    <td>👨‍✈️ Choferes</td>
                    <td>Listado completo de choferes registrados.</td>
                    <td>
                        <a href="reporte_choferes.php" target="_blank">
                            📄 Generar PDF
                        </a>
                    </td>
                </tr>

                <tr>
                    <td>🚗 Vehículos</td>
                    <td>Listado completo de vehículos.</td>
                    <td>
                        <a href="reporte_vehiculos.php" target="_blank">
                            📄 Generar PDF
                        </a>
                    </td>
                </tr>

                <tr>
                    <td>👨‍💼 Directores</td>
                    <td>Listado de directores.</td>
                    <td>
                        <a href="reporte_directores.php" target="_blank">
                            📄 Generar PDF
                        </a>
                    </td>
                </tr>

                <tr>
                    <td>📍 Ubicaciones</td>
                    <td>Listado de ubicaciones.</td>
                    <td>
                        <a href="reporte_ubicaciones.php" target="_blank">
                            📄 Generar PDF
                        </a>
                    </td>
                </tr>

                <tr>
                    <td>📝 Órdenes de Movilización</td>
                    <td>Listado general de órdenes.</td>
                    <td>
                        <a href="reporte_ordenes.php" target="_blank">
                            📄 Generar PDF
                        </a>
                    </td>
                </tr>

                <tr>
                    <td>📅 Reporte por Fechas</td>
                    <td>Consultar órdenes por rango de fechas.</td>
                    <td>
                        <a href="reporte_fechas.php">
                            🔍 Consultar
                        </a>
                    </td>
                </tr>

            </tbody>

        </table>

    </div>

</main>

</body>
</html>