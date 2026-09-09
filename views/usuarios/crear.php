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

                        No tienes permisos para crear usuarios.

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
// SEGURIDAD CSRF
// =====================================================

csrf_token();


// =====================================================
// VARIABLES
// =====================================================

$nombre = "";

$usuario = "";

$email = "";

$rol = "vendedor";

$estado = 1;

$errores = [];


// =====================================================
// PROCESAR FORMULARIO
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    verificar_csrf();

    $nombre =
        trim($_POST["nombre"] ?? "");

    $usuario =
        trim($_POST["usuario"] ?? "");

    $email =
        trim($_POST["email"] ?? "");

    $password =
        $_POST["password"] ?? "";

    $passwordConfirmacion =
        $_POST["password_confirmacion"] ?? "";

    $rol =
        $_POST["rol"] ?? "vendedor";

    $estado =
        isset($_POST["estado"]) ? 1 : 0;


    // =================================================
    // VALIDAR NOMBRE
    // =================================================

    if ($nombre === "") {

        $errores[] =
            "El nombre completo es obligatorio.";

    } elseif (mb_strlen($nombre) < 3) {

        $errores[] =
            "El nombre debe tener al menos 3 caracteres.";

    } elseif (mb_strlen($nombre) > 100) {

        $errores[] =
            "El nombre no puede superar los 100 caracteres.";

    }


    // =================================================
    // VALIDAR USUARIO
    // =================================================

    if ($usuario === "") {

        $errores[] =
            "El nombre de usuario es obligatorio.";

    } elseif (mb_strlen($usuario) < 3) {

        $errores[] =
            "El usuario debe tener al menos 3 caracteres.";

    } elseif (mb_strlen($usuario) > 100) {

        $errores[] =
            "El usuario no puede superar los 100 caracteres.";

    } elseif (
        !preg_match(
            '/^[a-zA-Z0-9._-]+$/',
            $usuario
        )
    ) {

        $errores[] =
            "El usuario solo puede contener letras, números, punto, guion y guion bajo.";

    }


    // =================================================
    // VALIDAR CORREO
    // =================================================

    if ($email === "") {

        $errores[] =
            "El correo electrónico es obligatorio.";

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $errores[] =
            "Debes ingresar un correo electrónico válido.";

    } elseif (mb_strlen($email) > 150) {

        $errores[] =
            "El correo electrónico no puede superar los 150 caracteres.";

    }


    // =================================================
    // VALIDAR CONTRASEÑA
    // =================================================

    if ($password === "") {

        $errores[] =
            "La contraseña es obligatoria.";

    } elseif (strlen($password) < 8) {

        $errores[] =
            "La contraseña debe tener al menos 8 caracteres.";

    }


    // =================================================
    // CONFIRMAR CONTRASEÑA
    // =================================================

    if ($password !== $passwordConfirmacion) {

        $errores[] =
            "Las contraseñas no coinciden.";

    }


    // =================================================
    // VALIDAR ROL
    // =================================================

    if (
        !in_array(
            $rol,
            ["admin", "vendedor"],
            true
        )
    ) {

        $errores[] =
            "El rol seleccionado no es válido.";

    }


    // =================================================
    // COMPROBAR DATOS EXISTENTES
    // =================================================

    if (empty($errores)) {

        $sqlExiste = "
            SELECT
                id,
                usuario,
                email
            FROM usuarios
            WHERE LOWER(usuario) = LOWER(?)
               OR LOWER(email) = LOWER(?)
            LIMIT 1
        ";

        $stmtExiste =
            $conexion->prepare($sqlExiste);


        if (!$stmtExiste) {

            $errores[] =
                "Error al comprobar los datos: "
                . $conexion->error;

        } else {

            $stmtExiste->bind_param(
                "ss",
                $usuario,
                $email
            );

            $stmtExiste->execute();

            $resultadoExiste =
                $stmtExiste->get_result();


            if ($resultadoExiste->num_rows > 0) {

                $usuarioExistente = false;
                $emailExistente = false;

                while (
                    $filaExistente =
                    $resultadoExiste->fetch_assoc()
                ) {

                    if (
                        strcasecmp(
                            $filaExistente["usuario"],
                            $usuario
                        ) === 0
                    ) {

                        $usuarioExistente = true;
                    }

                    if (
                        strcasecmp(
                            $filaExistente["email"],
                            $email
                        ) === 0
                    ) {

                        $emailExistente = true;
                    }
                }

                if ($usuarioExistente) {

                    $errores[] =
                        "El nombre de usuario ya está registrado.";
                }

                if ($emailExistente) {

                    $errores[] =
                        "El correo electrónico ya está registrado.";
                }
            }


            $stmtExiste->close();

        }

    }


    // =================================================
    // CREAR USUARIO
    // =================================================

    if (empty($errores)) {

        $passwordHash =
            password_hash(
                $password,
                PASSWORD_DEFAULT
            );


        $sqlCrear = "
            INSERT INTO usuarios (
                nombre,
                usuario,
                email,
                password,
                rol,
                estado
            )
            VALUES (?, ?, ?, ?, ?, ?)
        ";


        $stmtCrear =
            $conexion->prepare($sqlCrear);


        if (!$stmtCrear) {

            $errores[] =
                "Error al preparar la creación del usuario: "
                . $conexion->error;

        } else {

            $stmtCrear->bind_param(
                "sssssi",
                $nombre,
                $usuario,
                $email,
                $passwordHash,
                $rol,
                $estado
            );


            if ($stmtCrear->execute()) {

                $stmtCrear->close();

                header(
                    "Location: listar.php?mensaje=usuario_creado"
                );

                exit;

            } else {

                $errores[] =
                    "Error al crear el usuario: "
                    . $stmtCrear->error;

                $stmtCrear->close();

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

    <title>Nuevo usuario | Inventario</title>


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


    <!-- CSS DEL SISTEMA -->

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


<div class="container-fluid px-4 py-4">


    <!-- =================================================
         ENCABEZADO
    ================================================== -->

    <div class="mb-4">

        <h2 class="mb-1">

            <i class="bi bi-person-plus"></i>

            Nuevo usuario

        </h2>

        <p class="text-muted mb-0">

            Registra un nuevo usuario para acceder al sistema.

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

                        No se pudo crear el usuario.

                    </strong>


                    <ul class="mb-0 mt-2">

                        <?php foreach ($errores as $error): ?>

                            <li>

                                <?php
                                echo htmlspecialchars(
                                    $error,
                                    ENT_QUOTES,
                                    "UTF-8"
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
                action="crear.php"
                id="formCrearUsuario"
            >

                <?php echo csrf_field(); ?>

                <div class="row g-4">


                    <!-- =================================================
                         NOMBRE
                    ================================================== -->

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
                                value="<?php echo htmlspecialchars(
                                    $nombre,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>"
                                placeholder="Ej: Juan Pérez"
                                autocomplete="name"
                                required
                            >

                        </div>

                    </div>



                    <!-- =================================================
                         USUARIO
                    ================================================== -->

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
                                value="<?php echo htmlspecialchars(
                                    $usuario,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>"
                                placeholder="Ej: juan"
                                autocomplete="username"
                                required
                            >

                        </div>


                        <small class="text-muted">

                            Usa letras, números, punto, guion o
                            guion bajo.

                        </small>

                    </div>



                    <!-- =================================================
                         CORREO ELECTRÓNICO
                    ================================================== -->

                    <div class="col-md-6">

                        <label
                            for="email"
                            class="form-label"
                        >

                            Correo electrónico

                            <span class="text-danger">*</span>

                        </label>


                        <div class="input-group">

                            <span class="input-group-text">

                                <i class="bi bi-envelope"></i>

                            </span>


                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control"
                                maxlength="150"
                                value="<?php echo htmlspecialchars(
                                    $email,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>"
                                placeholder="Ej: juan@gmail.com"
                                autocomplete="email"
                                required
                            >

                        </div>


                        <small class="text-muted">

                            Se utilizará para iniciar sesión y
                            recuperar la contraseña.

                        </small>

                    </div>



                    <!-- =================================================
                         CONTRASEÑA
                    ================================================== -->

                    <div class="col-md-6">

                        <label
                            for="password"
                            class="form-label"
                        >

                            Contraseña

                            <span class="text-danger">*</span>

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
                                required
                            >


                            <button
                                type="button"
                                class="btn btn-outline-secondary"
                                id="mostrarPassword"
                                title="Mostrar contraseña"
                            >

                                <i
                                    class="bi bi-eye"
                                    id="iconoPassword"
                                ></i>

                            </button>

                        </div>


                        <small
                            class="text-muted"
                            id="ayudaPassword"
                        >

                            Mínimo 8 caracteres.

                        </small>

                    </div>



                    <!-- =================================================
                         CONFIRMAR CONTRASEÑA
                    ================================================== -->

                    <div class="col-md-6">

                        <label
                            for="password_confirmacion"
                            class="form-label"
                        >

                            Confirmar contraseña

                            <span class="text-danger">*</span>

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
                                required
                            >


                            <button
                                type="button"
                                class="btn btn-outline-secondary"
                                id="mostrarPasswordConfirmacion"
                                title="Mostrar contraseña"
                            >

                                <i
                                    class="bi bi-eye"
                                    id="iconoPasswordConfirmacion"
                                ></i>

                            </button>

                        </div>


                        <small
                            class="text-muted"
                            id="ayudaConfirmacion"
                        >

                            Debe coincidir con la contraseña.

                        </small>

                    </div>



                    <!-- =================================================
                         ROL
                    ================================================== -->

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


                        <small class="text-muted">

                            El administrador puede gestionar usuarios.
                            El vendedor no.

                        </small>

                    </div>



                    <!-- =================================================
                         ESTADO
                    ================================================== -->

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
                            >


                            <label
                                class="form-check-label"
                                for="estado"
                            >

                                Usuario activo

                            </label>

                        </div>


                        <small class="text-muted">

                            Los usuarios inactivos no pueden
                            iniciar sesión.

                        </small>

                    </div>


                </div>



                <!-- =================================================
                     BOTONES
                ================================================== -->

                <hr class="my-4">


                <div
                    class="d-flex justify-content-end gap-2"
                >

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

                        <i class="bi bi-person-plus"></i>

                        Crear usuario

                    </button>

                </div>


            </form>

        </div>

    </div>

</div>



<!-- =====================================================
     JAVASCRIPT
     SOLO FUNCIONALIDAD, NO ESTILOS
====================================================== -->

<script>

// =====================================================
// MOSTRAR / OCULTAR CONTRASEÑA
// =====================================================

const password =
    document.getElementById("password");

const botonPassword =
    document.getElementById("mostrarPassword");

const iconoPassword =
    document.getElementById("iconoPassword");


botonPassword.addEventListener(
    "click",
    function () {

        if (password.type === "password") {

            password.type = "text";

            iconoPassword.classList.remove(
                "bi-eye"
            );

            iconoPassword.classList.add(
                "bi-eye-slash"
            );

            botonPassword.title =
                "Ocultar contraseña";

        } else {

            password.type = "password";

            iconoPassword.classList.remove(
                "bi-eye-slash"
            );

            iconoPassword.classList.add(
                "bi-eye"
            );

            botonPassword.title =
                "Mostrar contraseña";

        }

    }
);


// =====================================================
// MOSTRAR / OCULTAR CONFIRMACIÓN
// =====================================================

const passwordConfirmacion =
    document.getElementById(
        "password_confirmacion"
    );

const botonPasswordConfirmacion =
    document.getElementById(
        "mostrarPasswordConfirmacion"
    );

const iconoPasswordConfirmacion =
    document.getElementById(
        "iconoPasswordConfirmacion"
    );


botonPasswordConfirmacion.addEventListener(
    "click",
    function () {

        if (
            passwordConfirmacion.type ===
            "password"
        ) {

            passwordConfirmacion.type =
                "text";

            iconoPasswordConfirmacion.classList.remove(
                "bi-eye"
            );

            iconoPasswordConfirmacion.classList.add(
                "bi-eye-slash"
            );

            botonPasswordConfirmacion.title =
                "Ocultar contraseña";

        } else {

            passwordConfirmacion.type =
                "password";

            iconoPasswordConfirmacion.classList.remove(
                "bi-eye-slash"
            );

            iconoPasswordConfirmacion.classList.add(
                "bi-eye"
            );

            botonPasswordConfirmacion.title =
                "Mostrar contraseña";

        }

    }
);


// =====================================================
// COMPROBAR COINCIDENCIA DE CONTRASEÑAS
// =====================================================

const ayudaConfirmacion =
    document.getElementById(
        "ayudaConfirmacion"
    );


function comprobarPassword() {

    if (
        passwordConfirmacion.value === ""
    ) {

        ayudaConfirmacion.textContent =
            "Debe coincidir con la contraseña.";

        return;

    }


    if (
        password.value ===
        passwordConfirmacion.value
    ) {

        ayudaConfirmacion.textContent =
            "Las contraseñas coinciden.";

    } else {

        ayudaConfirmacion.textContent =
            "Las contraseñas no coinciden.";

    }

}


password.addEventListener(
    "input",
    comprobarPassword
);

passwordConfirmacion.addEventListener(
    "input",
    comprobarPassword
);


// =====================================================
// VALIDAR CORREO EN EL NAVEGADOR
// =====================================================

const email =
    document.getElementById("email");


email.addEventListener(
    "input",
    function () {

        if (email.validity.valid) {

            email.setCustomValidity("");

        } else {

            email.setCustomValidity(
                "Ingresa un correo electrónico válido."
            );

        }

    }
);


// =====================================================
// VALIDACIÓN ANTES DE ENVIAR
// =====================================================

const formulario =
    document.getElementById(
        "formCrearUsuario"
    );


formulario.addEventListener(
    "submit",
    function (event) {

        if (!email.checkValidity()) {

            event.preventDefault();

            alert(
                "Debes ingresar un correo electrónico válido."
            );

            email.focus();

            return;

        }


        if (
            password.value.length < 8
        ) {

            event.preventDefault();

            alert(
                "La contraseña debe tener al menos 8 caracteres."
            );

            password.focus();

            return;

        }


        if (
            password.value !==
            passwordConfirmacion.value
        ) {

            event.preventDefault();

            alert(
                "Las contraseñas no coinciden."
            );

            passwordConfirmacion.focus();

        }

    }
);

</script>


</body>

</html>