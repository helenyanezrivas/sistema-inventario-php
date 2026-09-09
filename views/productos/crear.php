<?php

session_start();

require_once "../../config/database.php";

// Verificar sesión
if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../../login.php");
    exit;
}

$mensaje = "";
$tipoMensaje = "";


// =====================================================
// FUNCIÓN PARA GENERAR CÓDIGO AUTOMÁTICO
// =====================================================

function generarCodigoProducto($nombre, $conexion)
{
    // Quitar espacios al inicio y final
    $nombre = trim($nombre);

    // Eliminar caracteres especiales y dejar letras/números
    $nombreLimpio = preg_replace(
        "/[^a-zA-ZáéíóúÁÉÍÓÚñÑ0-9]/u",
        "",
        $nombre
    );

    // Si por alguna razón queda vacío
    if (empty($nombreLimpio)) {
        $prefijo = "PRO";
    } else {

        // Tomar las primeras 3 letras
        $prefijo = mb_substr(
            $nombreLimpio,
            0,
            3,
            "UTF-8"
        );

        // Convertir a mayúsculas
        $prefijo = mb_strtoupper(
            $prefijo,
            "UTF-8"
        );

        // Si tiene menos de 3 caracteres,
        // completar con X
        $prefijo = str_pad(
            $prefijo,
            3,
            "X"
        );
    }


    // =================================================
    // BUSCAR EL PRIMER NÚMERO DISPONIBLE
    // =================================================

    $numero = 1;

    while (true) {

        $codigo = $prefijo . "-" . str_pad(
            $numero,
            3,
            "0",
            STR_PAD_LEFT
        );


        $sql = "
            SELECT id
            FROM productos
            WHERE codigo = ?
            LIMIT 1
        ";

        $stmt = $conexion->prepare($sql);

        if (!$stmt) {
            die(
                "Error al preparar la consulta del código: "
                . $conexion->error
            );
        }

        $stmt->bind_param(
            "s",
            $codigo
        );

        $stmt->execute();

        $resultado = $stmt->get_result();

        $existe = $resultado->num_rows > 0;

        $stmt->close();


        // Si no existe, encontramos nuestro código
        if (!$existe) {
            return $codigo;
        }


        // Si existe, probar con el siguiente
        $numero++;
    }
}


