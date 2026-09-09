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
// VERIFICAR ROL DE ADMINISTRADOR
// =====================================================

if (($_SESSION["rol"] ?? "") !== "admin") {

    http_response_code(403);

    ?>

    <!DOCTYPE html>
    <html lang="es">

    <head>

        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >

        <title>Acceso denegado | Inventario</title>

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

                <div class="card-body text-center py-5">

                    <i
                        class="bi bi-shield-lock fs-1 text-danger"
                    ></i>

                    <h3 class="mt-3">

                        Acceso denegado

                    </h3>

                    <p class="text-muted">

                        No tienes permisos para administrar usuarios.

                    </p>

                    <a
                        href="../../index.php"
                        class="btn btn-primary"
                    >

                        <i class="bi bi-arrow-left"></i>

                        Volver al Dashboard

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
// OBTENER ID
// =====================================================

$id = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$id || $id < 1) {

    header("Location: listar.php");

    exit;
}

// =====================================================
// IMPEDIR DESACTIVAR AL USUARIO ACTUAL
// =====================================================

if (
    (int) $id ===
    (int) $_SESSION["usuario_id"]
) {

    ?>

    <!DOCTYPE html>
    <html lang="es">

    <head>

        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >

        <title>Acción no permitida | Inventario</title>

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

                <div class="card-body text-center py-5">

                    <i
                        class="bi bi-person-lock fs-1 text-warning"
                    ></i>

                    <h3 class="mt-3">

                        Acción no permitida

                    </h3>

                    <p class="text-muted">

                        No puedes desactivar el usuario con el que
                        estás conectado.

                    </p>

                    <a
                        href="listar.php"
                        class="btn btn-primary"
                    >

                        <i class="bi bi-arrow-left"></i>

                        Volver a usuarios

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
// OBTENER USUARIO
// =====================================================

$sqlUsuario = "
    SELECT
        id,
        nombre,
        usuario,
        rol,
        estado
    FROM usuarios
    WHERE id = ?
    LIMIT 1
";

$stmtUsuario = $conexion->prepare($sqlUsuario);

if (!$stmtUsuario) {

    die(
        "Error al preparar la consulta: "
        . $conexion->error
    );
}

$stmtUsuario->bind_param(
    "i",
    $id
);

$stmtUsuario->execute();

$resultadoUsuario =
    $stmtUsuario->get_result();

if ($resultadoUsuario->num_rows === 0) {

    $stmtUsuario->close();

    header("Location: listar.php");

    exit;
}

$usuario = $resultadoUsuario->fetch_assoc();

$stmtUsuario->close();

// =====================================================
// COMPROBAR SI YA ESTÁ INACTIVO
// =====================================================

if ((int) $usuario["estado"] === 0) {

    header(
        "Location: listar.php?mensaje=usuario_ya_inactivo"
    );

    exit;
}

// =====================================================
// PROTEGER AL ÚLTIMO ADMINISTRADOR
// =====================================================

if ($usuario["rol"] === "admin") {

    $sqlAdmins = "
        SELECT COUNT(*) AS total
        FROM usuarios
        WHERE rol = 'admin'
          AND estado = 1
          AND id <> ?
    ";

    $stmtAdmins =
        $conexion->prepare($sqlAdmins);

    if (!$stmtAdmins) {

        die(
            "Error al comprobar administradores: "
            . $conexion->error
        );
    }

    $stmtAdmins->bind_param(
        "i",
        $id
    );

    $stmtAdmins->execute();

    $resultadoAdmins =
        $stmtAdmins->get_result();

    $cantidadAdmins =
        (int) $resultadoAdmins
        ->fetch_assoc()["total"];

    $stmtAdmins->close();

    if ($cantidadAdmins === 0) {

        ?>

        <!DOCTYPE html>
        <html lang="es">

        <head>

            <meta charset="UTF-8">

            <meta
                name="viewport"
                content="width=device-width, initial-scale=1.0"
            >

            <title>Acción no permitida | Inventario</title>

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

                    <div class="card-body text-center py-5">

                        <i
                            class="bi bi-shield-exclamation fs-1 text-danger"
                        ></i>

                        <h3 class="mt-3">

                            No se puede desactivar

                        </h3>

                        <p class="text-muted">

                            Este usuario es el único administrador
                            activo del sistema.

                        </p>

                        <a
                            href="listar.php"
                            class="btn btn-primary"
                        >

                            <i class="bi bi-arrow-left"></i>

                            Volver a usuarios

                        </a>

                    </div>

                </div>

            </div>

        </body>

        </html>

        <?php

        exit;
    }
}

// =====================================================
// DESACTIVAR USUARIO
// =====================================================

$sqlDesactivar = "
    UPDATE usuarios
    SET estado = 0
    WHERE id = ?
";

$stmtDesactivar =
    $conexion->prepare($sqlDesactivar);

if (!$stmtDesactivar) {

    die(
        "Error al preparar la desactivación: "
        . $conexion->error
    );
}

$stmtDesactivar->bind_param(
    "i",
    $id
);

if (!$stmtDesactivar->execute()) {

    $error =
        $stmtDesactivar->error;

    $stmtDesactivar->close();

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

        <?php include "../../includes/navbar.php"; ?>

        <div class="container py-5">

            <div class="card">

                <div class="card-body text-center py-5">

                    <i
                        class="bi bi-x-circle fs-1 text-danger"
                    ></i>

                    <h3 class="mt-3">

                        No se pudo desactivar el usuario

                    </h3>

                    <p class="text-muted">

                        <?php
                        echo htmlspecialchars($error);
                        ?>

                    </p>

                    <a
                        href="listar.php"
                        class="btn btn-primary"
                    >

                        <i class="bi bi-arrow-left"></i>

                        Volver a usuarios

                    </a>

                </div>

            </div>

        </div>

    </body>

    </html>

    <?php

    exit;
}

$stmtDesactivar->close();

// =====================================================
// REDIRECCIÓN
// =====================================================

header(
    "Location: listar.php?mensaje=usuario_desactivado"
);

exit;