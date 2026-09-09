<?php

session_start();

require_once "config/database.php";
require_once "includes/security.php";

/*
|--------------------------------------------------------------------------
| Si ya existe una sesión iniciada
|--------------------------------------------------------------------------
*/

if (isset($_SESSION["usuario_id"])) {
    header("Location: index.php");
    exit;
}

$mensaje = "";
$tipoMensaje = "danger";
$mostrarFormulario = false;
$token = "";
$usuarioId = 0;

/*
|--------------------------------------------------------------------------
| OBTENER TOKEN
|--------------------------------------------------------------------------
*/

$token = trim(
    $_GET["token"] ?? $_POST["token"] ?? ""
);

/*
|--------------------------------------------------------------------------
| VALIDAR FORMATO DEL TOKEN
|--------------------------------------------------------------------------
*/

if (
    empty($token)
    || !preg_match('/^[a-f0-9]{64}$/', $token)
) {

    $mensaje =
        "El enlace de recuperación no es válido o ha expirado.";

} else {

    /*
    |--------------------------------------------------------------------------
    | GENERAR HASH DEL TOKEN RECIBIDO
    |--------------------------------------------------------------------------
    */

    $tokenHash = hash(
        "sha256",
        $token
    );

    /*
    |--------------------------------------------------------------------------
    | VERIFICAR TOKEN
    |--------------------------------------------------------------------------
    */

    $sql = "
        SELECT
            id,
            usuario,
            nombre,
            email,
            rol,
            estado
        FROM usuarios
        WHERE token_recuperacion = ?
          AND token_recuperacion_expira > NOW()
        LIMIT 1
    ";

    $stmt =
        $conexion->prepare($sql);

    if ($stmt) {

        $stmt->bind_param(
            "s",
            $tokenHash
        );

        if ($stmt->execute()) {

            $resultado =
                $stmt->get_result();

            if ($resultado->num_rows === 1) {

                $usuarioDB =
                    $resultado->fetch_assoc();

                /*
                |--------------------------------------------------------------------------
                | VERIFICAR CUENTA ACTIVA
                |--------------------------------------------------------------------------
                */

                if (
                    (int) $usuarioDB["estado"] === 1
                ) {

                    $mostrarFormulario = true;

                    $usuarioId =
                        (int) $usuarioDB["id"];

                } else {

                    $mensaje =
                        "La cuenta no se encuentra activa.";
                }

            } else {

                $mensaje =
                    "El enlace de recuperación no es válido o ha expirado.";
            }

        } else {

            $mensaje =
                "Ocurrió un error al verificar el enlace.";
        }

        $stmt->close();

    } else {

        $mensaje =
            "Ocurrió un error al procesar la recuperación.";
    }
}

/*
|--------------------------------------------------------------------------
| TOKEN CSRF
|--------------------------------------------------------------------------
*/

csrf_token();

