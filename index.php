<?php

session_start();

require_once "config/database.php";

// =====================================================
// VERIFICAR SESIÓN
// =====================================================

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}


// =====================================================
// ESTADÍSTICAS GENERALES
// =====================================================

// Productos activos

$sqlProductos = "
    SELECT COUNT(*) AS total
    FROM productos
    WHERE estado = 1
";

$resultadoProductos = $conexion->query($sqlProductos);

if (!$resultadoProductos) {
    die("Error al obtener productos: " . $conexion->error);
}

$totalProductos = (int) $resultadoProductos->fetch_assoc()["total"];


// Categorías activas

$sqlCategorias = "
    SELECT COUNT(*) AS total
    FROM categorias
    WHERE estado = 1
";

$resultadoCategorias = $conexion->query($sqlCategorias);

if (!$resultadoCategorias) {
    die("Error al obtener categorías: " . $conexion->error);
}

$totalCategorias = (int) $resultadoCategorias->fetch_assoc()["total"];


// Total de ventas REALIZADAS

$sqlVentas = "
    SELECT COUNT(*) AS total
    FROM ventas
    WHERE estado = 'realizada'
";

$resultadoVentas = $conexion->query($sqlVentas);

if (!$resultadoVentas) {
    die("Error al obtener ventas: " . $conexion->error);
}

$totalVentas = (int) $resultadoVentas->fetch_assoc()["total"];


// Productos con stock bajo

$sqlStockBajo = "
    SELECT COUNT(*) AS total
    FROM productos
    WHERE estado = 1
      AND stock <= stock_minimo
";

$resultadoStockBajo = $conexion->query($sqlStockBajo);

if (!$resultadoStockBajo) {
    die("Error al obtener stock bajo: " . $conexion->error);
}

$totalStockBajo = (int) $resultadoStockBajo->fetch_assoc()["total"];


// =====================================================
// VENTAS DE HOY
// =====================================================

$sqlVentasHoy = "
    SELECT
        COUNT(*) AS cantidad,
        COALESCE(SUM(total), 0) AS monto
    FROM ventas
    WHERE DATE(fecha) = CURDATE()
      AND estado = 'realizada'
";

$resultadoVentasHoy = $conexion->query($sqlVentasHoy);

if (!$resultadoVentasHoy) {
    die("Error al obtener ventas de hoy: " . $conexion->error);
}

$ventasHoy = $resultadoVentasHoy->fetch_assoc();

$cantidadVentasHoy = (int) $ventasHoy["cantidad"];
$montoVentasHoy = (float) $ventasHoy["monto"];


// =====================================================
// ÚLTIMAS 5 VENTAS
// =====================================================

$sqlUltimasVentas = "
    SELECT
        v.id,
        v.fecha,
        v.total,
        v.estado,
        u.nombre AS usuario
    FROM ventas v
    INNER JOIN usuarios u
        ON v.usuario_id = u.id
    ORDER BY v.id DESC
    LIMIT 5
";

$resultadoUltimasVentas = $conexion->query($sqlUltimasVentas);

if (!$resultadoUltimasVentas) {
    die("Error al obtener últimas ventas: " . $conexion->error);
}


// =====================================================
// PRODUCTOS DE LAS ÚLTIMAS VENTAS
// =====================================================

$productosVentas = [];

while ($venta = $resultadoUltimasVentas->fetch_assoc()) {

    $ventaId = (int) $venta["id"];

    $sqlDetalle = "
        SELECT
            p.nombre,
            dv.cantidad
        FROM detalle_ventas dv
        INNER JOIN productos p
            ON dv.producto_id = p.id
        WHERE dv.venta_id = ?
        ORDER BY dv.id ASC
    ";

    $stmtDetalle = $conexion->prepare($sqlDetalle);

    if (!$stmtDetalle) {
        die("Error al preparar detalle de venta: " . $conexion->error);
    }

    $stmtDetalle->bind_param("i", $ventaId);

    if (!$stmtDetalle->execute()) {
        die("Error al obtener detalle de venta: " . $stmtDetalle->error);
    }

    $resultadoDetalle = $stmtDetalle->get_result();

    $detalles = [];

    while ($detalle = $resultadoDetalle->fetch_assoc()) {

        $detalles[] =
            htmlspecialchars($detalle["nombre"])
            . " × "
            . (int) $detalle["cantidad"];
    }

    $stmtDetalle->close();

    $venta["productos"] = $detalles;

    $productosVentas[] = $venta;
}


