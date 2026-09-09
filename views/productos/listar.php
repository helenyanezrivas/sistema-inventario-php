<?php

session_start();

require_once "../../config/database.php";

// =====================================================
// VERIFICAR SESIÓN
// =====================================================

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../../login.php");
    exit;
}

// =====================================================
// FILTROS
// =====================================================

$busqueda = trim($_GET["busqueda"] ?? "");
$stockFiltro = $_GET["stock"] ?? "";
$estadoFiltro = $_GET["estado"] ?? "";

// =====================================================
// CONSULTA DE PRODUCTOS
// =====================================================

$sql = "
    SELECT
        p.id,
        p.codigo,
        p.nombre,
        p.descripcion,
        c.nombre AS categoria,
        p.precio_compra,
        p.precio_venta,
        p.stock,
        p.stock_minimo,
        p.estado
    FROM productos p
    INNER JOIN categorias c
        ON p.categoria_id = c.id
    WHERE 1 = 1
";

$tipos = "";
$parametros = [];

// =====================================================
// FILTRO DE BÚSQUEDA
// =====================================================

if ($busqueda !== "") {

    $sql .= "
        AND (
            p.codigo LIKE ?
            OR p.nombre LIKE ?
            OR c.nombre LIKE ?
        )
    ";

    $busquedaLike = "%" . $busqueda . "%";

    $tipos .= "sss";

    $parametros[] = $busquedaLike;
    $parametros[] = $busquedaLike;
    $parametros[] = $busquedaLike;
}

// =====================================================
// FILTRO DE STOCK
// =====================================================

if ($stockFiltro === "agotado") {

    $sql .= "
        AND p.stock = 0
    ";

} elseif ($stockFiltro === "bajo") {

    $sql .= "
        AND p.stock > 0
        AND p.stock <= p.stock_minimo
    ";

} elseif ($stockFiltro === "normal") {

    $sql .= "
        AND p.stock > p.stock_minimo
    ";
}

// =====================================================
// FILTRO DE ESTADO
// =====================================================

if ($estadoFiltro === "activo") {

    $sql .= "
        AND p.estado = 1
    ";

} elseif ($estadoFiltro === "inactivo") {

    $sql .= "
        AND p.estado = 0
    ";
}

// =====================================================
// ORDEN
// =====================================================

$sql .= "
    ORDER BY p.id DESC
";

// =====================================================
// EJECUTAR CONSULTA
// =====================================================

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    die("Error al preparar la consulta: " . $conexion->error);
}

if (!empty($parametros)) {

    $stmt->bind_param(
        $tipos,
        ...$parametros
    );
}

if (!$stmt->execute()) {
    die("Error al obtener los productos: " . $stmt->error);
}

$resultado = $stmt->get_result();

// =====================================================
// URLS DE EXPORTACIÓN
// =====================================================

$parametrosExportacion = [];

if ($busqueda !== "") {
    $parametrosExportacion["busqueda"] = $busqueda;
}

if ($stockFiltro !== "") {
    $parametrosExportacion["stock"] = $stockFiltro;
}

if ($estadoFiltro !== "") {
    $parametrosExportacion["estado"] = $estadoFiltro;
}

$queryExportacion = "";

