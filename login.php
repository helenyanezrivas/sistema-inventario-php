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

/*
|--------------------------------------------------------------------------
| MENSAJE DE RECUPERACIÓN
|--------------------------------------------------------------------------
*/

if (
    isset($_GET["recuperacion"])
    && $_GET["recuperacion"] === "ok"
) {

    $mensaje =
        "Tu contraseña fue cambiada correctamente. "
        . "Ya puedes iniciar sesión.";

    $tipoMensaje = "success";
}

/*
|--------------------------------------------------------------------------
| CONTROL DE INTENTOS DE LOGIN
|--------------------------------------------------------------------------
| - Máximo 5 intentos fallidos.
| - Bloqueo temporal de 60 segundos.
|--------------------------------------------------------------------------
*/

$maxIntentosLogin = 5;
$segundosBloqueoLogin = 60;

$intentosLogin =
    (int) ($_SESSION["login_intentos"] ?? 0);

$bloqueadoHasta =
    (int) ($_SESSION["login_bloqueado_hasta"] ?? 0);

$loginBloqueado =
    $bloqueadoHasta > time();

if (
    !$loginBloqueado
    && $bloqueadoHasta > 0
) {

    unset($_SESSION["login_bloqueado_hasta"]);

    $bloqueadoHasta = 0;
}

/*
|--------------------------------------------------------------------------
| TOKEN CSRF
|--------------------------------------------------------------------------
*/

csrf_token();