// =====================================================
// PRODUCTOS CON STOCK BAJO
// =====================================================

$sqlProductosStockBajo = "
    SELECT
        id,
        codigo,
        nombre,
        stock,
        stock_minimo
    FROM productos
    WHERE estado = 1
      AND stock <= stock_minimo
    ORDER BY stock ASC, nombre ASC
    LIMIT 5
";

$resultadoProductosStockBajo = $conexion->query($sqlProductosStockBajo);

if (!$resultadoProductosStockBajo) {
    die("Error al obtener productos con stock bajo: " . $conexion->error);
}


// =====================================================
// RESUMEN DEL MES
// =====================================================
// IMPORTANTE:
// SOLO SE CONSIDERAN VENTAS REALIZADAS.
// LAS VENTAS ANULADAS NO SE SUMAN.
// =====================================================

$sqlMes = "
    SELECT
        COUNT(*) AS cantidad,
        COALESCE(SUM(total), 0) AS total,
        COALESCE(AVG(total), 0) AS promedio
    FROM ventas
    WHERE YEAR(fecha) = YEAR(CURDATE())
      AND MONTH(fecha) = MONTH(CURDATE())
      AND estado = 'realizada'
";

$resultadoMes = $conexion->query($sqlMes);

if (!$resultadoMes) {
    die("Error al obtener resumen mensual: " . $conexion->error);
}

$resumenMes = $resultadoMes->fetch_assoc();

$ventasMes = (int) $resumenMes["cantidad"];
$totalMes = (float) $resumenMes["total"];
$promedioMes = (float) $resumenMes["promedio"];


// =====================================================
// GRÁFICO: VENTAS ÚLTIMOS 7 DÍAS
// =====================================================

$sqlVentas7Dias = "
    SELECT
        DATE(fecha) AS dia,
        COUNT(*) AS cantidad,
        COALESCE(SUM(total), 0) AS total
    FROM ventas
    WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
      AND estado = 'realizada'
    GROUP BY DATE(fecha)
    ORDER BY dia ASC
";

$resultadoVentas7Dias = $conexion->query($sqlVentas7Dias);

if (!$resultadoVentas7Dias) {
    die("Error al obtener ventas de los últimos días: " . $conexion->error);
}


// Crear los últimos 7 días

$ventasPorDia = [];

for ($i = 6; $i >= 0; $i--) {

    $fecha = date(
        "Y-m-d",
        strtotime("-" . $i . " days")
    );

    $ventasPorDia[$fecha] = 0;
}


// Colocar ventas reales

while ($fila = $resultadoVentas7Dias->fetch_assoc()) {

    $ventasPorDia[$fila["dia"]] = (float) $fila["total"];
}


// Preparar datos para Chart.js

$labels7Dias = [];
$datos7Dias = [];

foreach ($ventasPorDia as $fecha => $total) {

    $labels7Dias[] = date(
        "d/m",
        strtotime($fecha)
    );

    $datos7Dias[] = $total;
}


// =====================================================
// GRÁFICO: VENTAS POR CATEGORÍA DEL MES
// =====================================================

$sqlVentasCategoria = "
    SELECT
        c.nombre AS categoria,
        COALESCE(SUM(dv.subtotal), 0) AS total
    FROM detalle_ventas dv
    INNER JOIN productos p
        ON dv.producto_id = p.id
    INNER JOIN categorias c
        ON p.categoria_id = c.id
    INNER JOIN ventas v
        ON dv.venta_id = v.id
    WHERE YEAR(v.fecha) = YEAR(CURDATE())
      AND MONTH(v.fecha) = MONTH(CURDATE())
      AND v.estado = 'realizada'
    GROUP BY c.id, c.nombre
    ORDER BY total DESC
