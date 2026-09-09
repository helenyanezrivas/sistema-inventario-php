<?php

session_start();

require_once "../../config/database.php";
require_once "../../includes/security.php";

// =====================================================
// VERIFICAR SESIÓN
// =====================================================

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../../login.php");
    exit;
}

// =====================================================
// SOLO PERMITIR POST
// =====================================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: listar.php");
    exit;
}

// =====================================================
// VERIFICAR CSRF
// =====================================================

verificar_csrf();

// =====================================================
// OBTENER ID
// =====================================================

$id = filter_input(
    INPUT_POST,
    "id",
    FILTER_VALIDATE_INT
);

if (!$id || $id < 1) {
    header("Location: listar.php");
    exit;
}


// =====================================================
// VERIFICAR QUE LA CATEGORÍA EXISTA
// =====================================================

$sqlCategoria = "
    SELECT
        id,
        nombre
    FROM categorias
    WHERE id = ?
    LIMIT 1
";

$stmtCategoria = $conexion->prepare($sqlCategoria);

if (!$stmtCategoria) {
    die("Ocurrió un error al preparar la consulta.");
}

$stmtCategoria->bind_param(
    "i",
    $id
);

$stmtCategoria->execute();

$resultadoCategoria =
    $stmtCategoria->get_result();

if ($resultadoCategoria->num_rows === 0) {

    $stmtCategoria->close();

    header("Location: listar.php");
    exit;
}

$categoria =
    $resultadoCategoria->fetch_assoc();

$stmtCategoria->close();


// =====================================================
// VERIFICAR SI TIENE PRODUCTOS ASOCIADOS
// =====================================================

$sqlProductos = "
    SELECT COUNT(*) AS cantidad
    FROM productos
    WHERE categoria_id = ?
";

$stmtProductos =
    $conexion->prepare($sqlProductos);

if (!$stmtProductos) {
    die("Ocurrió un error al preparar la consulta.");
}

$stmtProductos->bind_param(
    "i",
    $id
);

$stmtProductos->execute();

$resultadoProductos =
    $stmtProductos->get_result();

$datosProductos =
    $resultadoProductos->fetch_assoc();

$cantidadProductos =
    (int) $datosProductos["cantidad"];

$stmtProductos->close();


// =====================================================
// NO PERMITIR ELIMINAR SI TIENE PRODUCTOS
// =====================================================

if ($cantidadProductos > 0) {

    $mensaje =
        "No se puede eliminar la categoría \"" .
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

        <link
            href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
            rel="stylesheet"
        >

        <link
            rel="stylesheet"
            href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
        >

        <link
            rel="stylesheet"
            href="../../assets/css/estilos.css"
        >

    </head>

    <body>

        <?php include "../../includes/navbar.php"; ?>

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
                        echo htmlspecialchars(
                            $mensaje,
                            ENT_QUOTES,
                            "UTF-8"
                        );
                        ?>

                    </p>

                    <p class="text-muted">

                        Debes eliminar o cambiar de categoría los
                        productos asociados antes de poder eliminar
                        esta categoría.

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

$stmtEliminar =
    $conexion->prepare($sqlEliminar);

if (!$stmtEliminar) {
    die("Ocurrió un error al preparar la eliminación.");
}

$stmtEliminar->bind_param(
    "i",
    $id
);

if (!$stmtEliminar->execute()) {

    $stmtEliminar->close();

    die("No se pudo eliminar la categoría.");
}

$stmtEliminar->close();


// =====================================================
// VOLVER AL LISTADO
// =====================================================

header("Location: listar.php");

exit;