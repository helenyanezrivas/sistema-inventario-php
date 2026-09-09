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
// VALIDAR ID
// =====================================================

if (!isset($_POST["id"]) || !is_numeric($_POST["id"])) {
    die("Venta inválida.");
}

$id = (int) $_POST["id"];

if ($id <= 0) {
    die("Venta inválida.");
}

// =====================================================
// INICIAR TRANSACCIÓN
// =====================================================

$conexion->begin_transaction();

try {

    // =================================================
    // BUSCAR VENTA Y BLOQUEARLA
    // =================================================

    $sqlVenta = "
        SELECT
            id,
            estado
        FROM ventas
        WHERE id = ?
        FOR UPDATE
    ";

    $stmtVenta = $conexion->prepare($sqlVenta);

    if (!$stmtVenta) {
        throw new Exception(
            "Error al preparar la venta: "
            . $conexion->error
        );
    }

    $stmtVenta->bind_param("i", $id);

    if (!$stmtVenta->execute()) {
        throw new Exception(
            "Error al consultar la venta: "
            . $stmtVenta->error
        );
    }

    $resultadoVenta = $stmtVenta->get_result();

    if ($resultadoVenta->num_rows === 0) {
        $stmtVenta->close();
        throw new Exception("La venta no existe.");
    }

    $venta = $resultadoVenta->fetch_assoc();
    $stmtVenta->close();

    // =================================================
    // VERIFICAR SI YA ESTÁ ANULADA
    // =================================================

    if ($venta["estado"] === "anulada") {
        throw new Exception(
            "Esta venta ya fue anulada anteriormente."
        );
    }

    // =================================================
    // OBTENER DETALLE DE LA VENTA
    // =================================================

    $sqlDetalle = "
        SELECT
            producto_id,
            cantidad
        FROM detalle_ventas
        WHERE venta_id = ?
        FOR UPDATE
    ";

    $stmtDetalle = $conexion->prepare($sqlDetalle);

    if (!$stmtDetalle) {
        throw new Exception(
            "Error al preparar el detalle: "
            . $conexion->error
        );
    }

    $stmtDetalle->bind_param("i", $id);

    if (!$stmtDetalle->execute()) {
        throw new Exception(
            "Error al obtener el detalle: "
            . $stmtDetalle->error
        );
    }

    $resultadoDetalle = $stmtDetalle->get_result();

    // =================================================
    // RESTAURAR STOCK
    // =================================================

    while ($detalle = $resultadoDetalle->fetch_assoc()) {

        $productoId = (int) $detalle["producto_id"];
        $cantidad = (int) $detalle["cantidad"];

        $sqlStock = "
            UPDATE productos
            SET stock = stock + ?
            WHERE id = ?
        ";

        $stmtStock = $conexion->prepare($sqlStock);

        if (!$stmtStock) {
            throw new Exception(
                "Error al preparar la actualización del stock: "
                . $conexion->error
            );
        }

        $stmtStock->bind_param(
            "ii",
            $cantidad,
            $productoId
        );

        if (!$stmtStock->execute()) {
            throw new Exception(
                "Error al restaurar el stock: "
                . $stmtStock->error
            );
        }

        $stmtStock->close();
    }

    $stmtDetalle->close();

    // =================================================
    // CAMBIAR ESTADO DE LA VENTA
    // =================================================

    $sqlAnular = "
        UPDATE ventas
        SET estado = 'anulada'
        WHERE id = ?
    ";

    $stmtAnular = $conexion->prepare($sqlAnular);

    if (!$stmtAnular) {
        throw new Exception(
            "Error al preparar la anulación: "
            . $conexion->error
        );
    }

    $stmtAnular->bind_param("i", $id);

    if (!$stmtAnular->execute()) {
        throw new Exception(
            "Error al anular la venta: "
            . $stmtAnular->error
        );
    }

    $stmtAnular->close();

    // =================================================
    // CONFIRMAR TRANSACCIÓN
    // =================================================

    $conexion->commit();

    // =================================================
    // VOLVER AL LISTADO
    // =================================================

    header("Location: listar.php?mensaje=venta_anulada");
    exit;

} catch (Exception $e) {

    // =================================================
    // DESHACER CAMBIOS
    // =================================================

    $conexion->rollback();

    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >

        <title>Error | Inventario</title>

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

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-7">
                <div class="card shadow">
                    <div class="card-body text-center">

                        <div class="text-danger mb-3">
                            <i class="bi bi-exclamation-triangle fs-1"></i>
                        </div>

                        <h3 class="mb-3">
                            No se pudo anular la venta
                        </h3>

                        <p class="text-muted">
                            <?php
                            echo htmlspecialchars(
                                $e->getMessage(),
                                ENT_QUOTES,
                                "UTF-8"
                            );
                            ?>
                        </p>

                        <a
                            href="listar.php"
                            class="btn btn-primary"
                        >
                            <i class="bi bi-arrow-left"></i>
                            Volver a ventas
                        </a>

                    </div>
                </div>
            </div>
        </div>
    </div>

    </body>
    </html>
    <?php
}
?>