";

$resultadoVentasCategoria = $conexion->query($sqlVentasCategoria);

if (!$resultadoVentasCategoria) {
    die("Error al obtener ventas por categoría: " . $conexion->error);
}

$labelsCategorias = [];
$datosCategorias = [];

while ($fila = $resultadoVentasCategoria->fetch_assoc()) {

    $labelsCategorias[] = $fila["categoria"];
    $datosCategorias[] = (float) $fila["total"];
}


// =====================================================
// MES ACTUAL EN ESPAÑOL
// =====================================================

$meses = [
    1 => "Enero",
    2 => "Febrero",
    3 => "Marzo",
    4 => "Abril",
    5 => "Mayo",
    6 => "Junio",
    7 => "Julio",
    8 => "Agosto",
    9 => "Septiembre",
    10 => "Octubre",
    11 => "Noviembre",
    12 => "Diciembre"
];

$mesActual = $meses[(int) date("n")];

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Dashboard | Sistema de Inventario</title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Bootstrap Icons -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
    >


    <!-- CSS del sistema -->

    <link
        rel="stylesheet"
        href="assets/css/estilos.css"
    >


    <!-- Chart.js -->

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>


<body>


<!-- =====================================================
     NAVBAR COMPARTIDA
===================================================== -->

<?php

include "includes/navbar.php";

?>


<!-- =====================================================
     CONTENIDO
===================================================== -->

