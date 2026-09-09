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

                        No tienes permisos para editar usuarios.

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

$resultadoUsuario = $stmtUsuario->get_result();

if ($resultadoUsuario->num_rows === 0) {

    $stmtUsuario->close();

    header("Location: listar.php");

    exit;
}

$usuarioActual = $resultadoUsuario->fetch_assoc();

$stmtUsuario->close();

// =====================================================
// VARIABLES DEL FORMULARIO
// =====================================================

$nombre = $usuarioActual["nombre"];
$usuario = $usuarioActual["usuario"];
$rol = $usuarioActual["rol"];
$estado = (int) $usuarioActual["estado"];

$errores = [];

// =====================================================
// DETERMINAR SI ES EL USUARIO ACTUAL
// =====================================================

$esUsuarioActual =
    (int) $id ===
    (int) $_SESSION["usuario_id"];

// =====================================================
// PROCESAR FORMULARIO
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre = trim($_POST["nombre"] ?? "");
    $usuario = trim($_POST["usuario"] ?? "");
    $password = $_POST["password"] ?? "";
    $passwordConfirmacion =
        $_POST["password_confirmacion"] ?? "";

    $rolSolicitado =
        $_POST["rol"] ?? $rol;

    $estadoSolicitado =
        isset($_POST["estado"])
        ? 1
        : 0;

    // =================================================
    // VALIDAR NOMBRE
    // =================================================

    if ($nombre === "") {

        $errores[] =
            "El nombre es obligatorio.";

    } elseif (mb_strlen($nombre) < 3) {

        $errores[] =
            "El nombre debe tener al menos 3 caracteres.";

    }

    // =================================================
    // VALIDAR USUARIO
    // =================================================

    if ($usuario === "") {

        $errores[] =
            "El nombre de usuario es obligatorio.";

    } elseif (
        !preg_match(
            '/^[a-zA-Z0-9._-]+$/',
            $usuario
        )
    ) {

        $errores[] =
            "El usuario solo puede contener letras, números, punto, guion y guion bajo.";

    } elseif (mb_strlen($usuario) < 3) {

        $errores[] =
            "El usuario debe tener al menos 3 caracteres.";

    }

    // =================================================
    // VALIDAR CONTRASEÑA
    // =================================================

    if ($password !== "") {

        if (strlen($password) < 8) {

            $errores[] =
                "La nueva contraseña debe tener al menos 8 caracteres.";

        }

        if (
            $password !==
            $passwordConfirmacion
        ) {

            $errores[] =
                "Las contraseñas no coinciden.";

        }

    }

    // =================================================
    // VALIDAR ROL
    // =================================================

    if (
        !in_array(
            $rolSolicitado,
            ["admin", "vendedor"],
            true
        )
    ) {

        $errores[] =
            "El rol seleccionado no es válido.";

    }

    // =================================================
    // PROTEGER AL ADMINISTRADOR PRINCIPAL
    // =================================================

    if (
        $esUsuarioActual
        && $rol === "admin"
        && $rolSolicitado !== "admin"
    ) {

        $errores[] =
            "No puedes cambiar tu propio rol de administrador mientras estás conectado.";

    }

    if (
        $esUsuarioActual
        && $estadoSolicitado === 0
    ) {

        $errores[] =
            "No puedes desactivar el usuario con el que estás conectado.";

    }

    // =================================================
    // COMPROBAR QUE NO SE ELIMINE EL ÚLTIMO ADMIN
    // =================================================

    if (
        $rol === "admin"
        && $rolSolicitado !== "admin"
        && empty($errores)
    ) {

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

            $errores[] =
                "Error al comprobar los administradores: "
                . $conexion->error;

        } else {

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

                $errores[] =
                    "No puedes cambiar este usuario a vendedor porque debe existir al menos un administrador activo.";

            }

        }

    }

    // =================================================
    // COMPROBAR USUARIO DUPLICADO
    // =================================================

    if (empty($errores)) {

        $sqlExiste = "
            SELECT id
            FROM usuarios
            WHERE LOWER(usuario) = LOWER(?)
              AND id <> ?
            LIMIT 1
        ";

        $stmtExiste =
            $conexion->prepare($sqlExiste);

        if (!$stmtExiste) {

            $errores[] =
                "Error al comprobar el nombre de usuario: "
                . $conexion->error;

        } else {

            $stmtExiste->bind_param(
                "si",
                $usuario,
                $id
            );

            $stmtExiste->execute();

            $resultadoExiste =
                $stmtExiste->get_result();

            if (
                $resultadoExiste->num_rows > 0
            ) {

                $errores[] =
                    "El nombre de usuario ya está registrado.";

            }

            $stmtExiste->close();

        }

    }

    // =================================================
    // ACTUALIZAR USUARIO
    // =================================================

    if (empty($errores)) {

        if ($password !== "") {

            // -----------------------------------------
            // ACTUALIZAR CON NUEVA CONTRASEÑA
            // -----------------------------------------

            $passwordHash =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

            $sqlActualizar = "
                UPDATE usuarios
                SET
                    nombre = ?,
                    usuario = ?,
                    password = ?,
                    rol = ?,
                    estado = ?
                WHERE id = ?
            ";

            $stmtActualizar =
                $conexion->prepare(
                    $sqlActualizar
                );

            if (!$stmtActualizar) {

                $errores[] =
                    "Error al preparar la actualización: "
                    . $conexion->error;

            } else {

                $stmtActualizar->bind_param(
                    "ssssii",
                    $nombre,
                    $usuario,
                    $passwordHash,
                    $rolSolicitado,
                    $estadoSolicitado,
                    $id
                );

                if (
                    !$stmtActualizar->execute()
                ) {

                    $errores[] =
                        "Error al actualizar el usuario: "
                        . $stmtActualizar->error;

                }

                $stmtActualizar->close();

            }

        } else {

            // -----------------------------------------
            // ACTUALIZAR SIN CAMBIAR CONTRASEÑA
            // -----------------------------------------

            $sqlActualizar = "
                UPDATE usuarios
                SET
                    nombre = ?,
                    usuario = ?,
                    rol = ?,
                    estado = ?
                WHERE id = ?
            ";

            $stmtActualizar =
                $conexion->prepare(
                    $sqlActualizar
                );

            if (!$stmtActualizar) {

                $errores[] =
                    "Error al preparar la actualización: "
                    . $conexion->error;

            } else {

                $stmtActualizar->bind_param(
                    "sssii",
                    $nombre,
                    $usuario,
                    $rolSolicitado,
                    $estadoSolicitado,
                    $id
                );

                if (
                    !$stmtActualizar->execute()
                ) {

                    $errores[] =
                        "Error al actualizar el usuario: "
                        . $stmtActualizar->error;

                }

                $stmtActualizar->close();

            }

        }

    }

    // =================================================
    // REDIRECCIÓN
    // =================================================

    if (empty($errores)) {

        // Si el usuario editó sus propios datos,
        // actualizamos la información de sesión.

        if ($esUsuarioActual) {

            $_SESSION["nombre"] =
                $nombre;

            $_SESSION["usuario"] =
                $usuario;

            $_SESSION["rol"] =
                $rolSolicitado;

        }

        header(
            "Location: listar.php?mensaje=usuario_editado"
        );

        exit;
    }
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

    <title>Editar usuario | Inventario</title>

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

