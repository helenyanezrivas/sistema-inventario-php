<?php

session_start();

require_once "../../config/database.php";
require_once "../../includes/security.php";

// Verificar sesión
if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../../login.php");
    exit;
}

csrf_token();


// =====================================================
// VERIFICAR ID DEL PRODUCTO
// =====================================================

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: listar.php");
    exit;
}

$id = intval($_GET["id"]);


// =====================================================
// OBTENER PRODUCTO
// =====================================================

$sqlProducto = "
    SELECT
        id,
        codigo,
        nombre,
        descripcion,
        categoria_id,
        precio_compra,
        precio_venta,
        stock,
        stock_minimo,
        estado
    FROM productos
    WHERE id = ?
    LIMIT 1
";

$stmtProducto = $conexion->prepare($sqlProducto);

$stmtProducto->bind_param("i", $id);

$stmtProducto->execute();

$resultadoProducto = $stmtProducto->get_result();


// Si no existe el producto
if ($resultadoProducto->num_rows === 0) {

    $stmtProducto->close();

    header("Location: listar.php");
    exit;
}

$producto = $resultadoProducto->fetch_assoc();

$stmtProducto->close();


// =====================================================
// VARIABLES DEL FORMULARIO
// =====================================================

$codigo = $producto["codigo"];
$nombre = $producto["nombre"];
$descripcion = $producto["descripcion"];
$categoria_id = $producto["categoria_id"];
$precio_compra = $producto["precio_compra"];
$precio_venta = $producto["precio_venta"];
$stock = $producto["stock"];
$estado = $producto["estado"];

$mensaje = "";
$tipoMensaje = "";


// =====================================================
// ACTUALIZAR PRODUCTO
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Verificar protección CSRF
    verificar_csrf();

    $codigo = trim($_POST["codigo"] ?? "");
    $nombre = trim($_POST["nombre"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $categoria_id = intval($_POST["categoria_id"] ?? 0);
    $precio_compra = floatval($_POST["precio_compra"] ?? 0);
    $precio_venta = floatval($_POST["precio_venta"] ?? 0);
    $stock = intval($_POST["stock"] ?? 0);
    $estado = isset($_POST["estado"]) ? intval($_POST["estado"]) : 0;


    // =================================================
    // VALIDACIONES
    // =================================================

    if (
        empty($codigo) ||
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

    } elseif ($estado !== 0 && $estado !== 1) {

        $mensaje = "El estado seleccionado no es válido.";
        $tipoMensaje = "danger";

    } else {


        // =============================================
        // VERIFICAR CÓDIGO DUPLICADO
        // =============================================

        $sqlExiste = "
            SELECT id
            FROM productos
            WHERE codigo = ?
            AND id != ?
            LIMIT 1
        ";

        $stmtExiste = $conexion->prepare($sqlExiste);

        $stmtExiste->bind_param(
            "si",
            $codigo,
            $id
        );

        $stmtExiste->execute();

        $resultadoExiste = $stmtExiste->get_result();


        if ($resultadoExiste->num_rows > 0) {

            $mensaje = "Ya existe otro producto con ese código.";
            $tipoMensaje = "danger";

        } else {


            // =========================================
            // ACTUALIZAR PRODUCTO
            // =========================================

            $sqlActualizar = "
                UPDATE productos
                SET
                    codigo = ?,
                    nombre = ?,
                    descripcion = ?,
                    categoria_id = ?,
                    precio_compra = ?,
                    precio_venta = ?,
                    stock = ?,
                    estado = ?
                WHERE id = ?
            ";

            $stmtActualizar = $conexion->prepare($sqlActualizar);

            $stmtActualizar->bind_param(
                "sssiddiii",
                $codigo,
                $nombre,
                $descripcion,
                $categoria_id,
                $precio_compra,
                $precio_venta,
                $stock,
                $estado,
                $id
            );


            if ($stmtActualizar->execute()) {

                header("Location: listar.php?mensaje=editado");
                exit;

            } else {

                $mensaje = "Ocurrió un error al actualizar el producto.";
                $tipoMensaje = "danger";
            }

            $stmtActualizar->close();
        }

        $stmtExiste->close();
    }
}


// =====================================================
// OBTENER CATEGORÍAS
// =====================================================

$sqlCategorias = "
    SELECT
        id,
        nombre
    FROM categorias
    WHERE estado = 1
    ORDER BY nombre ASC
";

$resultadoCategorias = $conexion->query($sqlCategorias);

?>


<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Editar producto | Inventario</title>


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

            Volver a productos

        </a>


        <h2 class="mt-3">

            <i class="bi bi-pencil-square"></i>

            Editar producto

        </h2>


        <p class="text-muted">

            Modifica la información del producto.

        </p>

    </div>


    <!-- FORMULARIO -->

    <div class="card form-card">

        <div class="card-body p-4">


            <?php if (!empty($mensaje)): ?>

                <div
                    class="alert alert-<?php echo htmlspecialchars($tipoMensaje, ENT_QUOTES, "UTF-8"); ?>"
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


            <form method="POST" action="">

                <?php echo csrf_field(); ?>


                <div class="row g-4">


                    <!-- CÓDIGO -->

                    <div class="col-md-6">

                        <label
                            for="codigo"
                            class="form-label"
                        >

                            Código

                            <span class="required">*</span>

                        </label>


                        <input
                            type="text"
                            class="form-control"
                            id="codigo"
                            name="codigo"
                            value="<?php echo htmlspecialchars($codigo, ENT_QUOTES, "UTF-8"); ?>"
                            maxlength="50"
                            required
                        >

                    </div>


                    <!-- NOMBRE -->

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
                            value="<?php echo htmlspecialchars($nombre, ENT_QUOTES, "UTF-8"); ?>"
                            maxlength="150"
                            required
                        >

                    </div>


                    <!-- CATEGORÍA -->

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


                            <?php while ($categoria = $resultadoCategorias->fetch_assoc()): ?>

                                <option
                                    value="<?php echo (int) $categoria["id"]; ?>"
                                    <?php
                                    echo (
                                        $categoria_id == $categoria["id"]
                                    )
                                        ? "selected"
                                        : "";
                                    ?>
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $categoria["nombre"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>

                                </option>

                            <?php endwhile; ?>

                        </select>

                    </div>


                    <!-- DESCRIPCIÓN -->

                    <div class="col-md-6">

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
                        ><?php echo htmlspecialchars($descripcion, ENT_QUOTES, "UTF-8"); ?></textarea>

                    </div>


                    <!-- PRECIO COMPRA -->

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
                                value="<?php echo htmlspecialchars($precio_compra, ENT_QUOTES, "UTF-8"); ?>"
                            >

                        </div>

                    </div>


                    <!-- PRECIO VENTA -->

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
                                value="<?php echo htmlspecialchars($precio_venta, ENT_QUOTES, "UTF-8"); ?>"
                            >

                        </div>

                    </div>


                    <!-- STOCK -->

                    <div class="col-md-4">

                        <label
                            for="stock"
                            class="form-label"
                        >

                            Stock

                        </label>


                        <input
                            type="number"
                            class="form-control"
                            id="stock"
                            name="stock"
                            min="0"
                            step="1"
                            value="<?php echo htmlspecialchars($stock, ENT_QUOTES, "UTF-8"); ?>"
                        >

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
                                echo ($estado == 1)
                                    ? "selected"
                                    : "";
                                ?>
                            >
                                Activo
                            </option>


                            <option
                                value="0"
                                <?php
                                echo ($estado == 0)
                                    ? "selected"
                                    : "";
                                ?>
                            >
                                Inactivo
                            </option>

                        </select>

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