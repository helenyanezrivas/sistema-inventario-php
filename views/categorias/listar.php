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

// Generar token CSRF
csrf_token();


// =====================================================
// OBTENER CATEGORÍAS Y CANTIDAD DE PRODUCTOS
// =====================================================

$sql = "

    SELECT

        c.id,
        c.nombre,
        c.estado,
        c.creado_en,
        COUNT(p.id) AS cantidad_productos

    FROM categorias c

    LEFT JOIN productos p
        ON c.id = p.categoria_id

    GROUP BY

        c.id,
        c.nombre,
        c.estado,
        c.creado_en

    ORDER BY c.id DESC

";

$resultado = $conexion->query($sql);

if (!$resultado) {

    die(
        "Error al obtener las categorías: " .
        htmlspecialchars($conexion->error)
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

    <title>Categorías | Inventario</title>

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


<!-- =====================================================
     CONTENIDO
===================================================== -->

<div class="container-fluid px-4 py-4">


    <!-- TÍTULO Y BOTÓN -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2>

                <i class="bi bi-tags"></i>

                Categorías

            </h2>

            <p class="text-muted mb-0">

                Gestiona las categorías de tus productos.

            </p>

        </div>


        <a
            href="crear.php"
            class="btn btn-primary"
        >

            <i class="bi bi-plus-lg"></i>

            Nueva categoría

        </a>

    </div>


    <!-- =================================================
         TABLA
    ================================================== -->

    <div class="card">

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead>

                        <tr>

                            <th>ID</th>

                            <th>Nombre</th>

                            <th>Productos</th>

                            <th>Estado</th>

                            <th>Fecha de creación</th>

                            <th>Acciones</th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if ($resultado->num_rows > 0): ?>


                        <?php while ($categoria = $resultado->fetch_assoc()): ?>


                            <tr>


                                <!-- ID -->

                                <td>

                                    <?php

                                    echo (int) $categoria["id"];

                                    ?>

                                </td>


                                <!-- NOMBRE -->

                                <td>

                                    <strong>

                                        <?php

                                        echo htmlspecialchars(
                                            $categoria["nombre"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );

                                        ?>

                                    </strong>

                                </td>


                                <!-- PRODUCTOS -->

                                <td>

                                    <span class="badge text-bg-primary">

                                        <?php

                                        echo (int) $categoria["cantidad_productos"];

                                        ?>

                                    </span>

                                </td>


                                <!-- ESTADO -->

                                <td>

                                    <?php if ($categoria["estado"] == 1): ?>

                                        <span class="badge text-bg-success">

                                            Activa

                                        </span>

                                    <?php else: ?>

                                        <span class="badge text-bg-secondary">

                                            Inactiva

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- FECHA -->

                                <td>

                                    <?php

                                    echo date(
                                        "d/m/Y H:i",
                                        strtotime(
                                            $categoria["creado_en"]
                                        )
                                    );

                                    ?>

                                </td>


                                <!-- ACCIONES -->

                                <td>


                                    <!-- EDITAR -->

                                    <a
                                        href="editar.php?id=<?php echo (int) $categoria["id"]; ?>"
                                        class="btn btn-sm btn-warning"
                                        title="Editar"
                                    >

                                        <i class="bi bi-pencil"></i>

                                    </a>


                                    <!-- ELIMINAR -->

                                    <form
                                        method="POST"
                                        action="eliminar.php"
                                        class="d-inline"
                                        onsubmit="return confirm('¿Estás segura de eliminar esta categoría?');"
                                    >

                                        <?php echo csrf_field(); ?>

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?php echo (int) $categoria["id"]; ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-danger"
                                            title="Eliminar"
                                        >

                                            <i class="bi bi-trash"></i>

                                        </button>

                                    </form>


                                </td>


                            </tr>


                        <?php endwhile; ?>


                    <?php else: ?>


                        <!-- SIN CATEGORÍAS -->

                        <tr>

                            <td
                                colspan="6"
                                class="text-center py-5"
                            >

                                <i
                                    class="bi bi-tags fs-1 text-muted"
                                ></i>

                                <p class="text-muted mt-3 mb-0">

                                    No hay categorías registradas.

                                </p>

                            </td>

                        </tr>


                    <?php endif; ?>


                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>


</body>

</html>