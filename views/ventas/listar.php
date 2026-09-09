<?php

session_start();

require_once "../../config/database.php";


// =====================================================
// VERIFICAR SESIÓN
// =====================================================

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../../login.php");
    exit;
}


// =====================================================
// FILTROS
// =====================================================

$fechaDesde = $_GET["fecha_desde"] ?? "";
$fechaHasta = $_GET["fecha_hasta"] ?? "";
$usuarioFiltro = $_GET["usuario"] ?? "";
$estadoFiltro = $_GET["estado"] ?? "";


// =====================================================
// VALIDAR ESTADO
// =====================================================

$estadosPermitidos = [
    "",
    "realizada",
    "anulada"
];

if (!in_array($estadoFiltro, $estadosPermitidos, true)) {
    $estadoFiltro = "";
}


// =====================================================
// OBTENER USUARIOS
// =====================================================

$sqlUsuarios = "
    SELECT
        id,
        nombre
    FROM usuarios
    ORDER BY nombre ASC
";

$resultadoUsuarios = $conexion->query($sqlUsuarios);

if (!$resultadoUsuarios) {
    die(
        "Error al obtener los usuarios: "
        . $conexion->error
    );
}


// =====================================================
// CONSULTA DE VENTAS
// =====================================================

$sql = "
    SELECT
        v.id,
        v.fecha,
        v.total,
        v.estado,
        u.nombre AS usuario
    FROM ventas v
    INNER JOIN usuarios u
        ON v.usuario_id = u.id
    WHERE 1 = 1
";

$tipos = "";
$parametros = [];


// =====================================================
// FILTRO FECHA DESDE
// =====================================================

if ($fechaDesde !== "") {

    $sql .= "
        AND DATE(v.fecha) >= ?
    ";

    $tipos .= "s";

    $parametros[] = $fechaDesde;
}


// =====================================================
// FILTRO FECHA HASTA
// =====================================================

if ($fechaHasta !== "") {

    $sql .= "
        AND DATE(v.fecha) <= ?
    ";

    $tipos .= "s";

    $parametros[] = $fechaHasta;
}


// =====================================================
// FILTRO USUARIO
// =====================================================

if (
    $usuarioFiltro !== ""
    && is_numeric($usuarioFiltro)
) {

    $sql .= "
        AND v.usuario_id = ?
    ";

    $tipos .= "i";

    $parametros[] = (int) $usuarioFiltro;
}


// =====================================================
// FILTRO ESTADO
// =====================================================

if ($estadoFiltro !== "") {

    $sql .= "
        AND v.estado = ?
    ";

    $tipos .= "s";

    $parametros[] = $estadoFiltro;
}


// =====================================================
// ORDEN
// =====================================================

$sql .= "
    ORDER BY v.id DESC
";


// =====================================================
// PREPARAR CONSULTA
// =====================================================

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    die(
        "Error al preparar la consulta: "
        . $conexion->error
    );
}


// =====================================================
// ASIGNAR PARÁMETROS
// =====================================================

if (!empty($parametros)) {

    $stmt->bind_param(
        $tipos,
        ...$parametros
    );
}


// =====================================================
// EJECUTAR
// =====================================================

if (!$stmt->execute()) {

    die(
        "Error al obtener las ventas: "
        . $stmt->error
    );
}

$resultado = $stmt->get_result();


// =====================================================
// FILTROS PARA EXPORTACIÓN
// =====================================================

$filtrosExportacion = [];

if ($fechaDesde !== "") {
    $filtrosExportacion["fecha_desde"] = $fechaDesde;
}

if ($fechaHasta !== "") {
    $filtrosExportacion["fecha_hasta"] = $fechaHasta;
}

if ($usuarioFiltro !== "") {
    $filtrosExportacion["usuario"] = $usuarioFiltro;
}

if ($estadoFiltro !== "") {
    $filtrosExportacion["estado"] = $estadoFiltro;
}


$queryExportacion = http_build_query(
    $filtrosExportacion
);


$enlaceExcel = "exportar_excel.php";
$enlacePDF = "exportar_pdf.php";