if (!empty($parametrosExportacion)) {
    $queryExportacion = "?" . http_build_query($parametrosExportacion);
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

    <title>Productos | Inventario</title>

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

<?php

// =====================================================
// NAVBAR COMPARTIDA
// =====================================================

include "../../includes/navbar.php";

?>

<?php

$mensaje = $_GET["mensaje"] ?? "";

$mensajes = [
    "creado" => "Producto creado correctamente.",
    "editado" => "Producto editado correctamente.",
    "eliminado" => "Producto eliminado correctamente.",
    "no_encontrado" => "El producto seleccionado no existe."
];

?>

<?php if (isset($mensajes[$mensaje])): ?>

    <div class="container-fluid px-4 pt-4">

        <div
            class="alert alert-success alert-dismissible fade show"
            role="alert"
            id="mensajeConfirmacion"
        >

            <i class="bi bi-check-circle-fill"></i>

            <?php echo htmlspecialchars($mensajes[$mensaje]); ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Cerrar"
            ></button>

        </div>

    </div>

<?php endif; ?>

<!-- =====================================================
     CONTENIDO
===================================================== -->

<div class="container-fluid px-4 py-4">

    <!-- =================================================
         ENCABEZADO
    ================================================== -->

    <div
        class="d-flex justify-content-between align-items-center mb-4"
    >

        <div>

            <h2 class="mb-1">

                <i class="bi bi-box-seam"></i>

                Productos

            </h2>

            <p class="text-muted mb-0">

                Gestiona los productos del inventario.

            </p>

        </div>

        <div class="d-flex gap-2">

            <!-- EXPORTAR EXCEL -->

            <a
                href="exportar_excel.php<?php echo $queryExportacion; ?>"
                class="btn btn-success"
                title="Exportar productos a Excel"
            >

                <i class="bi bi-file-earmark-excel"></i>

                Excel

            </a>

            <!-- EXPORTAR PDF -->

            <a
                href="exportar_pdf.php<?php echo $queryExportacion; ?>"
                class="btn btn-danger"
                title="Exportar productos a PDF"
            >

                <i class="bi bi-file-earmark-pdf"></i>

                PDF

            </a>

            <!-- NUEVO PRODUCTO -->

            <a
                href="crear.php"
                class="btn btn-primary"
            >

                <i class="bi bi-plus-lg"></i>

                Nuevo producto

            </a>

        </div>

    </div>

    <!-- =================================================
         FILTROS
    ================================================== -->

    <div class="card mb-4">

        <div class="card-body">

            <form
                method="GET"
                action="listar.php"
            >

                <div class="row g-3 align-items-end">

                    <!-- BÚSQUEDA -->

                    <div class="col-lg-5">

                        <label
                            for="busqueda"
                            class="form-label"
                        >

                            Buscar producto

                        </label>

                        <div class="input-group">

                            <span class="input-group-text">

                                <i class="bi bi-search"></i>

                            </span>

                            <input
                                type="text"
                                id="busqueda"
                                name="busqueda"
                                class="form-control"
                                placeholder="Código, producto o categoría..."
                                value="<?php echo htmlspecialchars($busqueda); ?>"
                            >

                        </div>

                    </div>

                    <!-- FILTRO STOCK -->

                    <div class="col-lg-3">

                        <label
                            for="stock"
                            class="form-label"
                        >

                            Stock

                        </label>

                        <select
                            id="stock"
                            name="stock"
                            class="form-select"
                        >

                            <option value="">
                                Todos
                            </option>

                            <option
                                value="agotado"
                                <?php echo $stockFiltro === "agotado" ? "selected" : ""; ?>
                            >
                                Agotado
                            </option>

                            <option
                                value="bajo"
                                <?php echo $stockFiltro === "bajo" ? "selected" : ""; ?>
                            >
                                Stock bajo
                            </option>

                            <option
                                value="normal"
                                <?php echo $stockFiltro === "normal" ? "selected" : ""; ?>
                            >
                                Stock normal
                            </option>

                        </select>

                    </div>

                    <!-- FILTRO ESTADO -->

                    <div class="col-lg-2">

                        <label
                            for="estado"
                            class="form-label"
                        >

                            Estado

                        </label>

                        <select
                            id="estado"
                            name="estado"
                            class="form-select"
                        >

                            <option value="">
                                Todos
                            </option>

                            <option
                                value="activo"
                                <?php echo $estadoFiltro === "activo" ? "selected" : ""; ?>
                            >
                                Activos
                            </option>

                            <option
                                value="inactivo"
                                <?php echo $estadoFiltro === "inactivo" ? "selected" : ""; ?>
                            >
                                Inactivos
                            </option>

                        </select>

                    </div>

                    <!-- BOTONES -->

                    <div class="col-lg-2">

                        <div class="d-flex gap-2">

                            <button
                                type="submit"
                                class="btn btn-primary flex-grow-1"
                            >

                                <i class="bi bi-search"></i>

                                Buscar

                            </button>

                            <a
                                href="listar.php"
                                class="btn btn-outline-secondary"
                                title="Limpiar filtros"
                            >

                                <i class="bi bi-arrow-counterclockwise"></i>

                            </a>

                        </div>

                    </div>

                </div>

            </form>

        </div>

    </div>

    <!-- =================================================
         RESULTADOS
    ================================================== -->

    <div class="card">

        <div class="card-body">

            <!-- ENCABEZADO DE RESULTADOS -->

            <div
                class="d-flex justify-content-between align-items-center mb-3"
            >

                <div>

                    <h5 class="mb-1">

                        <i class="bi bi-list-ul"></i>

                        Inventario

                    </h5>

                    <small class="text-muted">

                        <?php
                        echo $resultado->num_rows;
                        ?>

                        <?php
                        echo $resultado->num_rows === 1
                            ? " producto encontrado"
                            : " productos encontrados";
                        ?>

                    </small>

                </div>

                <?php if (
                    $busqueda !== ""
                    || $stockFiltro !== ""
                    || $estadoFiltro !== ""
                ): ?>

                    <span class="badge rounded-pill text-bg-light border">

                        <i class="bi bi-funnel"></i>

                        Filtros aplicados

                    </span>

                <?php endif; ?>

            </div>

            <!-- =================================================
                 TABLA
            ================================================== -->

            <div class="table-responsive">

                <table
                    class="table table-hover align-middle"
                >

                    <thead>

                        <tr>

                            <th>ID</th>

                            <th>Código</th>

                            <th>Producto</th>

                            <th>Categoría</th>

                            <th>Precio compra</th>

                            <th>Precio venta</th>

                            <th>Stock</th>

                            <th>Estado</th>

                            <th>Acciones</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php if ($resultado->num_rows > 0): ?>

                        <?php while (
                            $producto =
                            $resultado->fetch_assoc()
                        ): ?>

                            <tr>

                                <!-- ID -->

                                <td>

                                    <strong>

                                        #

                                        <?php
                                        echo $producto["id"];
                                        ?>

                                    </strong>

                                </td>

                                <!-- CÓDIGO -->

                                <td>

                                    <span class="text-muted">

                                        <?php
                                        echo htmlspecialchars(
                                            $producto["codigo"]
                                        );
                                        ?>

                                    </span>

                                </td>

                                <!-- PRODUCTO -->

                                <td>

                                    <strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $producto["nombre"]
                                        );
                                        ?>

                                    </strong>

                                    <?php if (
                                        !empty(
                                            $producto["descripcion"]
                                        )
                                    ): ?>

                                        <br>

                                        <small class="text-muted">

                                            <?php
                                            echo htmlspecialchars(
                                                $producto["descripcion"]
                                            );
                                            ?>

                                        </small>

                                    <?php endif; ?>

                                </td>

                                <!-- CATEGORÍA -->

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $producto["categoria"]
                                    );
                                    ?>

                                </td>

                                <!-- PRECIO COMPRA -->

                                <td>

                                    $

                                    <?php
                                    echo number_format(
                                        $producto["precio_compra"],
                                        0,
                                        ",",
                                        "."
                                    );
                                    ?>

                                </td>

                                <!-- PRECIO VENTA -->

                                <td>

                                    <strong>

                                        $

                                        <?php
                                        echo number_format(
                                            $producto["precio_venta"],
                                            0,
                                            ",",
                                            "."
                                        );
                                        ?>

                                    </strong>

                                </td>

                                <!-- STOCK -->

                                <td>

                                    <?php if (
                                        $producto["stock"] == 0
                                    ): ?>

                                        <span
                                            class="badge text-bg-danger"
                                        >

                                            Agotado

                                        </span>

                                    <?php elseif (
                                        $producto["stock"]
                                        <=
                                        $producto["stock_minimo"]
                                    ): ?>

                                        <span
                                            class="badge text-bg-warning"
                                        >

                                            <?php
                                            echo $producto["stock"];
                                            ?>

                                            bajo

                                        </span>

                                    <?php else: ?>

                                        <span
                                            class="badge text-bg-success"
                                        >

                                            <?php
                                            echo $producto["stock"];
                                            ?>

                                            disponibles

                                        </span>

                                    <?php endif; ?>

                                </td>

                                <!-- ESTADO -->

                                <td>

                                    <?php if (
                                        $producto["estado"] == 1
                                    ): ?>

                                        <span
                                            class="badge text-bg-success"
                                        >

                                            Activo

                                        </span>

                                    <?php else: ?>

                                        <span
                                            class="badge text-bg-secondary"
                                        >

                                            Inactivo

                                        </span>

                                    <?php endif; ?>

                                </td>

                                <!-- ACCIONES -->

                                <td>

                                    <div class="d-flex gap-1">

                                        <a
                                            href="editar.php?id=<?php echo $producto["id"]; ?>"
                                            class="btn btn-sm btn-warning"
                                            title="Editar producto"
                                        >

                                            <i class="bi bi-pencil"></i>

                                        </a>

                                        <a
                                            href="eliminar.php?id=<?php echo $producto["id"]; ?>"
                                            class="btn btn-sm btn-danger"
                                            title="Eliminar producto"
                                            onclick="return confirm('¿Estás segura de eliminar este producto?');"
                                        >

                                            <i class="bi bi-trash"></i>

                                        </a>

                                    </div>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <!-- SIN RESULTADOS -->

                        <tr>

                            <td
                                colspan="9"
                                class="text-center py-5"
                            >

                                <i
                                    class="bi bi-search fs-1 text-muted"
                                ></i>

                                <h5 class="mt-3">

                                    No se encontraron productos

                                </h5>

                                <p class="text-muted mb-3">

                                    Prueba cambiando los filtros
                                    de búsqueda.

                                </p>

                                <a
                                    href="listar.php"
                                    class="btn btn-outline-primary"
                                >

                                    <i class="bi bi-arrow-counterclockwise"></i>

                                    Limpiar filtros

                                </a>

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

<!-- =====================================================
     BOOTSTRAP JS
===================================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"
></script>

<script>

document.addEventListener("DOMContentLoaded", function () {

    const alerta = document.getElementById("mensajeConfirmacion");

    if (!alerta) {
        return;
    }

    setTimeout(function () {

        const instancia = bootstrap.Alert.getOrCreateInstance(alerta);

        instancia.close();

    }, 4000);

});

</script>

</body>

</html>

<?php

$stmt->close();

?>