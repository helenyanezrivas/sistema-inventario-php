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

/*
|--------------------------------------------------------------------------
| CONTROL DE INTENTOS DE LOGIN
|--------------------------------------------------------------------------
| Primera capa de protección contra intentos repetidos:
| - Máximo 5 intentos fallidos.
| - Bloqueo temporal de 60 segundos.
|--------------------------------------------------------------------------
*/

$maxIntentosLogin = 5;
$segundosBloqueoLogin = 60;

$intentosLogin = (int) ($_SESSION["login_intentos"] ?? 0);
$bloqueadoHasta = (int) ($_SESSION["login_bloqueado_hasta"] ?? 0);

$loginBloqueado = $bloqueadoHasta > time();

if (!$loginBloqueado && $bloqueadoHasta > 0) {
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

        $usuario = trim(
            $_POST["usuario"] ?? ""
        );

        $password =
            $_POST["password"] ?? "";

        if (
            empty($usuario)
            || empty($password)
        ) {

            $mensaje =
                "Debes ingresar usuario y contraseña.";

        } else {

            $sql = "
                SELECT
                    id,
                    nombre,
                    usuario,
                    password,
                    rol
                FROM usuarios
                WHERE usuario = ?
                  AND estado = 1
                LIMIT 1
            ";

            $stmt =
                $conexion->prepare($sql);

            if ($stmt) {

                $stmt->bind_param(
                    "s",
                    $usuario
                );

                if ($stmt->execute()) {

                    $resultado =
                        $stmt->get_result();

                    if (
                        $resultado->num_rows === 1
                    ) {

                        $usuarioDB =
                            $resultado->fetch_assoc();

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
                                    "Usuario o contraseña incorrectos.";

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
                                "Usuario o contraseña incorrectos.";

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

    <style>

        body {
            min-height: 100vh;
            background: #f4f4f8;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
        }

        .login-card {
            background: white;
            border: none;
            border-radius: 15px;
            padding: 35px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.10);
        }

        .logo {
            width: 70px;
            height: 70px;
            background: #6f42c1;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 30px;
            font-weight: bold;
        }

        .btn-login {
            background: #6f42c1;
            border: none;
            padding: 12px;
            font-weight: 600;
        }

        .btn-login:hover {
            background: #59339d;
        }

    </style>

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
                    class="alert alert-danger"
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
                        for="usuario"
                        class="form-label"
                    >
                        Usuario
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="usuario"
                        name="usuario"
                        placeholder="Ingresa tu usuario"
                        maxlength="100"
                        autocomplete="username"
                        required
                        <?php echo $loginBloqueado ? "disabled" : ""; ?>
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
                        <?php echo $loginBloqueado ? "disabled" : ""; ?>
                    >

                </div>

                <button
                    type="submit"
                    class="btn btn-primary btn-login w-100"
                    <?php echo $loginBloqueado ? "disabled" : ""; ?>
                >
                    <?php
                    echo $loginBloqueado
                        ? "Acceso bloqueado temporalmente"
                        : "Iniciar sesión";
                    ?>
                </button>

            </form>

        </div>

    </div>

</body>

</html>