if ($queryExportacion !== "") {

    $enlaceExcel .= "?" . $queryExportacion;

    $enlacePDF .= "?" . $queryExportacion;
}

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Ventas | Inventario</title>


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

<?php

include "../../includes/navbar.php";

?>


<!-- =====================================================
     CONTENIDO
===================================================== -->

<div class="container-fluid px-4 py-4">


    <!-- =================================================
         ENCABEZADO
    ================================================== -->

    <div
        class="d-flex justify-content-between align-items-center mb-4"
    >

        <div>

            <h2 class="mb-1">

                <i class="bi bi-cart-check"></i>

                Ventas

            </h2>

            <p class="text-muted mb-0">

                Gestiona y consulta las ventas realizadas.

            </p>

        </div>


        <!-- BOTONES -->

        <div class="d-flex gap-2">


            <!-- EXCEL -->

            <a
                href="<?php echo htmlspecialchars($enlaceExcel); ?>"
                class="btn btn-success"
                title="Exportar ventas a Excel"
            >

                <i class="bi bi-file-earmark-excel"></i>

                Excel

            </a>


            <!-- PDF -->

            <a
                href="<?php echo htmlspecialchars($enlacePDF); ?>"
                class="btn btn-danger"
                title="Exportar ventas a PDF"
            >

                <i class="bi bi-file-earmark-pdf"></i>

                PDF

            </a>


            <!-- NUEVA VENTA -->

            <a
                href="crear.php"
                class="btn btn-primary"
            >

                <i class="bi bi-plus-lg"></i>

                Nueva venta

            </a>

        </div>

    </div>


    <!-- =================================================
         MENSAJES DE CONFIRMACIÓN
    ================================================== -->

    <?php

    $mensaje = $_GET["mensaje"] ?? "";

    ?>

    <?php if ($mensaje === "venta_anulada"): ?>

        <div
            class="alert alert-success alert-dismissible fade show"
            role="alert"
            id="mensajeConfirmacion"
        >

            <i class="bi bi-check-circle"></i>

            La venta fue anulada correctamente y el stock
            de los productos fue restaurado.

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Cerrar"
            ></button>

        </div>

    <?php elseif ($mensaje === "venta_registrada"): ?>

        <div
            class="alert alert-success alert-dismissible fade show"
            role="alert"
            id="mensajeConfirmacion"
        >

            <i class="bi bi-check-circle"></i>

            La venta fue registrada correctamente.

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Cerrar"
            ></button>

        </div>

    <?php endif; ?>


    <!-- =================================================
         FILTROS
    ================================================== -->

    <div class="card mb-4">

        <div class="card-body">

            <form
                method="GET"
                action="listar.php"
            >

                <div class="row g-3 align-items-end">


                    <!-- DESDE -->

                    <div class="col-xl-2 col-lg-3">

                        <label
                            for="fecha_desde"
                            class="form-label"
                        >

                            Desde

                        </label>

                        <input
                            type="date"
                            id="fecha_desde"
                            name="fecha_desde"
                            class="form-control"
                            value="<?php echo htmlspecialchars($fechaDesde); ?>"
                        >

                    </div>


                    <!-- HASTA -->

                    <div class="col-xl-2 col-lg-3">

                        <label
                            for="fecha_hasta"
                            class="form-label"
                        >

                            Hasta

                        </label>

                        <input
                            type="date"
                            id="fecha_hasta"
                            name="fecha_hasta"
                            class="form-control"
                            value="<?php echo htmlspecialchars($fechaHasta); ?>"
                        >

                    </div>


                    <!-- USUARIO -->

                    <div class="col-xl-2 col-lg-3">

                        <label
                            for="usuario"
                            class="form-label"
                        >

                            Usuario

                        </label>

                        <select
                            id="usuario"
                            name="usuario"
                            class="form-select"
                        >

                            <option value="">

                                Todos

                            </option>


                            <?php while (
                                $usuario =
                                $resultadoUsuarios->fetch_assoc()
                            ): ?>

                                <option
                                    value="<?php echo $usuario["id"]; ?>"
                                    <?php

                                    echo (
                                        $usuarioFiltro !== ""
                                        && (int) $usuarioFiltro
                                        === (int) $usuario["id"]
                                    )
                                        ? "selected"
                                        : "";

                                    ?>
                                >

                                    <?php

                                    echo htmlspecialchars(
                                        $usuario["nombre"]
                                    );

                                    ?>

                                </option>

                            <?php endwhile; ?>

                        </select>

                    </div>


                    <!-- ESTADO -->

                    <div class="col-xl-2 col-lg-3">

                        <label
                            for="estado"
                            class="form-label"
                        >

                            Estado

                        </label>

                        <select
                            id="estado"
                            name="estado"
                            class="form-select"
                        >

                            <option
                                value=""
                                <?php

                                echo $estadoFiltro === ""
                                    ? "selected"
                                    : "";

                                ?>
                            >

                                Todos

                            </option>


                            <option
                                value="realizada"
                                <?php

                                echo $estadoFiltro === "realizada"
                                    ? "selected"
                                    : "";

                                ?>
                            >

                                Realizadas

                            </option>


                            <option
                                value="anulada"
                                <?php

                                echo $estadoFiltro === "anulada"
                                    ? "selected"
                                    : "";

                                ?>
                            >

                                Anuladas

                            </option>

                        </select>

                    </div>


                    <!-- BUSCAR Y LIMPIAR -->

                    <div class="col-xl-2 col-lg-6">

                        <div class="d-flex gap-2">


                            <button
                                type="submit"
                                class="btn btn-primary flex-grow-1"
                            >

                                <i class="bi bi-search"></i>

                                Buscar

                            </button>


                            <a
                                href="listar.php"
                                class="btn btn-outline-secondary"
                                title="Limpiar filtros"
                            >

                                <i class="bi bi-arrow-counterclockwise"></i>

                            </a>

                        </div>

                    </div>


                </div>

            </form>

        </div>

    </div>


    <!-- =================================================
         RESULTADOS
    ================================================== -->

    <div class="card">

        <div class="card-body">


            <!-- ENCABEZADO -->

            <div
                class="d-flex justify-content-between align-items-center mb-3"
            >

                <div>

                    <h5 class="mb-1">

                        <i class="bi bi-receipt"></i>

                        Historial de ventas

                    </h5>

                    <small class="text-muted">

                        <?php

                        echo $resultado->num_rows;

                        ?>

                        <?php

                        echo $resultado->num_rows === 1
                            ? " venta encontrada"
                            : " ventas encontradas";

                        ?>

                    </small>

                </div>


                <?php if (
                    $fechaDesde !== ""
                    || $fechaHasta !== ""
                    || $usuarioFiltro !== ""
                    || $estadoFiltro !== ""
                ): ?>

                    <span
                        class="badge rounded-pill text-bg-light border"
                    >

                        <i class="bi bi-funnel"></i>

                        Filtros aplicados

                    </span>

                <?php endif; ?>

            </div>


            <!-- =================================================
                 TABLA
            ================================================== -->

            <div class="table-responsive">

                <table
                    class="table table-hover align-middle"
                >

                    <thead>

                        <tr>

                            <th>Venta</th>

                            <th>Productos</th>

                            <th>Usuario</th>

                            <th>Fecha</th>

                            <th>Total</th>

                            <th>Acciones</th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if ($resultado->num_rows > 0): ?>


                        <?php while (
                            $venta =
                            $resultado->fetch_assoc()
                        ): ?>


                            <?php

                            // =================================================
                            // PRODUCTOS DE LA VENTA
                            // =================================================

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


                            $stmtDetalle =
                                $conexion->prepare(
                                    $sqlDetalle
                                );


                            if (!$stmtDetalle) {

                                die(
                                    "Error al preparar detalle: "
                                    . $conexion->error
                                );

                            }


                            $stmtDetalle->bind_param(
                                "i",
                                $venta["id"]
                            );


                            if (!$stmtDetalle->execute()) {

                                die(
                                    "Error al obtener detalle: "
                                    . $stmtDetalle->error
                                );

                            }


                            $resultadoDetalle =
                                $stmtDetalle->get_result();


                            $productosVenta = [];


                            while (
                                $detalle =
                                $resultadoDetalle->fetch_assoc()
                            ) {

                                $productosVenta[] =
                                    htmlspecialchars(
                                        $detalle["nombre"]
                                    )
                                    . " × "
                                    . (int) $detalle["cantidad"];

                            }


                            $stmtDetalle->close();

                            ?>


                            <!-- FILA -->

                            <tr>


                                <!-- VENTA -->

                                <td>

                                    <strong>

                                        #<?php
                                        echo $venta["id"];
                                        ?>

                                    </strong>

                                    <br>


                                    <?php if (
                                        $venta["estado"] === "realizada"
                                    ): ?>

                                        <span
                                            class="badge text-bg-success"
                                        >

                                            <i class="bi bi-check-circle"></i>

                                            Realizada

                                        </span>

                                    <?php else: ?>

                                        <span
                                            class="badge text-bg-danger"
                                        >

                                            <i class="bi bi-x-circle"></i>

                                            Anulada

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- PRODUCTOS -->

                                <td>

                                    <?php

                                    if (!empty($productosVenta)) {

                                        echo implode(
                                            "<br>",
                                            $productosVenta
                                        );

                                    } else {

                                        echo '<span class="text-muted">Sin productos</span>';

                                    }

                                    ?>

                                </td>


                                <!-- USUARIO -->

                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $venta["usuario"]
                                    );

                                    ?>

                                </td>


                                <!-- FECHA -->

                                <td>

                                    <?php

                                    echo date(
                                        "d/m/Y H:i",
                                        strtotime(
                                            $venta["fecha"]
                                        )
                                    );

                                    ?>

                                </td>


                                <!-- TOTAL -->

                                <td>

                                    <strong>

                                        $<?php

                                        echo number_format(
                                            $venta["total"],
                                            0,
                                            ",",
                                            "."
                                        );

                                        ?>

                                    </strong>

                                </td>


                                <!-- ACCIONES -->

                                <td>

                                    <div class="d-flex gap-1">


                                        <!-- VER -->

                                        <a
                                            href="ver.php?id=<?php echo $venta["id"]; ?>"
                                            class="btn btn-sm btn-info"
                                            title="Ver venta"
                                        >

                                            <i class="bi bi-eye"></i>

                                        </a>


                                        <?php if (
                                            $venta["estado"] === "realizada"
                                        ): ?>


                                            <!-- ANULAR -->

                                            <a
                                                href="eliminar.php?id=<?php echo $venta["id"]; ?>"
                                                class="btn btn-sm btn-danger"
                                                title="Anular venta"
                                                onclick="return confirm('¿Estás segura de anular esta venta?\\n\\nLa venta permanecerá en el historial y el stock de los productos será restaurado.');"
                                            >

                                                <i class="bi bi-x-circle"></i>

                                            </a>


                                        <?php endif; ?>


                                    </div>

                                </td>


                            </tr>


                        <?php endwhile; ?>


                    <?php else: ?>


                        <!-- SIN RESULTADOS -->

                        <tr>

                            <td
                                colspan="6"
                                class="text-center py-5"
                            >

                                <i
                                    class="bi bi-search fs-1 text-muted"
                                ></i>


                                <h5 class="mt-3">

                                    No se encontraron ventas

                                </h5>


                                <p class="text-muted mb-3">

                                    Prueba cambiando los filtros
                                    de búsqueda.

                                </p>


                                <a
                                    href="listar.php"
                                    class="btn btn-outline-primary"
                                >

                                    <i class="bi bi-arrow-counterclockwise"></i>

                                    Limpiar filtros

                                </a>

                            </td>

                        </tr>


                    <?php endif; ?>


                    </tbody>

                </table>

            </div>


        </div>

    </div>


</div>


<!-- =====================================================
     BOOTSTRAP JS
===================================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"
></script>

<script>

document.addEventListener("DOMContentLoaded", function () {

    const alerta = document.getElementById("mensajeConfirmacion");

    if (!alerta) {
        return;
    }

    setTimeout(function () {

        const instancia = bootstrap.Alert.getOrCreateInstance(alerta);

        instancia.close();

    }, 4000);

});

</script>


</body>

</html>


<?php

$stmt->close();

?>