<div class="container-fluid px-4 py-4">


    <!-- =================================================
         ENCABEZADO
    ================================================== -->

    <div class="mb-4">

        <h2 class="dashboard-title mb-1">

            <i class="bi bi-speedometer2"></i>

            Dashboard

        </h2>

        <p class="text-muted mb-0">

            Resumen general del sistema de inventario.

        </p>

    </div>



    <!-- =================================================
         ESTADÍSTICAS PRINCIPALES
    ================================================== -->

    <div class="row g-4 mb-4">


        <!-- PRODUCTOS -->

        <div class="col-md-6 col-xl-3">

            <div class="card stat-card h-100">

                <div class="card-body">

                    <div class="d-flex align-items-center">

                        <div class="stat-icon icon-productos">

                            <i class="bi bi-box-seam"></i>

                        </div>

                        <div class="ms-3">

                            <small class="text-muted">

                                Productos activos

                            </small>

                            <div class="number">

                                <?php
                                echo $totalProductos;
                                ?>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>



        <!-- CATEGORÍAS -->

        <div class="col-md-6 col-xl-3">

            <div class="card stat-card h-100">

                <div class="card-body">

                    <div class="d-flex align-items-center">

                        <div class="stat-icon icon-categorias">

                            <i class="bi bi-tags"></i>

                        </div>

                        <div class="ms-3">

                            <small class="text-muted">

                                Categorías activas

                            </small>

                            <div class="number">

                                <?php
                                echo $totalCategorias;
                                ?>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>



        <!-- VENTAS -->

        <div class="col-md-6 col-xl-3">

            <div class="card stat-card h-100">

                <div class="card-body">

                    <div class="d-flex align-items-center">

                        <div class="stat-icon icon-ventas">

                            <i class="bi bi-cart-check"></i>

                        </div>

                        <div class="ms-3">

                            <small class="text-muted">

                                Ventas realizadas

                            </small>

                            <div class="number">

                                <?php
                                echo $totalVentas;
                                ?>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>



        <!-- STOCK BAJO -->

        <div class="col-md-6 col-xl-3">

            <div class="card stat-card h-100">

                <div class="card-body">

                    <div class="d-flex align-items-center">

                        <div class="stat-icon icon-stock">

                            <i class="bi bi-exclamation-triangle"></i>

                        </div>

                        <div class="ms-3">

                            <small class="text-muted">

                                Stock bajo

                            </small>

                            <div class="number">

                                <?php
                                echo $totalStockBajo;
                                ?>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>


    </div>



    <!-- =================================================
         ESTADÍSTICAS DE HOY
    ================================================== -->

    <div class="row g-4 mb-4">


        <!-- VENTAS HOY -->

        <div class="col-md-6">

            <div class="card dashboard-info-card h-100">

                <div class="card-body">

                    <div class="d-flex align-items-center">

                        <div class="dashboard-small-icon">

                            <i class="bi bi-calendar-day"></i>

                        </div>

                        <div class="ms-3">

                            <h6 class="mb-1">

                                Ventas de hoy

                            </h6>

                            <h4 class="mb-0">

                                <?php
                                echo $cantidadVentasHoy;
                                ?>

                                <small class="text-muted fs-6">

                                    ventas

                                </small>

                            </h4>

                        </div>

                    </div>

                </div>

            </div>

        </div>



        <!-- MONTO HOY -->

        <div class="col-md-6">

            <div class="card dashboard-info-card h-100">

                <div class="card-body">

                    <div class="d-flex align-items-center">

                        <div class="dashboard-small-icon">

                            <i class="bi bi-currency-dollar"></i>

                        </div>

                        <div class="ms-3">

                            <h6 class="mb-1">

                                Total vendido hoy

                            </h6>

                            <h4 class="mb-0">

                                $

                                <?php
                                echo number_format(
                                    $montoVentasHoy,
                                    0,
                                    ",",
                                    "."
                                );
                                ?>

                            </h4>

                        </div>

                    </div>

                </div>

            </div>

        </div>


    </div>



    <!-- =================================================
         GRÁFICOS
    ================================================== -->

    <div class="row g-4 mb-4">


        <!-- VENTAS ÚLTIMOS 7 DÍAS -->

        <div class="col-lg-7">

            <div class="card dashboard-chart-card h-100">

                <div class="card-body">

                    <div class="mb-3">

                        <h5 class="mb-1">

                            <i class="bi bi-graph-up"></i>

                            Ventas de los últimos 7 días

                        </h5>

                        <small class="text-muted">

                            Total vendido por día.

                        </small>

                    </div>


                    <canvas
                        id="graficoVentas7Dias"
                        height="220"
                    ></canvas>

                </div>

            </div>

        </div>



        <!-- VENTAS POR CATEGORÍA -->

        <div class="col-lg-5">

            <div class="card dashboard-chart-card h-100">

                <div class="card-body">

                    <div class="mb-3">

                        <h5 class="mb-1">

                            <i class="bi bi-pie-chart"></i>

                            Ventas por categoría

                        </h5>

                        <small class="text-muted">

                            Distribución de ventas de

                            <?php
                            echo htmlspecialchars($mesActual);
                            ?>.

                        </small>

                    </div>


                    <?php if (count($labelsCategorias) > 0): ?>

                        <canvas
                            id="graficoCategorias"
                            height="220"
                        ></canvas>

                    <?php else: ?>

                        <div class="text-center py-5">

                            <i class="bi bi-bar-chart fs-1 text-muted"></i>

                            <p class="text-muted mt-3 mb-0">

                                No hay ventas este mes.

                            </p>

                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </div>


    </div>



    <!-- =================================================
         ÚLTIMAS VENTAS Y STOCK BAJO
    ================================================== -->

    <div class="row g-4 mb-4">


        <!-- ÚLTIMAS VENTAS -->

        <div class="col-lg-8">

            <div class="card dashboard-table-card h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center mb-3">

                        <div>

                            <h5 class="mb-1">

                                <i class="bi bi-receipt"></i>

                                Últimas ventas

                            </h5>

                            <small class="text-muted">

                                Movimientos más recientes.

                            </small>

                        </div>


                        <a
                            href="views/ventas/listar.php"
                            class="btn btn-sm btn-outline-primary"
                        >

                            Ver todas

                        </a>

                    </div>


                    <?php if (count($productosVentas) > 0): ?>

                        <div class="table-responsive">

                            <table class="table table-hover align-middle mb-0">

                                <thead>

                                    <tr>

                                        <th>
                                            Venta
                                        </th>

                                        <th>
                                            Productos
                                        </th>

                                        <th>
                                            Usuario
                                        </th>

                                        <th>
                                            Fecha
                                        </th>

                                        <th class="text-end">
                                            Total
                                        </th>

                                        <th class="text-center">
                                            Estado
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>

                                    <?php foreach ($productosVentas as $venta): ?>

                                        <tr>

                                            <td>

                                                <strong>

                                                    #<?php
                                                    echo (int) $venta["id"];
                                                    ?>

                                                </strong>

                                            </td>


                                            <td>

                                                <?php if (!empty($venta["productos"])): ?>

                                                    <?php
                                                    echo implode(
                                                        "<br>",
                                                        $venta["productos"]
                                                    );
                                                    ?>

                                                <?php else: ?>

                                                    <span class="text-muted">

                                                        Sin productos

                                                    </span>

                                                <?php endif; ?>

                                            </td>


                                            <td>

                                                <?php
                                                echo htmlspecialchars(
                                                    $venta["usuario"]
                                                );
                                                ?>

                                            </td>


                                            <td>

                                                <?php
                                                echo date(
                                                    "d/m/Y H:i",
                                                    strtotime($venta["fecha"])
                                                );
                                                ?>

                                            </td>


                                            <td class="text-end">

                                                $

                                                <?php
                                                echo number_format(
                                                    $venta["total"],
                                                    0,
                                                    ",",
                                                    "."
                                                );
                                                ?>

                                            </td>


                                            <td class="text-center">

                                                <?php if ($venta["estado"] === "realizada"): ?>

                                                    <span class="badge text-bg-success">

                                                        Realizada

                                                    </span>

                                                <?php else: ?>

                                                    <span class="badge text-bg-danger">

                                                        Anulada

                                                    </span>

                                                <?php endif; ?>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    <?php else: ?>

                        <div class="text-center py-5">

                            <i class="bi bi-receipt fs-1 text-muted"></i>

                            <p class="text-muted mt-3 mb-0">

                                No hay ventas registradas.

                            </p>

                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </div>



        <!-- STOCK BAJO -->

        <div class="col-lg-4">

            <div class="card dashboard-table-card h-100">

                <div class="card-body">

                    <div class="mb-3">

                        <h5 class="mb-1">

                            <i class="bi bi-exclamation-triangle"></i>

                            Stock bajo

                        </h5>

                        <small class="text-muted">

                            Productos que necesitan reposición.

                        </small>

                    </div>


                    <?php if ($resultadoProductosStockBajo->num_rows > 0): ?>

                        <div class="d-flex flex-column gap-2">

                            <?php while (
                                $producto =
                                $resultadoProductosStockBajo->fetch_assoc()
                            ): ?>

                                <div class="stock-item">

                                    <div class="d-flex justify-content-between align-items-center">

                                        <div>

                                            <strong>

                                                <?php
                                                echo htmlspecialchars(
                                                    $producto["nombre"]
                                                );
                                                ?>

                                            </strong>

                                            <br>

                                            <small class="text-muted">

                                                <?php
                                                echo htmlspecialchars(
                                                    $producto["codigo"]
                                                );
                                                ?>

                                            </small>

                                        </div>


                                        <?php if (
                                            $producto["stock"] == 0
                                        ): ?>

                                            <span class="badge text-bg-danger">

                                                Agotado

                                            </span>

                                        <?php else: ?>

                                            <span class="badge text-bg-warning">

                                                <?php
                                                echo $producto["stock"];
                                                ?>

                                                disponibles

                                            </span>

                                        <?php endif; ?>

                                    </div>

                                </div>

                            <?php endwhile; ?>

                        </div>

                    <?php else: ?>

                        <div class="text-center py-5">

                            <i class="bi bi-check-circle fs-1 text-success"></i>

                            <p class="text-muted mt-3 mb-0">

                                No hay productos con stock bajo.

                            </p>

                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </div>


    </div>



    <!-- =================================================
         RESUMEN MENSUAL
    ================================================== -->

    <div class="card dashboard-table-card mb-4">

        <div class="card-body">

            <div class="mb-3">

                <h5 class="mb-1">

                    <i class="bi bi-calendar3"></i>

                    Resumen de <?php echo $mesActual; ?>

                </h5>

                <small class="text-muted">

                    Estadísticas del mes actual.

                </small>

            </div>


            <div class="row g-3">


                <div class="col-md-4">

                    <div class="monthly-stat">

                        <span class="text-muted">

                            Ventas

                        </span>

                        <strong>

                            <?php
                            echo $ventasMes;
                            ?>

                        </strong>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="monthly-stat">

                        <span class="text-muted">

                            Total vendido

                        </span>

                        <strong>

                            $

                            <?php
                            echo number_format(
                                $totalMes,
                                0,
                                ",",
                                "."
                            );
                            ?>

                        </strong>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="monthly-stat">

                        <span class="text-muted">

                            Promedio por venta

                        </span>

                        <strong>

                            $

                            <?php
                            echo number_format(
                                $promedioMes,
                                0,
                                ",",
                                "."
                            );
                            ?>

                        </strong>

                    </div>

                </div>


            </div>

        </div>

    </div>



