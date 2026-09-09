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

        <!-- CSS GENERAL DEL SISTEMA -->

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
                        No tienes permisos para administrar usuarios.
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

    <script>
document.addEventListener("DOMContentLoaded", function () {

    const alerta = document.querySelector(".alert.alert-success");

    if (!alerta) {
        return;
    }

    const botonCerrar = alerta.querySelector(".btn-close");

    if (botonCerrar) {
        botonCerrar.addEventListener("click", function () {
            alerta.remove();
        });
    }

    setTimeout(function () {
        alerta.remove();
    }, 4000);

});
</script>

</body>

    </html>

    <?php

    exit;

}


// =====================================================
// FILTROS
// =====================================================

$busqueda = trim($_GET["busqueda"] ?? "");

$rol = $_GET["rol"] ?? "";

$estado = $_GET["estado"] ?? "";


// =====================================================
// VALIDAR ROL
// =====================================================

$rolesPermitidos = [
    "",
    "admin",
    "vendedor"
];

if (!in_array($rol, $rolesPermitidos, true)) {

    $rol = "";

}


// =====================================================
// VALIDAR ESTADO
// =====================================================

$estadosPermitidos = [
    "",
    "1",
    "0"
];

if (!in_array($estado, $estadosPermitidos, true)) {

    $estado = "";

}


// =====================================================
// CONSULTA
// =====================================================

$sql = "
    SELECT
        id,
        nombre,
        usuario,
        rol,
        estado,
        creado_en
    FROM usuarios
    WHERE 1=1
";

$tipos = "";

$parametros = [];


// =====================================================
// BUSCAR POR NOMBRE O USUARIO
// =====================================================

if ($busqueda !== "") {

    $sql .= "
        AND (
            nombre LIKE ?
            OR usuario LIKE ?
        )
    ";

    $valorBusqueda = "%" . $busqueda . "%";

    $tipos .= "ss";

    $parametros[] = $valorBusqueda;
    $parametros[] = $valorBusqueda;

}


// =====================================================
// FILTRAR POR ROL
// =====================================================

if ($rol !== "") {

    $sql .= "
        AND rol = ?
    ";

    $tipos .= "s";

    $parametros[] = $rol;

}


// =====================================================
// FILTRAR POR ESTADO
// =====================================================

if ($estado !== "") {

    $sql .= "
        AND estado = ?
    ";

    $tipos .= "i";

    $parametros[] = (int) $estado;

}


// =====================================================
// ORDEN
// =====================================================

$sql .= "
    ORDER BY id DESC
";


// =====================================================
// PREPARAR
// =====================================================

$stmt = $conexion->prepare($sql);

if (!$stmt) {

    die(
        "Error al preparar la consulta: "
        . $conexion->error
    );

}


// =====================================================
// PARÁMETROS
// =====================================================

if (!empty($parametros)) {

    $stmt->bind_param(
        $tipos,
        ...$parametros
    );

}


// =====================================================
// EJECUTAR
// =====================================================

$stmt->execute();

$resultado = $stmt->get_result();

$totalUsuarios = $resultado->num_rows;

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Usuarios | Inventario</title>


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


    <!-- =================================================
         CSS GENERAL DEL SISTEMA
         LOS ESTILOS NO ESTÁN AQUÍ
    ================================================== -->

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

// =====================================================
// MENSAJES DE CONFIRMACIÓN
// =====================================================

$mensaje = $_GET["mensaje"] ?? "";

