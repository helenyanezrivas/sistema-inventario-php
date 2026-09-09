<?php

session_start();

require_once "../../config/database.php";
require_once "../../includes/security.php";

// Verificar sesión
if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../../login.php");
    exit;
}

$mensaje = "";
$tipoMensaje = "";

// Generar token CSRF
csrf_token();

// =====================================================
// GUARDAR CATEGORÍA
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Verificar token CSRF
    verificar_csrf();

    $nombre = trim($_POST["nombre"] ?? "");

    // Validar nombre
    if (empty($nombre)) {

        $mensaje = "Debes ingresar el nombre de la categoría.";
        $tipoMensaje = "danger";

    } else {

        // Verificar si ya existe
        $sqlExiste = "
            SELECT id
            FROM categorias
            WHERE nombre = ?
            LIMIT 1
        ";

        $stmtExiste = $conexion->prepare($sqlExiste);

        if (!$stmtExiste) {

            $mensaje = "Ocurrió un error al verificar la categoría.";
            $tipoMensaje = "danger";

        } else {

            $stmtExiste->bind_param("s", $nombre);
            $stmtExiste->execute();

            $resultadoExiste = $stmtExiste->get_result();

            if ($resultadoExiste->num_rows > 0) {

                $mensaje = "Ya existe una categoría con ese nombre.";
                $tipoMensaje = "danger";

            } else {

                // Insertar categoría
                $sql = "
                    INSERT INTO categorias
                    (nombre, estado)
                    VALUES (?, 1)
                ";

                $stmt = $conexion->prepare($sql);

                if (!$stmt) {

                    $mensaje = "Ocurrió un error al guardar la categoría.";
                    $tipoMensaje = "danger";

                } else {

                    $stmt->bind_param("s", $nombre);

                    if ($stmt->execute()) {

                        $stmt->close();
                        $stmtExiste->close();

                        header("Location: listar.php");
                        exit;

                    } else {

                        $mensaje = "Ocurrió un error al guardar la categoría.";
                        $tipoMensaje = "danger";

                    }

                    $stmt->close();
                }
            }

            $stmtExiste->close();
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

    <title>Nueva categoría | Inventario</title>

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

<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="navbar navbar-dark">

    <div class="container-fluid px-4">

        <a
            href="../../index.php"
            class="navbar-brand fw-bold"
        >

            <i class="bi bi-box-seam"></i>

            Sistema de Inventario

        </a>

        <div class="d-flex align-items-center gap-3">

            <span class="text-white">

                <i class="bi bi-person-circle"></i>

                <?php
                echo htmlspecialchars(
                    $_SESSION["nombre"] ?? "Usuario",
                    ENT_QUOTES,
                    "UTF-8"
                );
                ?>

            </span>

            <a
                href="../../logout.php"
                class="btn btn-light btn-sm"
            >

                <i class="bi bi-box-arrow-right"></i>

                Cerrar sesión

            </a>

        </div>

    </div>

</nav>


<!-- =====================================================
     CONTENIDO
===================================================== -->

<div class="container py-4">

    <!-- TÍTULO -->

    <div class="mb-4">

        <a
            href="listar.php"
            class="text-decoration-none"
        >

            <i class="bi bi-arrow-left"></i>

            Volver a categorías

        </a>

        <h2 class="mt-3">

            <i class="bi bi-plus-circle"></i>

            Nueva categoría

        </h2>

        <p class="text-muted">

            Registra una nueva categoría para organizar tus productos.

        </p>

    </div>


    <!-- =================================================
         FORMULARIO
    ================================================== -->

    <div class="card form-card">

        <div class="card-body p-4">

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

                <div class="row">

                    <div class="col-md-8">

                        <label
                            for="nombre"
                            class="form-label"
                        >

                            Nombre de la categoría

                            <span class="required">*</span>

                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="nombre"
                            name="nombre"
                            placeholder="Ej: Computación"
                            value="<?php echo htmlspecialchars(
                                $_POST["nombre"] ?? "",
                                ENT_QUOTES,
                                "UTF-8"
                            ); ?>"
                            maxlength="100"
                            required
                        >

                        <div class="form-text">

                            Ingresa un nombre claro para identificar la categoría.

                        </div>

                    </div>

                </div>


                <!-- BOTONES -->

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">

                    <a
                        href="listar.php"
                        class="btn btn-secondary"
                    >

                        <i class="bi bi-x-lg"></i>

                        Cancelar

                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        <i class="bi bi-save"></i>

                        Guardar categoría

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

</body>

</html>