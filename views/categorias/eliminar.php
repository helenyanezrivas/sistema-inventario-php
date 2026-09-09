<?php

session_start();

require_once "../../config/database.php";

// Verificar sesión
if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../../login.php");
    exit;
}

// Obtener ID de la categoría
$id = intval($_GET["id"] ?? 0);

if ($id <= 0) {
    header("Location: listar.php");
    exit;
}


// =====================================================
// VERIFICAR QUE LA CATEGORÍA EXISTA
// =====================================================

$sqlCategoria = "
    SELECT id, nombre
    FROM categorias
    WHERE id = ?
    LIMIT 1
";

$stmtCategoria = $conexion->prepare($sqlCategoria);
$stmtCategoria->bind_param("i", $id);
$stmtCategoria->execute();

$resultadoCategoria = $stmtCategoria->get_result();

if ($resultadoCategoria->num_rows === 0) {

    $stmtCategoria->close();

    header("Location: listar.php");
    exit;
}

$categoria = $resultadoCategoria->fetch_assoc();

$stmtCategoria->close();


// =====================================================
// VERIFICAR SI TIENE PRODUCTOS ASOCIADOS
// =====================================================

$sqlProductos = "
    SELECT COUNT(*) AS cantidad
    FROM productos
    WHERE categoria_id = ?
";

$stmtProductos = $conexion->prepare($sqlProductos);
$stmtProductos->bind_param("i", $id);
$stmtProductos->execute();

$resultadoProductos = $stmtProductos->get_result();
$datosProductos = $resultadoProductos->fetch_assoc();

$cantidadProductos = (int) $datosProductos["cantidad"];

$stmtProductos->close();


// =====================================================
// NO PERMITIR ELIMINAR SI TIENE PRODUCTOS
// =====================================================

if ($cantidadProductos > 0) {

    $mensaje = "No se puede eliminar la categoría \"" .
        $categoria["nombre"] .
        "\" porque tiene " .
        $cantidadProductos .
        " producto(s) asociado(s).";

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

        <!-- NAVBAR -->

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
                        echo htmlspecialchars($_SESSION["nombre"]);
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


        <!-- CONTENIDO -->

        <div class="container py-5">

            <div class="card">

                <div class="card-body p-5 text-center">

                    <i
                        class="bi bi-exclamation-triangle text-warning"
                        style="font-size: 55px;"
                    ></i>

                    <h3 class="mt-4">

                        No se puede eliminar la categoría

                    </h3>

                    <p class="text-muted mt-3">

                        <?php
                        echo htmlspecialchars($mensaje);
                        ?>

                    </p>

                    <p class="text-muted">

                        Debes eliminar o cambiar de categoría los productos
                        asociados antes de poder eliminar esta categoría.

                    </p>

                    <a
                        href="listar.php"
                        class="btn btn-primary mt-3"
                    >

                        <i class="bi bi-arrow-left"></i>

                        Volver a categorías

                    </a>

                </div>

            </div>

        </div>

    </body>

    </html>

    <?php

    exit;
}


// =====================================================
// ELIMINAR CATEGORÍA
// =====================================================

$sqlEliminar = "
    DELETE FROM categorias
    WHERE id = ?
";

$stmtEliminar = $conexion->prepare($sqlEliminar);
$stmtEliminar->bind_param("i", $id);

if (!$stmtEliminar->execute()) {

    $stmtEliminar->close();

    die(
        "No se pudo eliminar la categoría: " .
        htmlspecialchars($conexion->error)
    );
}

$stmtEliminar->close();


// =====================================================
// VOLVER AL LISTADO
// =====================================================

header("Location: listar.php");
exit;