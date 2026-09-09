<?php

session_start();

require_once "config/database.php";
require_once "includes/security.php";
require_once "config/correo.php";
require_once "vendor/autoload.php";

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

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
| CONTROL DE INTENTOS DE RECUPERACIÓN
|--------------------------------------------------------------------------
*/

$maxIntentosRecuperacion = 5;
$segundosBloqueoRecuperacion = 60;

$intentosRecuperacion =
    (int) ($_SESSION["recuperacion_intentos"] ?? 0);

$bloqueadoRecuperacionHasta =
    (int) ($_SESSION["recuperacion_bloqueado_hasta"] ?? 0);

$recuperacionBloqueada =
    $bloqueadoRecuperacionHasta > time();

if (
    !$recuperacionBloqueada
    && $bloqueadoRecuperacionHasta > 0
) {

    unset($_SESSION["recuperacion_bloqueado_hasta"]);

    $bloqueadoRecuperacionHasta = 0;
}

/*
|--------------------------------------------------------------------------
| TOKEN CSRF
|--------------------------------------------------------------------------
*/

csrf_token();

/*
|--------------------------------------------------------------------------
| PROCESAR RECUPERACIÓN
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    verificar_csrf();

    if ($recuperacionBloqueada) {

        $segundosRestantes =
            max(
                1,
                $bloqueadoRecuperacionHasta - time()
            );

        $mensaje =
            "Demasiados intentos. "
            . "Espera "
            . $segundosRestantes
            . " segundos e inténtalo nuevamente.";

    } else {

        $email =
            trim($_POST["email"] ?? "");

        if (empty($email)) {

            $mensaje =
                "Debes ingresar tu correo electrónico.";

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
            | RESPUESTA GENERAL
            |--------------------------------------------------------------------------
            | No revelamos si el correo existe.
            |--------------------------------------------------------------------------
            */

            $mensaje =
                "Si la cuenta puede recuperar su contraseña, "
                . "recibirás un correo con las instrucciones.";

            $tipoMensaje = "success";

            /*
            |--------------------------------------------------------------------------
            | BUSCAR CUENTA ACTIVA CON ESE CORREO
            |--------------------------------------------------------------------------
            */

            $sql = "
                SELECT
                    id,
                    nombre,
                    email,
                    estado
                FROM usuarios
                WHERE LOWER(email) = LOWER(?)
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

                    if ($resultado->num_rows === 1) {

                        $usuarioDB =
                            $resultado->fetch_assoc();

                        /*
                        |--------------------------------------------------------------------------
                        | GENERAR TOKEN ALEATORIO
                        |--------------------------------------------------------------------------
                        */

                        $token =
                            bin2hex(
                                random_bytes(32)
                            );

                        /*
                        |--------------------------------------------------------------------------
                        | GUARDAR HASH DEL TOKEN
                        |--------------------------------------------------------------------------
                        */

                        $tokenHash =
                            hash(
                                "sha256",
                                $token
                            );

                        /*
                        |--------------------------------------------------------------------------
                        | EXPIRACIÓN: 15 MINUTOS
                        |--------------------------------------------------------------------------
                        */

                        $expira =
                            date(
                                "Y-m-d H:i:s",
                                time() + (15 * 60)
                            );

                        $sqlToken = "
                            UPDATE usuarios
                            SET
                                token_recuperacion = ?,
                                token_recuperacion_expira = ?
                            WHERE id = ?
                        ";

                        $stmtToken =
                            $conexion->prepare($sqlToken);

                        if ($stmtToken) {

                            $stmtToken->bind_param(
                                "ssi",
                                $tokenHash,
                                $expira,
                                $usuarioDB["id"]
                            );

                            if ($stmtToken->execute()) {

                                /*
                                |--------------------------------------------------------------------------
                                | ENLACE
                                |--------------------------------------------------------------------------
                                */

                                $enlace =
                                    "http://localhost/inventario/"
                                    . "restablecer.php?token="
                                    . urlencode($token);

                                /*
                                |--------------------------------------------------------------------------
                                | CONFIGURAR PHPMailer
                                |--------------------------------------------------------------------------
                                */

                                $mail =
                                    new PHPMailer(true);

                                try {

                                    $mail->isSMTP();

                                    $mail->Host =
                                        $smtpHost;

                                    $mail->SMTPAuth =
                                        true;

                                    $mail->Username =
                                        $smtpUsername;

                                    $mail->Password =
                                        $smtpPassword;

                                    $mail->SMTPSecure =
                                        PHPMailer::ENCRYPTION_STARTTLS;

                                    $mail->Port =
                                        $smtpPort;

                                    $mail->CharSet =
                                        "UTF-8";

                                    $mail->setFrom(
                                        $smtpFromEmail,
                                        $smtpFromName
                                    );

                                    $mail->addAddress(
                                        $usuarioDB["email"],
                                        $usuarioDB["nombre"]
                                    );

                                    $mail->isHTML(true);

                                    $mail->Subject =
                                        "Recuperación de contraseña - Sistema de Inventario";

                                    $nombre =
                                        htmlspecialchars(
                                            $usuarioDB["nombre"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );

                                    $enlaceHtml =
                                        htmlspecialchars(
                                            $enlace,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );

                                    $mail->Body = "
                                        <div style='
                                            font-family: Arial, sans-serif;
                                            max-width: 600px;
                                            margin: auto;
                                        '>

                                            <h2 style='color: #6f42c1;'>
                                                Recuperación de contraseña
                                            </h2>

                                            <p>
                                                Hola,
                                                <strong>
                                                    {$nombre}
                                                </strong>.
                                            </p>

                                            <p>
                                                Recibimos una solicitud para cambiar
                                                la contraseña de tu cuenta del
                                                Sistema de Inventario.
                                            </p>

                                            <p>
                                                Haz clic en el siguiente botón para
                                                crear una nueva contraseña:
                                            </p>

                                            <p
                                                style='
                                                    text-align: center;
                                                    margin: 30px 0;
                                                '
                                            >

                                                <a
                                                    href='{$enlaceHtml}'
                                                    style='
                                                        background: #6f42c1;
                                                        color: white;
                                                        padding: 12px 24px;
                                                        text-decoration: none;
                                                        border-radius: 6px;
                                                        display: inline-block;
                                                    '
                                                >
                                                    Restablecer contraseña
                                                </a>

                                            </p>

                                            <p>
                                                Este enlace será válido durante
                                                <strong>15 minutos</strong>.
                                            </p>

                                            <p>
                                                Si tú no solicitaste este cambio,
                                                puedes ignorar este correo.
                                            </p>

                                            <hr>

                                            <p
                                                style='
                                                    font-size: 12px;
                                                    color: #777;
                                                '
                                            >
                                                Sistema de Inventario
                                            </p>

                                        </div>
                                    ";

                                    $mail->AltBody =
                                        "Recuperación de contraseña\n\n"
                                        . "Abre el siguiente enlace para "
                                        . "crear una nueva contraseña:\n\n"
                                        . $enlace
                                        . "\n\n"
                                        . "El enlace será válido durante "
                                        . "15 minutos.";

                                    $mail->send();

                                    unset(
                                        $_SESSION["recuperacion_intentos"],
                                        $_SESSION["recuperacion_bloqueado_hasta"]
                                    );

                                } catch (Exception $e) {

                                    /*
                                    |--------------------------------------------------------------------------
                                    | INVALIDAR TOKEN SI FALLA EL CORREO
                                    |--------------------------------------------------------------------------
                                    */

                                    $sqlLimpiar = "
                                        UPDATE usuarios
                                        SET
                                            token_recuperacion = NULL,
                                            token_recuperacion_expira = NULL
                                        WHERE id = ?
                                    ";

                                    $stmtLimpiar =
                                        $conexion->prepare(
                                            $sqlLimpiar
                                        );

                                    if ($stmtLimpiar) {

                                        $stmtLimpiar->bind_param(
                                            "i",
                                            $usuarioDB["id"]
                                        );

                                        $stmtLimpiar->execute();

                                        $stmtLimpiar->close();
                                    }

                                    $mensaje =
                                        "No fue posible enviar el correo "
                                        . "de recuperación. Verifica la "
                                        . "configuración del correo.";

                                    $tipoMensaje = "danger";
                                }

                            } else {

                                $mensaje =
                                    "No fue posible iniciar la recuperación.";

                                $tipoMensaje = "danger";
                            }

                            $stmtToken->close();

                        } else {

                            $mensaje =
                                "No fue posible iniciar la recuperación.";

                            $tipoMensaje = "danger";
                        }
                    }

                } else {

                    $mensaje =
                        "Ocurrió un error al procesar la solicitud.";

                    $tipoMensaje = "danger";
                }

                $stmt->close();

            } else {

                $mensaje =
                    "Ocurrió un error al procesar la solicitud.";

                $tipoMensaje = "danger";
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

    <title>Recuperar contraseña | Inventario</title>

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
        href="assets/css/recuperar.css"
    >

</head>

<body>

    <div class="login-container">

        <div class="login-card">

            <div class="logo">
                <i class="bi bi-envelope-fill"></i>
            </div>

            <h2 class="text-center mb-2">
                Recuperar contraseña
            </h2>

            <p class="text-center text-muted mb-4">
                Ingresa tu correo para recibir un enlace de recuperación.
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

                <div class="mb-4">

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
                        echo $recuperacionBloqueada
                            ? "disabled"
                            : "";
                        ?>
                    >

                </div>

                <button
                    type="submit"
                    class="btn btn-primary btn-login w-100"
                    <?php
                    echo $recuperacionBloqueada
                        ? "disabled"
                        : "";
                    ?>
                >
                    <?php
                    echo $recuperacionBloqueada
                        ? "Recuperación bloqueada temporalmente"
                        : "Enviar enlace";
                    ?>
                </button>

            </form>

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