// =====================================================
// GUARDAR PRODUCTO
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre = trim($_POST["nombre"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $categoria_id = intval($_POST["categoria_id"] ?? 0);
    $precio_compra = floatval($_POST["precio_compra"] ?? 0);
    $precio_venta = floatval($_POST["precio_venta"] ?? 0);
    $stock = intval($_POST["stock"] ?? 0);


    // =================================================
    // VALIDACIONES
    // =================================================

    if (
        empty($nombre) ||
        $categoria_id <= 0
    ) {

        $mensaje = "Completa todos los campos obligatorios.";
        $tipoMensaje = "danger";

    } elseif ($precio_compra < 0 || $precio_venta < 0) {

        $mensaje = "Los precios no pueden ser negativos.";
        $tipoMensaje = "danger";

    } elseif ($stock < 0) {

        $mensaje = "El stock no puede ser negativo.";
        $tipoMensaje = "danger";

    } else {


        // =============================================
        // VERIFICAR QUE LA CATEGORÍA EXISTA
        // =============================================

        $sqlCategoria = "
            SELECT id
            FROM categorias
            WHERE id = ?
            AND estado = 1
            LIMIT 1
        ";

        $stmtCategoria = $conexion->prepare($sqlCategoria);

        if (!$stmtCategoria) {
            die(
                "Error al preparar la consulta: "
                . $conexion->error
            );
        }

        $stmtCategoria->bind_param(
            "i",
            $categoria_id
        );

        $stmtCategoria->execute();

        $resultadoCategoria =
            $stmtCategoria->get_result();


        if ($resultadoCategoria->num_rows === 0) {

            $mensaje = "La categoría seleccionada no es válida.";
            $tipoMensaje = "danger";

        } else {


            // =========================================
            // GENERAR CÓDIGO AUTOMÁTICAMENTE
            // =========================================

            $codigo = generarCodigoProducto(
                $nombre,
                $conexion
            );


            // =========================================
            // INSERTAR PRODUCTO
            //
            // stock_minimo NO se envía.
            // MySQL utilizará automáticamente 5.
            // =========================================

            $sql = "
                INSERT INTO productos
                (
                    codigo,
                    nombre,
                    descripcion,
                    categoria_id,
                    precio_compra,
                    precio_venta,
                    stock,
                    estado
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)
            ";

            $stmt = $conexion->prepare($sql);

            if (!$stmt) {
                die(
                    "Error al preparar la consulta: "
                    . $conexion->error
                );
            }

            $stmt->bind_param(
                "sssiddi",
                $codigo,
                $nombre,
                $descripcion,
                $categoria_id,
                $precio_compra,
                $precio_venta,
                $stock
            );


            if ($stmt->execute()) {

                header(
                    "Location: listar.php?mensaje=creado"
                );

                exit;

            } else {

                $mensaje =
                    "Ocurrió un error al guardar el producto.";

                $tipoMensaje = "danger";
            }

            $stmt->close();
        }

        $stmtCategoria->close();
    }
}


// =====================================================
// OBTENER CATEGORÍAS
// =====================================================

$sqlCategorias = "
    SELECT id, nombre
    FROM categorias
    WHERE estado = 1
    ORDER BY nombre ASC
";

$resultadoCategorias =
    $conexion->query($sqlCategorias);

if (!$resultadoCategorias) {
    die(
        "Error al obtener categorías: "
        . $conexion->error
    );
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

    <title>Nuevo producto | Inventario</title>


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
                    $_SESSION["nombre"]
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

            Volver a productos

        </a>


        <h2 class="mt-3">

            <i class="bi bi-plus-circle"></i>

            Nuevo producto

        </h2>


        <p class="text-muted">

            Registra un nuevo producto en el inventario.

        </p>

    </div>


    <!-- =================================================
         FORMULARIO
    ================================================== -->

    <div class="card form-card">

        <div class="card-body p-4">


            <!-- MENSAJE -->

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


            <form
                method="POST"
                action=""
            >


                <div class="row g-4">


                    <!-- =================================
                         NOMBRE
                    ================================== -->

                    <div class="col-md-6">

                        <label
                            for="nombre"
                            class="form-label"
                        >

                            Nombre del producto

                            <span class="required">*</span>

                        </label>


                        <input
                            type="text"
                            class="form-control"
                            id="nombre"
                            name="nombre"
                            placeholder="Ej: Teclado USB"
                            value="<?php
                                echo htmlspecialchars(
                                    $_POST["nombre"] ?? ""
                                );
                            ?>"
                            required
                        >

                    </div>


                    <!-- =================================
                         CATEGORÍA
                    ================================== -->

                    <div class="col-md-6">

                        <label
                            for="categoria_id"
                            class="form-label"
                        >

                            Categoría

                            <span class="required">*</span>

                        </label>


                        <select
                            class="form-select"
                            id="categoria_id"
                            name="categoria_id"
                            required
                        >

                            <option value="">

                                Selecciona una categoría

                            </option>


                            <?php
                            while (
                                $categoria =
                                $resultadoCategorias->fetch_assoc()
                            ):
                            ?>

                                <option
                                    value="<?php
                                        echo $categoria["id"];
                                    ?>"
                                    <?php
                                    echo (
                                        isset(
                                            $_POST["categoria_id"]
                                        )
                                        &&
                                        $_POST["categoria_id"]
                                        ==
                                        $categoria["id"]
                                    )
                                        ? "selected"
                                        : "";
                                    ?>
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $categoria["nombre"]
                                    );
                                    ?>

                                </option>

                            <?php endwhile; ?>

                        </select>

                    </div>


                    <!-- =================================
                         DESCRIPCIÓN
                    ================================== -->

                    <div class="col-12">

                        <label
                            for="descripcion"
                            class="form-label"
                        >

                            Descripción

                        </label>


                        <textarea
                            class="form-control"
                            id="descripcion"
                            name="descripcion"
                            rows="3"
                            placeholder="Descripción del producto"
                        ><?php
                            echo htmlspecialchars(
                                $_POST["descripcion"] ?? ""
                            );
                        ?></textarea>

                    </div>


                    <!-- =================================
                         PRECIO DE COMPRA
                    ================================== -->

                    <div class="col-md-4">

                        <label
                            for="precio_compra"
                            class="form-label"
                        >

                            Precio de compra

                        </label>


                        <div class="input-group">

                            <span class="input-group-text">
                                $
                            </span>


                            <input
                                type="number"
                                class="form-control"
                                id="precio_compra"
                                name="precio_compra"
                                min="0"
                                step="1"
                                value="<?php
                                    echo htmlspecialchars(
                                        $_POST["precio_compra"]
                                        ?? "0"
                                    );
                                ?>"
                            >

                        </div>

                    </div>


                    <!-- =================================
                         PRECIO DE VENTA
                    ================================== -->

                    <div class="col-md-4">

                        <label
                            for="precio_venta"
                            class="form-label"
                        >

                            Precio de venta

                        </label>


                        <div class="input-group">

                            <span class="input-group-text">
                                $
                            </span>


                            <input
                                type="number"
                                class="form-control"
                                id="precio_venta"
                                name="precio_venta"
                                min="0"
                                step="1"
                                value="<?php
                                    echo htmlspecialchars(
                                        $_POST["precio_venta"]
                                        ?? "0"
                                    );
                                ?>"
                            >

                        </div>

                    </div>


                    <!-- =================================
                         STOCK INICIAL
                    ================================== -->

                    <div class="col-md-4">

                        <label
                            for="stock"
                            class="form-label"
                        >

                            Stock inicial

                        </label>


                        <input
                            type="number"
                            class="form-control"
                            id="stock"
                            name="stock"
                            min="0"
                            step="1"
                            value="<?php
                                echo htmlspecialchars(
                                    $_POST["stock"] ?? "0"
                                );
                            ?>"
                        >

                    </div>


                </div>


                <!-- =====================================
                     INFORMACIÓN DEL CÓDIGO
                ====================================== -->

                <div class="alert alert-light border mt-4 mb-0">

                    <div class="d-flex align-items-start">

                        <i
                            class="bi bi-info-circle me-2 mt-1"
                        ></i>

                        <div>

                            <strong>
                                Código automático
                            </strong>

                            <div class="text-muted small">

                                El sistema generará automáticamente
                                el código del producto utilizando las
                                primeras letras de su nombre.

                            </div>

                        </div>

                    </div>

                </div>


                <!-- =====================================
                     BOTONES
                ====================================== -->

                <div
                    class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top"
                >


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

                        Guardar producto

                    </button>


                </div>


            </form>

        </div>

    </div>

</div>


</body>

</html>