<?php

// =====================================================
// NAVBAR COMPARTIDA
// =====================================================

include "../../includes/navbar.php";

?>

<!-- =====================================================
     CONTENIDO
===================================================== -->

<div class="container-fluid px-4 py-4">

    <!-- =================================================
         ENCABEZADO
    ================================================== -->

    <div class="mb-4">

        <h2 class="mb-1">

            <i class="bi bi-person-gear"></i>

            Editar usuario

        </h2>

        <p class="text-muted mb-0">

            Modifica los datos y permisos del usuario.

        </p>

    </div>

    <!-- =================================================
         ERRORES
    ================================================== -->

    <?php if (!empty($errores)): ?>

        <div class="alert alert-danger">

            <div class="d-flex">

                <div class="me-2">

                    <i
                        class="bi bi-exclamation-triangle-fill"
                    ></i>

                </div>

                <div>

                    <strong>

                        No se pudo actualizar el usuario.

                    </strong>

                    <ul class="mb-0 mt-2">

                        <?php foreach ($errores as $error): ?>

                            <li>

                                <?php
                                echo htmlspecialchars(
                                    $error
                                );
                                ?>

                            </li>

                        <?php endforeach; ?>

                    </ul>

                </div>

            </div>

        </div>

    <?php endif; ?>

    <!-- =================================================
         FORMULARIO
    ================================================== -->

    <div class="card">

        <div class="card-body">

            <form
                method="POST"
                action="editar.php?id=<?php echo (int) $id; ?>"
            >

                <div class="row g-4">

                    <!-- NOMBRE -->

                    <div class="col-md-6">

                        <label
                            for="nombre"
                            class="form-label"
                        >

                            Nombre completo

                            <span class="text-danger">*</span>

                        </label>

                        <div class="input-group">

                            <span class="input-group-text">

                                <i class="bi bi-person"></i>

                            </span>

                            <input
                                type="text"
                                id="nombre"
                                name="nombre"
                                class="form-control"
                                maxlength="100"
                                value="<?php echo htmlspecialchars($nombre); ?>"
                                required
                            >

                        </div>

                    </div>

                    <!-- USUARIO -->

                    <div class="col-md-6">

                        <label
                            for="usuario"
                            class="form-label"
                        >

                            Nombre de usuario

                            <span class="text-danger">*</span>

                        </label>

                        <div class="input-group">

                            <span class="input-group-text">

                                <i class="bi bi-person-badge"></i>

                            </span>

                            <input
                                type="text"
                                id="usuario"
                                name="usuario"
                                class="form-control"
                                maxlength="100"
                                value="<?php echo htmlspecialchars($usuario); ?>"
                                autocomplete="username"
                                required
                            >

                        </div>

                        <small class="text-muted">

                            Usa letras, números, punto, guion o guion bajo.

                        </small>

                    </div>

                    <!-- NUEVA CONTRASEÑA -->

                    <div class="col-md-6">

                        <label
                            for="password"
                            class="form-label"
                        >

                            Nueva contraseña

                        </label>

                        <div class="input-group">

                            <span class="input-group-text">

                                <i class="bi bi-lock"></i>

                            </span>

                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control"
                                minlength="8"
                                autocomplete="new-password"
                            >

                        </div>

                        <small class="text-muted">

                            Déjala vacía si no quieres cambiarla.
                            Mínimo 8 caracteres si deseas cambiarla.

                        </small>

                    </div>

                    <!-- CONFIRMAR CONTRASEÑA -->

                    <div class="col-md-6">

                        <label
                            for="password_confirmacion"
                            class="form-label"
                        >

                            Confirmar nueva contraseña

                        </label>

                        <div class="input-group">

                            <span class="input-group-text">

                                <i class="bi bi-lock-fill"></i>

                            </span>

                            <input
                                type="password"
                                id="password_confirmacion"
                                name="password_confirmacion"
                                class="form-control"
                                minlength="8"
                                autocomplete="new-password"
                            >

                        </div>

                    </div>

                    <!-- ROL -->

                    <div class="col-md-6">

                        <label
                            for="rol"
                            class="form-label"
                        >

                            Rol

                            <span class="text-danger">*</span>

                        </label>

                        <select
                            id="rol"
                            name="rol"
                            class="form-select"
                            required
                        >

                            <option
                                value="admin"
                                <?php
                                echo $rol === "admin"
                                    ? "selected"
                                    : "";
                                ?>
                            >

                                Administrador

                            </option>

                            <option
                                value="vendedor"
                                <?php
                                echo $rol === "vendedor"
                                    ? "selected"
                                    : "";
                                ?>
                            >

                                Vendedor

                            </option>

                        </select>

                    </div>

                    <!-- ESTADO -->

                    <div class="col-md-6">

                        <label class="form-label">

                            Estado

                        </label>

                        <div class="form-check form-switch mt-2">

                            <input
                                class="form-check-input"
                                type="checkbox"
                                role="switch"
                                id="estado"
                                name="estado"
                                <?php
                                echo $estado === 1
                                    ? "checked"
                                    : "";
                                ?>
                                <?php
                                echo $esUsuarioActual
                                    ? "disabled"
                                    : "";
                                ?>
                            >

                            <?php if ($esUsuarioActual): ?>

                                <!-- Mantener activo al usuario actual -->

                                <input
                                    type="hidden"
                                    name="estado"
                                    value="1"
                                >

                            <?php endif; ?>

                            <label
                                class="form-check-label"
                                for="estado"
                            >

                                Usuario activo

                            </label>

                        </div>

                        <?php if ($esUsuarioActual): ?>

                            <small class="text-muted">

                                Tu propia cuenta no puede
                                desactivarse mientras estás conectado.

                            </small>

                        <?php else: ?>

                            <small class="text-muted">

                                Los usuarios inactivos no podrán
                                iniciar sesión.

                            </small>

                        <?php endif; ?>

                    </div>

                </div>

                <!-- =================================================
                     INFORMACIÓN DE SEGURIDAD
                ================================================== -->

                <div class="alert alert-light border mt-4 mb-0">

                    <div class="d-flex">

                        <div class="me-2">

                            <i class="bi bi-shield-check"></i>

                        </div>

                        <div>

                            <strong>

                                Seguridad

                            </strong>

                            <p class="mb-0 text-muted">

                                La contraseña actual no se muestra.
                                Si no ingresas una nueva contraseña,
                                se conservará la actual.

                            </p>

                        </div>

                    </div>

                </div>

                <!-- =================================================
                     BOTONES
                ================================================== -->

                <hr class="my-4">

                <div class="d-flex justify-content-end gap-2">

                    <a
                        href="listar.php"
                        class="btn btn-outline-secondary"
                    >

                        <i class="bi bi-arrow-left"></i>

                        Cancelar

                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        <i class="bi bi-check-lg"></i>

                        Guardar cambios

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

</body>

</html>