$mensajes = [
    "usuario_creado" => "Usuario creado correctamente.",
    "usuario_editado" => "Usuario actualizado correctamente.",
    "usuario_desactivado" => "Usuario desactivado correctamente.",
    "usuario_ya_inactivo" => "El usuario ya se encuentra inactivo."
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


<div class="container-fluid px-4 py-4">


    <!-- =================================================
         ENCABEZADO
    ================================================== -->

    <div
        class="d-flex justify-content-between align-items-center mb-4"
    >

        <div>

            <h2 class="mb-1">

                <i class="bi bi-people"></i>

                Usuarios

            </h2>

            <p class="text-muted mb-0">

                Gestiona los usuarios y sus permisos de acceso.

            </p>

        </div>


        <a
            href="crear.php"
            class="btn btn-primary"
        >

            <i class="bi bi-person-plus"></i>

            Nuevo usuario

        </a>

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


                    <!-- BUSCAR -->

                    <div class="col-md-5">

                        <label
                            for="busqueda"
                            class="form-label"
                        >

                            <i class="bi bi-search"></i>

                            Buscar

                        </label>

                        <input
                            type="text"
                            name="busqueda"
                            id="busqueda"
                            class="form-control"
                            placeholder="Buscar por nombre o usuario..."
                            value="<?php echo htmlspecialchars($busqueda); ?>"
                        >

                    </div>


                    <!-- ROL -->

                    <div class="col-md-3">

                        <label
                            for="rol"
                            class="form-label"
                        >

                            <i class="bi bi-person-badge"></i>

                            Rol

                        </label>

                        <select
                            name="rol"
                            id="rol"
                            class="form-select"
                        >

                            <option value="">
                                Todos
                            </option>

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

                    </div>


                    <!-- ESTADO -->

                    <div class="col-md-2">

                        <label
                            for="estado"
                            class="form-label"
                        >

                            <i class="bi bi-toggle-on"></i>

                            Estado

                        </label>

                        <select
                            name="estado"
                            id="estado"
                            class="form-select"
                        >

                            <option value="">
                                Todos
                            </option>

                            <option
                                value="1"
                                <?php
                                echo $estado === "1"
                                    ? "selected"
                                    : "";
                                ?>
                            >
                                Activos
                            </option>

                            <option
                                value="0"
                                <?php
                                echo $estado === "0"
                                    ? "selected"
                                    : "";
                                ?>
                            >
                                Inactivos
                            </option>

                        </select>

                    </div>


                    <!-- BOTONES -->

                    <div class="col-md-2">

                        <div class="d-flex gap-2">


                            <!-- BUSCAR -->

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >

                                <i class="bi bi-search"></i>

                                Buscar

                            </button>


                            <!-- LIMPIAR -->

                            <a
                                href="listar.php"
                                class="btn btn-outline-secondary"
                                title="Limpiar filtros"
                            >

                                <i
                                    class="bi bi-arrow-counterclockwise"
                                ></i>

                            </a>

                        </div>

                    </div>

                </div>

            </form>

        </div>

    </div>


    <!-- =================================================
         TABLA
    ================================================== -->

    <div class="card">

        <div class="card-body">


            <div class="mb-3">

                <h5 class="mb-1">

                    <i class="bi bi-person-lines-fill"></i>

                    Usuarios registrados

                </h5>

                <small class="text-muted">

                    <?php echo $totalUsuarios; ?>

                    <?php

                    echo $totalUsuarios === 1
                        ? " usuario encontrado"
                        : " usuarios encontrados";

                    ?>

                </small>

            </div>


            <!-- TABLA -->

            <div class="table-responsive">

                <table class="table table-hover align-middle">


                    <thead>

                        <tr>

                            <th>ID</th>

                            <th>Nombre</th>

                            <th>Usuario</th>

                            <th>Rol</th>

                            <th>Estado</th>

                            <th>Fecha de creación</th>

                            <th>Acciones</th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if ($resultado->num_rows > 0): ?>


                        <?php while (
                            $usuario = $resultado->fetch_assoc()
                        ): ?>


                            <tr>


                                <!-- ID -->

                                <td>

                                    <strong>

                                        #

                                        <?php
                                        echo (int) $usuario["id"];
                                        ?>

                                    </strong>

                                </td>


                                <!-- NOMBRE -->

                                <td>

                                    <strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $usuario["nombre"]
                                        );
                                        ?>

                                    </strong>

                                </td>


                                <!-- USUARIO -->

                                <td>

                                    <span class="text-muted">

                                        <i class="bi bi-person"></i>

                                        <?php
                                        echo htmlspecialchars(
                                            $usuario["usuario"]
                                        );
                                        ?>

                                    </span>

                                </td>


                                <!-- ROL -->

                                <td>

                                    <?php if (
                                        $usuario["rol"] === "admin"
                                    ): ?>

                                        <span
                                            class="badge text-bg-primary"
                                        >

                                            <i
                                                class="bi bi-shield-check"
                                            ></i>

                                            Administrador

                                        </span>

                                    <?php else: ?>

                                        <span
                                            class="badge text-bg-info"
                                        >

                                            <i
                                                class="bi bi-person"
                                            ></i>

                                            Vendedor

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- ESTADO -->

                                <td>

                                    <?php if (
                                        (int) $usuario["estado"] === 1
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


                                <!-- FECHA -->

                                <td>

                                    <?php

                                    echo date(
                                        "d/m/Y H:i",
                                        strtotime(
                                            $usuario["creado_en"]
                                        )
                                    );

                                    ?>

                                </td>


                                <!-- ACCIONES -->

                                <td>

                                    <div
                                        class="d-flex gap-1"
                                    >


                                        <!-- EDITAR -->

                                        <a
                                            href="editar.php?id=<?php echo (int) $usuario["id"]; ?>"
                                            class="btn btn-sm btn-warning"
                                            title="Editar usuario"
                                        >

                                            <i
                                                class="bi bi-pencil"
                                            ></i>

                                        </a>


                                        <!-- DESACTIVAR -->

                                        <?php if (
                                            (int) $usuario["id"]
                                            !==
                                            (int) $_SESSION["usuario_id"]
                                        ): ?>

                                            <a
                                                href="eliminar.php?id=<?php echo (int) $usuario["id"]; ?>"
                                                class="btn btn-sm btn-danger"
                                                title="Desactivar usuario"
                                                onclick="return confirm('¿Estás segura de desactivar este usuario?');"
                                            >

                                                <i
                                                    class="bi bi-person-x"
                                                ></i>

                                            </a>

                                        <?php else: ?>

                                            <!-- USUARIO ACTUAL -->

                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                title="No puedes desactivar tu propio usuario"
                                                disabled
                                            >

                                                <i
                                                    class="bi bi-lock"
                                                ></i>

                                            </button>

                                        <?php endif; ?>


                                    </div>

                                </td>


                            </tr>


                        <?php endwhile; ?>


                    <?php else: ?>


                        <!-- SIN RESULTADOS -->

                        <tr>

                            <td
                                colspan="7"
                                class="text-center py-5"
                            >

                                <i
                                    class="bi bi-search fs-1 text-muted"
                                ></i>

                                <h5 class="mt-3">

                                    No se encontraron usuarios

                                </h5>

                                <p class="text-muted mb-3">

                                    No existen usuarios que coincidan
                                    con los filtros seleccionados.

                                </p>

                                <a
                                    href="listar.php"
                                    class="btn btn-outline-primary"
                                >

                                    <i
                                        class="bi bi-arrow-counterclockwise"
                                    ></i>

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


<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

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