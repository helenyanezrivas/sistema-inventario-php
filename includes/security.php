<?php

/*
|--------------------------------------------------------------------------
| Seguridad del sistema
|--------------------------------------------------------------------------
| Funciones relacionadas con protección CSRF y cabeceras de seguridad.
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| CABECERAS DE SEGURIDAD
|--------------------------------------------------------------------------
| Se envían antes de cualquier contenido HTML.
|--------------------------------------------------------------------------
*/

if (!headers_sent()) {

    header(
        "X-Content-Type-Options: nosniff"
    );

    header(
        "X-Frame-Options: SAMEORIGIN"
    );

    header(
        "Referrer-Policy: strict-origin-when-cross-origin"
    );

    header(
        "Permissions-Policy: geolocation=(), camera=(), microphone=()"
    );
}


/**
 * Generar o recuperar el token CSRF de la sesión.
 */
function csrf_token()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(
            random_bytes(32)
        );
    }

    return $_SESSION["csrf_token"];
}


/**
 * Generar el campo oculto que se incluirá en los formularios.
 */
function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(
            csrf_token(),
            ENT_QUOTES,
            "UTF-8"
        )
        . '">';
}


/**
 * Verificar el token CSRF recibido.
 */
function verificar_csrf()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $tokenSesion = $_SESSION["csrf_token"] ?? "";
    $tokenFormulario = $_POST["csrf_token"] ?? "";

    if (
        empty($tokenSesion) ||
        empty($tokenFormulario) ||
        !hash_equals(
            $tokenSesion,
            $tokenFormulario
        )
    ) {

        http_response_code(403);

        die(
            "Solicitud no válida. "
            . "El token de seguridad no coincide."
        );
    }
}

?>
