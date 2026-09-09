<?php

session_start();

require_once "../../config/database.php";

// Verificar sesión
if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../../login.php");
    exit;
}


// =====================================================
// VERIFICAR ID
// =====================================================

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: listar.php");
    exit;
}

$id = intval($_GET["id"]);


// =====================================================
// OBTENER CATEGORÍA
// =====================================================

$sqlCategoria = "
    SELECT
        id,
        nombre,
        estado
    FROM categorias
    WHERE id = ?
    LIMIT 1
";

$stmtCategoria = $conexion->prepare($sqlCategoria);

$stmtCategoria->bind_param("i", $id);

$stmtCategoria->execute();

$resultadoCategoria = $stmtCategoria->get_result();


// Si no existe
if ($resultadoCategoria->num_rows === 0) {

    $stmtCategoria->close();

    header("Location: listar.php");
    exit;
}

$categoria = $resultadoCategoria->fetch_assoc();

$stmtCategoria->close();


// =====================================================
// VARIABLES DEL FORMULARIO
// =====================================================

$nombre = $categoria["nombre"];
$estado = intval($categoria["estado"]);

$mensaje = "";
$tipoMensaje = "";


// =====================================================
// ACTUALIZAR CATEGORÍA
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre = trim($_POST["nombre"] ?? "");
    $estado = isset($_POST["estado"])
        ? intval($_POST["estado"])
        : 0;


    // =================================================
    // VALIDACIONES
    // =================================================

    if (empty($nombre)) {

        $mensaje = "El nombre de la categoría es obligatorio.";
        $tipoMensaje = "danger";

    } elseif ($estado !== 0 && $estado !== 1) {

        $mensaje = "El estado seleccionado no es válido.";
        $tipoMensaje = "danger";

    } else {


        // =============================================
        // VERIFICAR NOMBRE DUPLICADO
        // =============================================

        $sqlExiste = "
            SELECT id
            FROM categorias
            WHERE nombre = ?
            AND id != ?
            LIMIT 1
        ";

        $stmtExiste = $conexion->prepare($sqlExiste);

        $stmtExiste->bind_param(
            "si",
            $nombre,
            $id
        );

        $stmtExiste->execute();

        $resultadoExiste = $stmtExiste->get_result();


        if ($resultadoExiste->num_rows > 0) {

            $mensaje = "Ya existe otra categoría con ese nombre.";
            $tipoMensaje = "danger";

        } else {


            // =========================================
            // ACTUALIZAR CATEGORÍA
            // =========================================

            $sqlActualizar = "
                UPDATE categorias
                SET
                    nombre = ?,
                    estado = ?
                WHERE id = ?
            ";

            $stmtActualizar = $conexion->prepare($sqlActualizar);

            $stmtActualizar->bind_param(
                "sii",
                $nombre,
                $estado,
                $id
            );


            if ($stmtActualizar->execute()) {

                $stmtActualizar->close();
                $stmtExiste->close();

                header("Location: listar.php");
                exit;

            } else {

                $mensaje = "Ocurrió un error al actualizar la categoría.";
                $tipoMensaje = "danger";
            }

            $stmtActualizar->close();
        }

        $stmtExiste->close();
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

    <title>Editar categoría | Inventario</title>


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
                echo htmlspecialchars($_SESSION["nombre"]);
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

            <i class="bi bi-pencil-square"></i>

            Editar categoría

        </h2>


        <p class="text-muted">

            Modifica el nombre y estado de la categoría.

        </p>

    </div>


    <!-- FORMULARIO -->

    <div class="card form-card">

        <div class="card-body p-4">


            <?php if (!empty($mensaje)): ?>

                <div
                    class="alert alert-<?php echo $tipoMensaje; ?>"
                    role="alert"
                >

                    <?php
                    echo htmlspecialchars($mensaje);
                    ?>

                </div>

            <?php endif; ?>


            <form method="POST" action="">


                <div class="row g-4">


                    <!-- NOMBRE -->

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
                            value="<?php echo htmlspecialchars($nombre); ?>"
                            maxlength="100"
                            required
                        >


                        <div class="form-text">

                            Modifica el nombre de la categoría.

                        </div>

                    </div>


                    <!-- ESTADO -->

                    <div class="col-md-4">

                        <label
                            for="estado"
                            class="form-label"
                        >

                            Estado

                        </label>


                        <select
                            class="form-select"
                            id="estado"
                            name="estado"
                        >

                            <option
                                value="1"
                                <?php
                                echo ($estado === 1)
                                    ? "selected"
                                    : "";
                                ?>
                            >

                                Activo

                            </option>


                            <option
                                value="0"
                                <?php
                                echo ($estado === 0)
                                    ? "selected"
                                    : "";
                                ?>
                            >

                                Inactivo

                            </option>

                        </select>


                        <div class="form-text">

                            Las categorías inactivas no aparecerán
                            al crear nuevos productos.

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

                        Guardar cambios

                    </button>

                </div>


            </form>

        </div>

    </div>

</div>


</body>

</html>