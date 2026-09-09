<?php

$baseUrl = "/inventario";

?>

<nav class="navbar navbar-dark">

    <div class="container-fluid px-4">

        <a
            href="<?php echo $baseUrl; ?>/index.php"
            class="navbar-brand fw-bold"
        >
            <i class="bi bi-box-seam"></i>
            Sistema de Inventario
        </a>

        <div class="d-flex align-items-center gap-2">

            <a href="<?php echo $baseUrl; ?>/index.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-speedometer2"></i>
                Dashboard
            </a>

            <a href="<?php echo $baseUrl; ?>/views/productos/listar.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-box-seam"></i>
                Productos
            </a>

            <a href="<?php echo $baseUrl; ?>/views/categorias/listar.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-tags"></i>
                Categorías
            </a>

            <a href="<?php echo $baseUrl; ?>/views/ventas/listar.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-cart-check"></i>
                Ventas
            </a>

            <?php if (isset($_SESSION["rol"]) && $_SESSION["rol"] === "admin"): ?>

                <a href="<?php echo $baseUrl; ?>/views/usuarios/listar.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-people"></i>
                    Usuarios
                </a>

            <?php endif; ?>

            <span class="text-white ms-2">
                <i class="bi bi-person-circle"></i>
                <?php
                echo htmlspecialchars($_SESSION["nombre"] ?? "Usuario");
                ?>
            </span>

            <a href="<?php echo $baseUrl; ?>/logout.php" class="btn btn-light btn-sm ms-1">
                <i class="bi bi-box-arrow-right"></i>
                Cerrar sesión
            </a>

        </div>

    </div>

</nav>