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

$error = "";

// =====================================================
// OBTENER PRODUCTOS ACTIVOS
// =====================================================

$sql_productos = "SELECT
                    id,
                    codigo,
                    nombre,
                    precio_venta,
                    stock
                  FROM productos
                  WHERE estado = 1
                  ORDER BY nombre ASC";

$resultado_productos = $conexion->query($sql_productos);

if (!$resultado_productos) {
    die("Error al obtener los productos: " . $conexion->error);
}


// =====================================================
// PROCESAR VENTA
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    verificar_csrf();

    $productos = isset($_POST["productos"])
        ? $_POST["productos"]
        : [];

    // Validar que exista al menos un producto
    if (!is_array($productos) || count($productos) === 0) {

        $error = "Debes agregar al menos un producto a la venta.";

    } else {

        try {

            // =================================================
            // INICIAR TRANSACCIÓN
            // =================================================

            $conexion->begin_transaction();


            $usuario_id = (int) $_SESSION["usuario_id"];

            $total_venta = 0;

            $productos_procesados = [];


            // =================================================
            // VALIDAR PRODUCTOS Y STOCK
            // =================================================

            foreach ($productos as $producto_id => $cantidad) {

                $producto_id = (int) $producto_id;
                $cantidad = (int) $cantidad;


                if ($producto_id <= 0 || $cantidad <= 0) {
                    continue;
                }


                // Buscar y bloquear producto
                $sql_producto = "SELECT
                                    id,
                                    nombre,
                                    precio_venta,
                                    stock
                                 FROM productos
                                 WHERE id = ?
                                 AND estado = 1
                                 FOR UPDATE";

                $stmt_producto =
                    $conexion->prepare($sql_producto);


                if (!$stmt_producto) {

                    throw new Exception(
                        "Error al preparar la consulta del producto."
                    );
                }


                $stmt_producto->bind_param(
                    "i",
                    $producto_id
                );

                $stmt_producto->execute();

                $resultado_producto =
                    $stmt_producto->get_result();


                if ($resultado_producto->num_rows === 0) {

                    $stmt_producto->close();

                    throw new Exception(
                        "Uno de los productos seleccionados no existe o está inactivo."
                    );
                }


                $producto =
                    $resultado_producto->fetch_assoc();

                $stmt_producto->close();


                // Verificar stock
                if ($cantidad > $producto["stock"]) {

                    throw new Exception(
                        "Stock insuficiente para "
                        . $producto["nombre"]
                        . ". Stock disponible: "
                        . $producto["stock"]
                    );
                }


                // Calcular subtotal
                $precio =
                    (float) $producto["precio_venta"];

                $subtotal =
                    $precio * $cantidad;


                $total_venta += $subtotal;


                // Guardar producto procesado
                $productos_procesados[] = [
                    "id" => $producto_id,
                    "nombre" => $producto["nombre"],
                    "cantidad" => $cantidad,
                    "precio" => $precio,
                    "subtotal" => $subtotal,
                    "stock" => (int) $producto["stock"]
                ];
            }


            // Verificar que realmente haya productos válidos
            if (count($productos_procesados) === 0) {

                throw new Exception(
                    "Debes agregar al menos un producto válido."
                );
            }


            // =================================================
            // CREAR CABECERA DE LA VENTA
            // =================================================

            $sql_venta = "INSERT INTO ventas
                            (usuario_id, total)
                          VALUES
                            (?, ?)";

            $stmt_venta =
                $conexion->prepare($sql_venta);


            if (!$stmt_venta) {

                throw new Exception(
                    "Error al preparar el registro de la venta."
                );
            }


            $stmt_venta->bind_param(
                "id",
                $usuario_id,
                $total_venta
            );


            if (!$stmt_venta->execute()) {

                throw new Exception(
                    "Error al registrar la venta: "
                    . $stmt_venta->error
                );
            }


            $venta_id =
                $conexion->insert_id;


            $stmt_venta->close();


            // =================================================
            // INSERTAR DETALLES Y DESCONTAR STOCK
            // =================================================

            foreach ($productos_procesados as $item) {

                // ---------------------------------------------
                // INSERTAR DETALLE
                // ---------------------------------------------

                $sql_detalle = "INSERT INTO detalle_ventas
                                    (
                                        venta_id,
                                        producto_id,
                                        cantidad,
                                        precio,
                                        subtotal
                                    )
                                VALUES
                                    (?, ?, ?, ?, ?)";

                $stmt_detalle =
                    $conexion->prepare($sql_detalle);


                if (!$stmt_detalle) {

                    throw new Exception(
                        "Error al preparar el detalle de la venta."
                    );
                }


                $stmt_detalle->bind_param(
                    "iiidd",
                    $venta_id,
                    $item["id"],
                    $item["cantidad"],
                    $item["precio"],
                    $item["subtotal"]
                );


                if (!$stmt_detalle->execute()) {

                    throw new Exception(
                        "Error al registrar el detalle: "
                        . $stmt_detalle->error
                    );
                }


                $stmt_detalle->close();


                // ---------------------------------------------
                // DESCONTAR STOCK
                // ---------------------------------------------

                $nuevo_stock =
                    $item["stock"] - $item["cantidad"];


                $sql_stock = "UPDATE productos
                              SET stock = ?
                              WHERE id = ?";

                $stmt_stock =
                    $conexion->prepare($sql_stock);


                if (!$stmt_stock) {

                    throw new Exception(
                        "Error al preparar la actualización del stock."
                    );
                }


                $stmt_stock->bind_param(
                    "ii",
                    $nuevo_stock,
                    $item["id"]
                );


                if (!$stmt_stock->execute()) {

                    throw new Exception(
                        "Error al actualizar el stock: "
                        . $stmt_stock->error
                    );
                }


                $stmt_stock->close();
            }


            // =================================================
            // CONFIRMAR TRANSACCIÓN
            // =================================================

            $conexion->commit();


            // Volver al listado
            header(
                "Location: listar.php?mensaje=venta_registrada"
            );

            exit;


        } catch (Exception $e) {

            // Deshacer todos los cambios
            $conexion->rollback();

            $error = $e->getMessage();
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

    <title>Nueva venta | Inventario</title>


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

<div class="container-fluid px-4 py-4">


    <!-- ENCABEZADO -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2>

                <i class="bi bi-cart-plus"></i>

                Nueva venta

            </h2>


            <p class="text-muted mb-0">

                Agrega uno o varios productos a la venta.

            </p>

        </div>


        <a
            href="listar.php"
            class="btn btn-secondary"
        >

            <i class="bi bi-arrow-left"></i>

            Volver

        </a>

    </div>


    <!-- ERROR -->

    <?php if ($error): ?>

        <div class="alert alert-danger">

            <i class="bi bi-exclamation-triangle"></i>

            <?php
            echo htmlspecialchars($error);
            ?>

        </div>

    <?php endif; ?>


    <!-- =================================================
         SELECCIÓN DE PRODUCTOS
    ================================================== -->

    <div class="card form-card mb-4">

        <div class="card-body">

            <h5 class="mb-4">

                <i class="bi bi-box-seam"></i>

                Agregar productos

            </h5>


            <div class="row g-3">


                <!-- PRODUCTO -->

                <div class="col-md-6">

                    <label
                        for="producto_id"
                        class="form-label"
                    >

                        <strong>Producto</strong>

                        <span class="required">*</span>

                    </label>


                    <select
                        id="producto_id"
                        class="form-select"
                    >

                        <option value="">

                            Selecciona un producto

                        </option>


                        <?php
                        if (
                            $resultado_productos &&
                            $resultado_productos->num_rows > 0
                        ):
                        ?>

                            <?php
                            while (
                                $producto_lista =
                                $resultado_productos->fetch_assoc()
                            ):
                            ?>

                                <option
                                    value="<?php echo $producto_lista["id"]; ?>"
                                    data-nombre="<?php echo htmlspecialchars($producto_lista["nombre"], ENT_QUOTES); ?>"
                                    data-codigo="<?php echo htmlspecialchars($producto_lista["codigo"], ENT_QUOTES); ?>"
                                    data-precio="<?php echo $producto_lista["precio_venta"]; ?>"
                                    data-stock="<?php echo $producto_lista["stock"]; ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $producto_lista["nombre"]
                                    );
                                    ?>

                                    -

                                    $

                                    <?php
                                    echo number_format(
                                        $producto_lista["precio_venta"],
                                        0,
                                        ",",
                                        "."
                                    );
                                    ?>

                                    - Stock:

                                    <?php
                                    echo $producto_lista["stock"];
                                    ?>

                                </option>

                            <?php endwhile; ?>

                        <?php endif; ?>

                    </select>

                </div>


                <!-- CANTIDAD -->

                <div class="col-md-3">

                    <label
                        for="cantidad"
                        class="form-label"
                    >

                        <strong>Cantidad</strong>

                        <span class="required">*</span>

                    </label>


                    <input
                        type="number"
                        id="cantidad"
                        class="form-control"
                        min="1"
                        value="1"
                    >

                </div>


                <!-- BOTÓN -->

                <div class="col-md-3 d-flex align-items-end">

                    <button
                        type="button"
                        id="btnAgregar"
                        class="btn btn-primary w-100"
                    >

                        <i class="bi bi-plus-lg"></i>

                        Agregar

                    </button>

                </div>

            </div>


            <!-- INFORMACIÓN DEL PRODUCTO -->

            <div
                id="informacionProducto"
                class="alert alert-light border mt-3 d-none"
            >

                <div class="row">

                    <div class="col-md-6">

                        <strong>
                            Precio unitario:
                        </strong>

                        <span id="precioProducto">
                            $0
                        </span>

                    </div>


                    <div class="col-md-6">

                        <strong>
                            Stock disponible:
                        </strong>

                        <span id="stockProducto">
                            0
                        </span>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- =================================================
         CARRITO / PRODUCTOS AGREGADOS
    ================================================== -->

    <div class="card form-card">

        <div class="card-body">

            <h5 class="mb-4">

                <i class="bi bi-cart3"></i>

                Productos de la venta

            </h5>


            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead>

                        <tr>

                            <th>Código</th>

                            <th>Producto</th>

                            <th class="text-center">
                                Cantidad
                            </th>

                            <th class="text-end">
                                Precio
                            </th>

                            <th class="text-end">
                                Subtotal
                            </th>

                            <th class="text-center">
                                Acción
                            </th>

                        </tr>

                    </thead>


                    <tbody id="tablaProductos">

                        <tr id="filaVacia">

                            <td
                                colspan="6"
                                class="text-center py-5 text-muted"
                            >

                                <i class="bi bi-cart-x fs-1"></i>

                                <p class="mt-3 mb-0">

                                    No hay productos agregados.

                                </p>

                                <small>

                                    Selecciona un producto
                                    arriba para comenzar.

                                </small>

                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>


            <!-- TOTAL -->

            <div class="d-flex justify-content-end mt-4">

                <div class="text-end">

                    <div class="text-muted">
                        Total de la venta
                    </div>


                    <div
                        id="totalVenta"
                        class="fs-2 fw-bold"
                    >

                        $0

                    </div>

                </div>

            </div>


            <!-- FORMULARIO REAL -->

            <form
                method="POST"
                id="formVenta"
            >

                <?php echo csrf_field(); ?>

                <div id="inputsProductos"></div>


                <div class="d-flex justify-content-end gap-2 mt-4">

                    <a
                        href="listar.php"
                        class="btn btn-secondary"
                    >

                        Cancelar

                    </a>


                    <button
                        type="submit"
                        class="btn btn-primary"
                        id="btnRegistrar"
                        disabled
                    >

                        <i class="bi bi-check-circle"></i>

                        Registrar venta

                    </button>

                </div>

            </form>

        </div>

    </div>


</div>


<script>

// =====================================================
// ELEMENTOS
// =====================================================

const productoSelect =
    document.getElementById("producto_id");

const cantidadInput =
    document.getElementById("cantidad");

const btnAgregar =
    document.getElementById("btnAgregar");

const informacionProducto =
    document.getElementById("informacionProducto");

const precioProducto =
    document.getElementById("precioProducto");

const stockProducto =
    document.getElementById("stockProducto");

const tablaProductos =
    document.getElementById("tablaProductos");

const filaVacia =
    document.getElementById("filaVacia");

const totalVenta =
    document.getElementById("totalVenta");

const inputsProductos =
    document.getElementById("inputsProductos");

const btnRegistrar =
    document.getElementById("btnRegistrar");


// =====================================================
// CARRITO
// =====================================================

let carrito = {};


// =====================================================
// MOSTRAR INFORMACIÓN DEL PRODUCTO
// =====================================================

productoSelect.addEventListener("change", function () {

    const opcion =
        productoSelect.options[
            productoSelect.selectedIndex
        ];


    if (!opcion || !opcion.value) {

        informacionProducto.classList.add("d-none");

        precioProducto.textContent = "$0";

        stockProducto.textContent = "0";

        return;
    }


    const precio =
        parseFloat(opcion.dataset.precio);

    const stock =
        parseInt(opcion.dataset.stock);


    precioProducto.textContent =
        "$" + precio.toLocaleString("es-CL");

    stockProducto.textContent =
        stock;

    informacionProducto.classList.remove("d-none");

});


// =====================================================
// AGREGAR PRODUCTO
// =====================================================

btnAgregar.addEventListener("click", function () {

    const opcion =
        productoSelect.options[
            productoSelect.selectedIndex
        ];


    if (!opcion || !opcion.value) {

        alert("Debes seleccionar un producto.");

        return;
    }


    const productoId =
        opcion.value;

    const nombre =
        opcion.dataset.nombre;

    const codigo =
        opcion.dataset.codigo;

    const precio =
        parseFloat(opcion.dataset.precio);

    const stock =
        parseInt(opcion.dataset.stock);


    let cantidad =
        parseInt(cantidadInput.value);


    if (isNaN(cantidad) || cantidad <= 0) {

        alert("La cantidad debe ser mayor que 0.");

        return;
    }


    // Cantidad que ya existe en el carrito
    const cantidadActual =
        carrito[productoId]
            ? carrito[productoId].cantidad
            : 0;


    const nuevaCantidad =
        cantidadActual + cantidad;


    // Verificar stock
    if (nuevaCantidad > stock) {

        alert(
            "Stock insuficiente. " +
            "Stock disponible: " +
            stock
        );

        return;
    }


    // Guardar producto
    carrito[productoId] = {

        id: productoId,

        codigo: codigo,

        nombre: nombre,

        precio: precio,

        stock: stock,

        cantidad: nuevaCantidad

    };


    renderizarCarrito();


    // Reiniciar selección
    productoSelect.value = "";

    cantidadInput.value = 1;

    informacionProducto.classList.add("d-none");

    precioProducto.textContent = "$0";

    stockProducto.textContent = "0";

});


// =====================================================
// RENDERIZAR CARRITO
// =====================================================

function renderizarCarrito() {

    tablaProductos.innerHTML = "";

    inputsProductos.innerHTML = "";


    const productos =
        Object.values(carrito);


    if (productos.length === 0) {

        tablaProductos.appendChild(filaVacia);

        totalVenta.textContent = "$0";

        btnRegistrar.disabled = true;

        return;
    }


    let total = 0;


    productos.forEach(function (producto) {

        const subtotal =
            producto.precio * producto.cantidad;


        total += subtotal;


        const fila =
            document.createElement("tr");


        fila.innerHTML = `

            <td>
                ${escapeHtml(producto.codigo)}
            </td>

            <td>
                <i class="bi bi-box"></i>
                ${escapeHtml(producto.nombre)}
            </td>

            <td class="text-center">
                ${producto.cantidad}
            </td>

            <td class="text-end">
                $${producto.precio.toLocaleString("es-CL")}
            </td>

            <td class="text-end fw-bold">
                $${subtotal.toLocaleString("es-CL")}
            </td>

            <td class="text-center">

                <button
                    type="button"
                    class="btn btn-sm btn-danger"
                    onclick="eliminarProducto('${producto.id}')"
                    title="Eliminar producto"
                >

                    <i class="bi bi-trash"></i>

                </button>

            </td>

        `;


        tablaProductos.appendChild(fila);


        // Crear input oculto
        const input =
            document.createElement("input");

        input.type = "hidden";

        input.name =
            "productos[" + producto.id + "]";

        input.value =
            producto.cantidad;

        inputsProductos.appendChild(input);

    });


    totalVenta.textContent =
        "$" + total.toLocaleString("es-CL");


    btnRegistrar.disabled = false;

}


// =====================================================
// ELIMINAR PRODUCTO
// =====================================================

function eliminarProducto(productoId) {

    delete carrito[productoId];

    renderizarCarrito();

}


// =====================================================
// PROTEGER TEXTO HTML
// =====================================================

function escapeHtml(texto) {

    const div =
        document.createElement("div");

    div.textContent =
        texto;

    return div.innerHTML;

}

</script>


</body>

</html>