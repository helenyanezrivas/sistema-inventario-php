<?php

session_start();

require_once "../../config/database.php";

// Verificar sesión
if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../../login.php");
    exit;
}

// Verificar ID de venta
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: listar.php");
    exit;
}

$venta_id = (int) $_GET["id"];


// =====================================================
// OBTENER INFORMACIÓN DE LA VENTA
// =====================================================

$sql_venta = "SELECT
                v.id,
                v.fecha,
                v.total,
                v.estado,
                u.nombre AS usuario
              FROM ventas v
              INNER JOIN usuarios u
                  ON v.usuario_id = u.id
              WHERE v.id = ?";

$stmt_venta = $conexion->prepare($sql_venta);

if (!$stmt_venta) {
    die("Error al preparar la consulta: " . $conexion->error);
}

$stmt_venta->bind_param("i", $venta_id);
$stmt_venta->execute();

$resultado_venta = $stmt_venta->get_result();

if ($resultado_venta->num_rows === 0) {

    $stmt_venta->close();

    header("Location: listar.php");
    exit;
}

$venta = $resultado_venta->fetch_assoc();

$stmt_venta->close();


// =====================================================
// OBTENER PRODUCTOS DE LA VENTA
// =====================================================

$sql_detalle = "SELECT
                    dv.id,
                    p.codigo,
                    p.nombre,
                    dv.cantidad,
                    dv.precio,
                    dv.subtotal
                FROM detalle_ventas dv
                INNER JOIN productos p
                    ON dv.producto_id = p.id
                WHERE dv.venta_id = ?
                ORDER BY dv.id ASC";

$stmt_detalle = $conexion->prepare($sql_detalle);

if (!$stmt_detalle) {
    die("Error al preparar el detalle: " . $conexion->error);
}

$stmt_detalle->bind_param("i", $venta_id);
$stmt_detalle->execute();

$resultado_detalle = $stmt_detalle->get_result();

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Venta #<?php echo $venta["id"]; ?> | Inventario
    </title>


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
        href="../../assets/css/estilos.css"
    >

</head>


<body>


<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="navbar navbar-dark">

    <div class="container-fluid px-4">

        <a
            href="../../index.php"
            class="navbar-brand fw-bold"
        >

            <i class="bi bi-box-seam"></i>

            Sistema de Inventario

        </a>


        <div class="d-flex align-items-center gap-3">

            <span class="text-white">

                <i class="bi bi-person-circle"></i>

                <?php
                echo htmlspecialchars(
                    $_SESSION["nombre"]
                );
                ?>

            </span>


            <a
                href="../../logout.php"
                class="btn btn-light btn-sm"
            >

                <i class="bi bi-box-arrow-right"></i>

                Cerrar sesión

            </a>

        </div>

    </div>

</nav>


<!-- =====================================================
     CONTENIDO
===================================================== -->