</div>



<!-- =====================================================
     GRÁFICOS
===================================================== -->

<script>

document.addEventListener("DOMContentLoaded", function () {


    // =================================================
    // GRÁFICO DE VENTAS DE LOS ÚLTIMOS 7 DÍAS
    // =================================================

    const elementoVentas = document.getElementById(
        "graficoVentas7Dias"
    );


    if (elementoVentas) {

        new Chart(elementoVentas, {

            type: "line",

            data: {

                labels: <?php
                    echo json_encode(
                        $labels7Dias,
                        JSON_UNESCAPED_UNICODE
                    );
                ?>,

                datasets: [

                    {

                        label: "Ventas",

                        data: <?php
                            echo json_encode(
                                $datos7Dias
                            );
                        ?>,

                        tension: 0.3,

                        fill: true,

                        borderWidth: 2

                    }

                ]

            },

            options: {

                responsive: true,

                maintainAspectRatio: true,

                plugins: {

                    legend: {

                        display: false

                    },

                    tooltip: {

                        callbacks: {

                            label: function (context) {

                                return "$" +
                                    new Intl.NumberFormat(
                                        "es-CL"
                                    ).format(
                                        context.raw
                                    );

                            }

                        }

                    }

                },

                scales: {

                    y: {

                        beginAtZero: true,

                        ticks: {

                            callback: function (value) {

                                return "$" +
                                    new Intl.NumberFormat(
                                        "es-CL"
                                    ).format(
                                        value
                                    );

                            }

                        }

                    }

                }

            }

        });

    }



    // =================================================
    // GRÁFICO DE VENTAS POR CATEGORÍA
    // =================================================

    const elementoCategorias = document.getElementById(
        "graficoCategorias"
    );


    if (elementoCategorias) {

        new Chart(elementoCategorias, {

            type: "doughnut",

            data: {

                labels: <?php
                    echo json_encode(
                        $labelsCategorias,
                        JSON_UNESCAPED_UNICODE
                    );
                ?>,

                datasets: [

                    {

                        data: <?php
                            echo json_encode(
                                $datosCategorias
                            );
                        ?>,

                        borderWidth: 1

                    }

                ]

            },

            options: {

                responsive: true,

                maintainAspectRatio: true,

                plugins: {

                    legend: {

                        position: "bottom"

                    },

                    tooltip: {

                        callbacks: {

                            label: function (context) {

                                return " $" +
                                    new Intl.NumberFormat(
                                        "es-CL"
                                    ).format(
                                        context.raw
                                    );

                            }

                        }

                    }

                }

            }

        });

    }


});

</script>


</body>

</html>