/*
|--------------------------------------------------------------------------
| PROCESAR LOGIN
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    verificar_csrf();

    /*
    |--------------------------------------------------------------------------
    | COMPROBAR BLOQUEO
    |--------------------------------------------------------------------------
    */

    if ($loginBloqueado) {

        $segundosRestantes =
            max(
                1,
                $bloqueadoHasta - time()
            );

        $mensaje =
            "Demasiados intentos fallidos. "
            . "Espera "
            . $segundosRestantes
            . " segundos e inténtalo nuevamente.";

    } else {

        $email =
            trim($_POST["email"] ?? "");

        $password =
            $_POST["password"] ?? "";

        if (
            empty($email)
            || empty($password)
        ) {

            $mensaje =
                "Debes ingresar correo electrónico y contraseña.";

        } elseif (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            $mensaje =
                "Debes ingresar un correo electrónico válido.";

        } else {

            /*
            |--------------------------------------------------------------------------
            | BUSCAR USUARIO POR CORREO
            |--------------------------------------------------------------------------
            */

            $sql = "
                SELECT
                    id,
                    nombre,
                    usuario,
                    email,
                    password,
                    rol
                FROM usuarios
                WHERE email = ?
                  AND estado = 1
                LIMIT 1
            ";

            $stmt =
                $conexion->prepare($sql);

            if ($stmt) {

                $stmt->bind_param(
                    "s",
                    $email
                );

                if ($stmt->execute()) {

                    $resultado =
                        $stmt->get_result();

                    if (
                        $resultado->num_rows === 1
                    ) {

                        $usuarioDB =
                            $resultado->fetch_assoc();

                        /*
                        |--------------------------------------------------------------------------
                        | VERIFICAR CONTRASEÑA
                        |--------------------------------------------------------------------------
                        */

                        if (
                            password_verify(
                                $password,
                                $usuarioDB["password"]
                            )
                        ) {

                            /*
                            |--------------------------------------------------------------------------
                            | LOGIN CORRECTO
                            |--------------------------------------------------------------------------
                            */

                            session_regenerate_id(true);

                            $_SESSION["usuario_id"] =
                                $usuarioDB["id"];

                            $_SESSION["nombre"] =
                                $usuarioDB["nombre"];

                            $_SESSION["usuario"] =
                                $usuarioDB["usuario"];

                            $_SESSION["email"] =
                                $usuarioDB["email"];

                            $_SESSION["rol"] =
                                $usuarioDB["rol"];

                            /*
                            |--------------------------------------------------------------------------
                            | REINICIAR INTENTOS
                            |--------------------------------------------------------------------------
                            */

                            unset(
                                $_SESSION["login_intentos"],
                                $_SESSION["login_bloqueado_hasta"]
                            );

                            /*
                            |--------------------------------------------------------------------------
                            | REGENERAR TOKEN CSRF
                            |--------------------------------------------------------------------------
                            */

                            $_SESSION["csrf_token"] =
                                bin2hex(
                                    random_bytes(32)
                                );

                            header(
                                "Location: index.php"
                            );

                            exit;

                        } else {

                            $intentosLogin++;

                            $_SESSION["login_intentos"] =
                                $intentosLogin;

                            if (
                                $intentosLogin >=
                                $maxIntentosLogin
                            ) {

                                $bloqueadoHasta =
                                    time()
                                    + $segundosBloqueoLogin;

                                $_SESSION["login_bloqueado_hasta"] =
                                    $bloqueadoHasta;

                                $loginBloqueado = true;

                                $mensaje =
                                    "Demasiados intentos fallidos. "
                                    . "Espera "
                                    . $segundosBloqueoLogin
                                    . " segundos e inténtalo nuevamente.";

                            } else {

                                $mensaje =
                                    "Correo o contraseña incorrectos.";
                            }
                        }

                    } else {

                        $intentosLogin++;

                        $_SESSION["login_intentos"] =
                            $intentosLogin;

                        if (
                            $intentosLogin >=
                            $maxIntentosLogin
                        ) {

                            $bloqueadoHasta =
                                time()
                                + $segundosBloqueoLogin;

                            $_SESSION["login_bloqueado_hasta"] =
                                $bloqueadoHasta;

                            $loginBloqueado = true;

                            $mensaje =
                                "Demasiados intentos fallidos. "
                                . "Espera "
                                . $segundosBloqueoLogin
                                . " segundos e inténtalo nuevamente.";

                        } else {

                            $mensaje =
                                "Correo o contraseña incorrectos.";
                        }
                    }

                } else {

                    $mensaje =
                        "Ocurrió un error al iniciar sesión.";
                }

                $stmt->close();

            } else {

                $mensaje =
                    "Ocurrió un error al iniciar sesión.";
            }
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

    <title>Iniciar sesión | Inventario</title>

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
        href="assets/css/login.css"
    >

</head>

<body>

    <div class="login-container">

        <div class="login-card">

            <div class="logo">
                I
            </div>

            <h2 class="text-center mb-2">
                Sistema de Inventario
            </h2>

            <p class="text-center text-muted mb-4">
                Inicia sesión para continuar
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

            <form
                method="POST"
                action=""
            >

                <?php echo csrf_field(); ?>

                <div class="mb-3">

                    <label
                        for="email"
                        class="form-label"
                    >
                        Correo electrónico
                    </label>

                    <input
                        type="email"
                        class="form-control"
                        id="email"
                        name="email"
                        placeholder="Ingresa tu correo"
                        maxlength="150"
                        autocomplete="email"
                        required
                        <?php
                        echo $loginBloqueado
                            ? "disabled"
                            : "";
                        ?>
                    >

                </div>

                <div class="mb-4">

                    <label
                        for="password"
                        class="form-label"
                    >
                        Contraseña
                    </label>

                    <input
                        type="password"
                        class="form-control"
                        id="password"
                        name="password"
                        placeholder="Ingresa tu contraseña"
                        autocomplete="current-password"
                        required
                        <?php
                        echo $loginBloqueado
                            ? "disabled"
                            : "";
                        ?>
                    >

                </div>

                <button
                    type="submit"
                    class="btn btn-primary btn-login w-100"
                    <?php
                    echo $loginBloqueado
                        ? "disabled"
                        : "";
                    ?>
                >
                    <?php
                    echo $loginBloqueado
                        ? "Acceso bloqueado temporalmente"
                        : "Iniciar sesión";
                    ?>
                </button>

            </form>

            <div class="text-center mt-3">

                <a
                    href="recuperar.php"
                    class="forgot-password"
                >
                    <i class="bi bi-key"></i>
                    ¿Olvidaste tu contraseña?
                </a>

            </div>

        </div>

    </div>

</body>

</html>