<div class="container-fluid px-4 py-4">


    <!-- ENCABEZADO -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2>

                <i class="bi bi-receipt"></i>

                Venta #<?php echo $venta["id"]; ?>

            </h2>


            <p class="text-muted mb-0">

                Detalle de la venta

            </p>

        </div>


        <a
            href="listar.php"
            class="btn btn-secondary"
        >

            <i class="bi bi-arrow-left"></i>

            Volver

        </a>

    </div>


    <!-- =================================================
         INFORMACIÓN DE LA VENTA
    ================================================== -->

    <div class="card mb-4">

        <div class="card-body">

            <div class="row">


                <!-- ID -->

                <div class="col-md-3 mb-3 mb-md-0">

                    <small class="text-muted">
                        Número de venta
                    </small>

                    <div class="fw-bold fs-5">

                        #<?php echo $venta["id"]; ?>

                    </div>

                </div>


                <!-- FECHA -->

                <div class="col-md-3 mb-3 mb-md-0">

                    <small class="text-muted">
                        Fecha
                    </small>

                    <div class="fw-bold">

                        <?php
                        echo date(
                            "d/m/Y H:i",
                            strtotime($venta["fecha"])
                        );
                        ?>

                    </div>

                </div>


                <!-- USUARIO -->

                <div class="col-md-3 mb-3 mb-md-0">

                    <small class="text-muted">
                        Usuario
                    </small>

                    <div class="fw-bold">

                        <i class="bi bi-person"></i>

                        <?php
                        echo htmlspecialchars(
                            $venta["usuario"]
                        );
                        ?>

                    </div>

                </div>


                <!-- ESTADO -->

                <div class="col-md-3 mb-3 mb-md-0">

                    <small class="text-muted">
                        Estado
                    </small>

                    <div class="fw-bold">

                        <?php if ($venta["estado"] === "anulada"): ?>

                            <span class="badge bg-danger">
                                <i class="bi bi-x-circle"></i>
                                Anulada
                            </span>

                        <?php else: ?>

                            <span class="badge bg-success">
                                <i class="bi bi-check-circle"></i>
                                Realizada
                            </span>

                        <?php endif; ?>

                    </div>

                </div>


                <!-- TOTAL -->

                <div class="col-md-3">

                    <small class="text-muted">
                        Total
                    </small>

                    <div class="fw-bold fs-4">

                        $

                        <?php
                        echo number_format(
                            $venta["total"],
                            0,
                            ",",
                            "."
                        );
                        ?>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- =================================================
         PRODUCTOS VENDIDOS
    ================================================== -->

    <div class="card">

        <div class="card-body">

            <h5 class="mb-4">

                <i class="bi bi-box-seam"></i>

                Productos vendidos

            </h5>


            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead>

                        <tr>

                            <th>Código</th>

                            <th>Producto</th>

                            <th class="text-center">
                                Cantidad
                            </th>

                            <th class="text-end">
                                Precio unitario
                            </th>

                            <th class="text-end">
                                Subtotal
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php
                        if ($resultado_detalle->num_rows > 0):
                        ?>

                            <?php
                            while (
                                $detalle =
                                $resultado_detalle->fetch_assoc()
                            ):
                            ?>

                                <tr>

                                    <!-- CÓDIGO -->

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $detalle["codigo"]
                                        );
                                        ?>

                                    </td>


                                    <!-- PRODUCTO -->

                                    <td>

                                        <i class="bi bi-box"></i>

                                        <?php
                                        echo htmlspecialchars(
                                            $detalle["nombre"]
                                        );
                                        ?>

                                    </td>


                                    <!-- CANTIDAD -->

                                    <td class="text-center">

                                        <?php
                                        echo $detalle["cantidad"];
                                        ?>

                                    </td>


                                    <!-- PRECIO -->

                                    <td class="text-end">

                                        $

                                        <?php
                                        echo number_format(
                                            $detalle["precio"],
                                            0,
                                            ",",
                                            "."
                                        );
                                        ?>

                                    </td>


                                    <!-- SUBTOTAL -->

                                    <td class="text-end fw-bold">

                                        $

                                        <?php
                                        echo number_format(
                                            $detalle["subtotal"],
                                            0,
                                            ",",
                                            "."
                                        );
                                        ?>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>

                                <td
                                    colspan="5"
                                    class="text-center py-4 text-muted"
                                >

                                    No hay productos asociados
                                    a esta venta.

                                </td>

                            </tr>

                        <?php endif; ?>


                    </tbody>

                </table>

            </div>


            <!-- TOTAL -->

            <div class="d-flex justify-content-end mt-4">

                <div class="text-end">

                    <div class="text-muted">
                        Total de la venta
                    </div>

                    <div class="fs-3 fw-bold">

                        $

                        <?php
                        echo number_format(
                            $venta["total"],
                            0,
                            ",",
                            "."
                        );
                        ?>

                    </div>

                </div>

            </div>

        </div>

    </div>


</div>


</body>

</html>

<?php

$stmt_detalle->close();

?>