/*
|--------------------------------------------------------------------------
| PROCESAR NUEVA CONTRASEÑA
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && $mostrarFormulario
) {

    verificar_csrf();

    $passwordNueva =
        $_POST["password_nueva"] ?? "";

    $passwordConfirmacion =
        $_POST["password_confirmacion"] ?? "";

    if (
        empty($passwordNueva)
        || empty($passwordConfirmacion)
    ) {

        $mensaje =
            "Debes ingresar y confirmar la nueva contraseña.";

    } elseif (strlen($passwordNueva) < 8) {

        $mensaje =
            "La contraseña debe tener al menos 8 caracteres.";

    } elseif ($passwordNueva !== $passwordConfirmacion) {

        $mensaje =
            "Las contraseñas no coinciden.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | VOLVER A VERIFICAR EL TOKEN
        |--------------------------------------------------------------------------
        */

        $tokenHash =
            hash(
                "sha256",
                $token
            );

        $sqlVerificar = "
            SELECT
                id,
                rol,
                estado
            FROM usuarios
            WHERE token_recuperacion = ?
              AND token_recuperacion_expira > NOW()
            LIMIT 1
        ";

        $stmtVerificar =
            $conexion->prepare(
                $sqlVerificar
            );

        if ($stmtVerificar) {

            $stmtVerificar->bind_param(
                "s",
                $tokenHash
            );

            if ($stmtVerificar->execute()) {

                $resultadoVerificar =
                    $stmtVerificar->get_result();

                if (
                    $resultadoVerificar->num_rows === 1
                ) {

                    $usuarioDB =
                        $resultadoVerificar->fetch_assoc();

                    /*
                    |--------------------------------------------------------------------------
                    | VERIFICAR CUENTA ACTIVA
                    |--------------------------------------------------------------------------
                    */

                    if (
                        (int) $usuarioDB["estado"] === 1
                    ) {

                        /*
                        |--------------------------------------------------------------------------
                        | GENERAR HASH DE LA NUEVA CONTRASEÑA
                        |--------------------------------------------------------------------------
                        */

                        $passwordHash =
                            password_hash(
                                $passwordNueva,
                                PASSWORD_DEFAULT
                            );

                        /*
                        |--------------------------------------------------------------------------
                        | ACTUALIZAR CONTRASEÑA
                        | E INVALIDAR TOKEN
                        |--------------------------------------------------------------------------
                        */

                        $sqlActualizar = "
                            UPDATE usuarios
                            SET
                                password = ?,
                                token_recuperacion = NULL,
                                token_recuperacion_expira = NULL
                            WHERE id = ?
                        ";

                        $stmtActualizar =
                            $conexion->prepare(
                                $sqlActualizar
                            );

                        if ($stmtActualizar) {

                            $stmtActualizar->bind_param(
                                "si",
                                $passwordHash,
                                $usuarioDB["id"]
                            );

                            if ($stmtActualizar->execute()) {

                                /*
                                |--------------------------------------------------------------------------
                                | LIMPIAR INTENTOS
                                |--------------------------------------------------------------------------
                                */

                                unset(
                                    $_SESSION["login_intentos"],
                                    $_SESSION["login_bloqueado_hasta"],
                                    $_SESSION["recuperacion_intentos"],
                                    $_SESSION["recuperacion_bloqueado_hasta"]
                                );

                                /*
                                |--------------------------------------------------------------------------
                                | GENERAR NUEVO TOKEN CSRF
                                |--------------------------------------------------------------------------
                                */

                                $_SESSION["csrf_token"] =
                                    bin2hex(
                                        random_bytes(32)
                                    );

                                /*
                                |--------------------------------------------------------------------------
                                | VOLVER AL LOGIN
                                |--------------------------------------------------------------------------
                                */

                                header(
                                    "Location: login.php?recuperacion=ok"
                                );

                                exit;

                            } else {

                                $mensaje =
                                    "No fue posible cambiar la contraseña.";
                            }

                            $stmtActualizar->close();

                        } else {

                            $mensaje =
                                "Ocurrió un error al actualizar la contraseña.";
                        }

                    } else {

                        $mensaje =
                            "La cuenta no se encuentra activa.";

                        $mostrarFormulario = false;
                    }

                } else {

                    $mensaje =
                        "El enlace de recuperación no es válido o ha expirado.";

                    $mostrarFormulario = false;
                }

            } else {

                $mensaje =
                    "Ocurrió un error al verificar el enlace.";
            }

            $stmtVerificar->close();

        } else {

            $mensaje =
                "Ocurrió un error al procesar la recuperación.";
        }
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

    <title>Restablecer contraseña | Inventario</title>

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

    <!-- Estilos propios -->

    <link
        rel="stylesheet"
        href="assets/css/recuperar.css"
    >

</head>

<body>

    <div class="login-container">

        <div class="login-card">

            <div class="logo">
                <i class="bi bi-shield-lock-fill"></i>
            </div>

            <h2 class="text-center mb-2">
                Nueva contraseña
            </h2>

            <p class="text-center text-muted mb-4">
                Crea una nueva contraseña para tu cuenta.
            </p>

            <?php if (!empty($mensaje)): ?>

                <div
                    class="alert alert-<?php echo htmlspecialchars(
                        $tipoMensaje,
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>"
                    role="alert"
                >
                    <?php
                    echo htmlspecialchars(
                        $mensaje,
                        ENT_QUOTES,
                        "UTF-8"
                    );
                    ?>
                </div>

            <?php endif; ?>

            <?php if ($mostrarFormulario): ?>

                <form
                    method="POST"
                    action=""
                >

                    <?php echo csrf_field(); ?>

                    <input
                        type="hidden"
                        name="token"
                        value="<?php echo htmlspecialchars(
                            $token,
                            ENT_QUOTES,
                            "UTF-8"
                        ); ?>"
                    >

                    <div class="mb-3">

                        <label
                            for="password_nueva"
                            class="form-label"
                        >
                            Nueva contraseña
                        </label>

                        <input
                            type="password"
                            class="form-control"
                            id="password_nueva"
                            name="password_nueva"
                            placeholder="Ingresa una nueva contraseña"
                            minlength="8"
                            maxlength="255"
                            autocomplete="new-password"
                            required
                        >

                        <div class="form-text">
                            Debe tener al menos 8 caracteres.
                        </div>

                    </div>

                    <div class="mb-4">

                        <label
                            for="password_confirmacion"
                            class="form-label"
                        >
                            Confirmar contraseña
                        </label>

                        <input
                            type="password"
                            class="form-control"
                            id="password_confirmacion"
                            name="password_confirmacion"
                            placeholder="Repite la nueva contraseña"
                            minlength="8"
                            maxlength="255"
                            autocomplete="new-password"
                            required
                        >

                    </div>

                    <button
                        type="submit"
                        class="btn btn-primary btn-login w-100"
                    >
                        <i class="bi bi-check-circle"></i>
                        Cambiar contraseña
                    </button>

                </form>

            <?php endif; ?>

            <div class="text-center mt-4">

                <a
                    href="login.php"
                    class="volver"
                >
                    <i class="bi bi-arrow-left"></i>
                    Volver al inicio de sesión
                </a>

            </div>

        </div>

    </div>

</body>

</html>