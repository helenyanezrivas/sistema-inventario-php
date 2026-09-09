<?php

session_start();

require_once "../../config/database.php";

// Verificar sesión
if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../../login.php");
    exit;
}


// =====================================================
// VALIDAR ID
// =====================================================

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: listar.php");
    exit;
}

$id = (int) $_GET["id"];


// =====================================================
// BUSCAR PRODUCTO
// =====================================================

$sqlProducto = "
    SELECT
        id,
        codigo,
        nombre
    FROM productos
    WHERE id = ?
    LIMIT 1
";

$stmtProducto = $conexion->prepare($sqlProducto);

if (!$stmtProducto) {
    die(
        "Error al preparar la consulta: "
        . $conexion->error
    );
}

$stmtProducto->bind_param("i", $id);
$stmtProducto->execute();

$resultadoProducto = $stmtProducto->get_result();


// Si el producto no existe
if ($resultadoProducto->num_rows === 0) {

    $stmtProducto->close();

    header("Location: listar.php?mensaje=no_encontrado");
    exit;
}

$producto = $resultadoProducto->fetch_assoc();

$stmtProducto->close();


// =====================================================
// VERIFICAR SI EL PRODUCTO TIENE VENTAS
// =====================================================

$sqlVentas = "
    SELECT COUNT(*) AS total
    FROM detalle_ventas
    WHERE producto_id = ?
";

$stmtVentas = $conexion->prepare($sqlVentas);

if (!$stmtVentas) {
    die(
        "Error al preparar la consulta: "
        . $conexion->error
    );
}

$stmtVentas->bind_param("i", $id);
$stmtVentas->execute();

$resultadoVentas = $stmtVentas->get_result();

$totalVentas = $resultadoVentas->fetch_assoc()["total"];

$stmtVentas->close();


// =====================================================
// SI TIENE VENTAS, NO PERMITIR ELIMINACIÓN
// =====================================================

if ($totalVentas > 0) {
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>No se puede eliminar | Inventario</title>


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

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-7">

            <div class="card form-card">

                <div class="card-body p-5 text-center">


                    <!-- ICONO -->

                    <div class="mb-4">

                        <i
                            class="bi bi-shield-exclamation product-delete-icon text-warning"
                        ></i>

                    </div>


                    <!-- TÍTULO -->

                    <h3 class="mb-3">

                        No se puede eliminar el producto

                    </h3>


                    <!-- PRODUCTO -->

                    <div class="alert alert-light border">

                        <strong>

                            <?php
                            echo htmlspecialchars(
                                $producto["nombre"]
                            );
                            ?>

                        </strong>

                        <br>

                        <small class="text-muted">

                            Código:

                            <?php
                            echo htmlspecialchars(
                                $producto["codigo"]
                            );
                            ?>

                        </small>

                    </div>


                    <!-- MENSAJE -->

                    <p class="text-muted">

                        Este producto ya forma parte de

                        <strong>

                            <?php echo $totalVentas; ?>

                            <?php

                            echo $totalVentas == 1
                                ? " venta registrada"
                                : " ventas registradas";

                            ?>

                        </strong>

                        en el sistema.

                    </p>


                    <p class="text-muted">

                        Para proteger el historial de ventas,
                        el producto no puede ser eliminado.

                    </p>


                    <!-- INFORMACIÓN -->

                    <div class="alert alert-info text-start">

                        <i class="bi bi-info-circle me-2"></i>

                        <strong>¿Qué puedes hacer?</strong>

                        <br>

                        Si ya no quieres utilizar este producto,
                        puedes dejarlo

                        <strong>inactivo</strong>

                        desde la opción de editar.

                    </div>


                    <!-- BOTONES -->

                    <div class="d-flex justify-content-center gap-2 mt-4">

                        <a
                            href="listar.php"
                            class="btn btn-secondary"
                        >

                            <i class="bi bi-arrow-left"></i>

                            Volver a productos

                        </a>


                        <a
                            href="../ventas/listar.php"
                            class="btn btn-outline-primary"
                        >

                            <i class="bi bi-receipt"></i>

                            Ver ventas

                        </a>

                    </div>


                </div>

            </div>

        </div>

    </div>

</div>


</body>

</html>

<?php

    exit;
}


// =====================================================
// ELIMINAR PRODUCTO
// =====================================================

$sqlEliminar = "
    DELETE FROM productos
    WHERE id = ?
";

$stmtEliminar = $conexion->prepare($sqlEliminar);

if (!$stmtEliminar) {
    die(
        "Error al preparar la eliminación: "
        . $conexion->error
    );
}

$stmtEliminar->bind_param("i", $id);


if ($stmtEliminar->execute()) {

    $stmtEliminar->close();

    header("Location: listar.php?mensaje=eliminado");
    exit;

} else {

    $error = $stmtEliminar->error;

    $stmtEliminar->close();

    die(
        "Error al eliminar el producto: "
        . $error
    );
}

?>