<?php

session_start();

require_once "config/database.php";

/*
|--------------------------------------------------------------------------
| Si ya existe una sesión iniciada
|--------------------------------------------------------------------------
*/

if (isset($_SESSION["usuario_id"])) {
    header("Location: index.php");
    exit;
}

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $usuario = trim($_POST["usuario"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($usuario) || empty($password)) {

        $mensaje = "Debes ingresar usuario y contraseña.";

    } else {

        $sql = "SELECT id, nombre, usuario, password, rol
                FROM usuarios
                WHERE usuario = ? AND estado = 1
                LIMIT 1";

        $stmt = $conexion->prepare($sql);

        if ($stmt) {

            $stmt->bind_param("s", $usuario);

            if ($stmt->execute()) {

                $resultado = $stmt->get_result();

                if ($resultado->num_rows === 1) {

                    $usuarioDB = $resultado->fetch_assoc();

                    if (password_verify($password, $usuarioDB["password"])) {

                        /*
                        | Regenerar el ID de sesión después de autenticar
                        | correctamente para evitar session fixation.
                        */
                        session_regenerate_id(true);

                        $_SESSION["usuario_id"] = $usuarioDB["id"];
                        $_SESSION["nombre"] = $usuarioDB["nombre"];
                        $_SESSION["usuario"] = $usuarioDB["usuario"];
                        $_SESSION["rol"] = $usuarioDB["rol"];

                        header("Location: index.php");
                        exit;

                    } else {

                        $mensaje = "Usuario o contraseña incorrectos.";

                    }

                } else {

                    $mensaje = "Usuario o contraseña incorrectos.";

                }

            } else {

                $mensaje = "Ocurrió un error al iniciar sesión.";

            }

            $stmt->close();

        } else {

            $mensaje = "Ocurrió un error al iniciar sesión.";

        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Iniciar sesión | Inventario</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        body {
            min-height: 100vh;
            background: #f4f4f8;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
        }

        .login-card {
            background: white;
            border: none;
            border-radius: 15px;
            padding: 35px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.10);
        }

        .logo {
            width: 70px;
            height: 70px;
            background: #6f42c1;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 30px;
            font-weight: bold;
        }

        .btn-login {
            background: #6f42c1;
            border: none;
            padding: 12px;
            font-weight: 600;
        }

        .btn-login:hover {
            background: #59339d;
        }

    </style>

</head>

<body>

    <div class="login-container">

        <div class="login-card">

            <div class="logo">
                I
            </div>

            <h2 class="text-center mb-2">
                Sistema de Inventario
            </h2>

            <p class="text-center text-muted mb-4">
                Inicia sesión para continuar
            </p>

            <?php if (!empty($mensaje)): ?>

                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>

            <?php endif; ?>

            <form method="POST" action="">

                <div class="mb-3">

                    <label for="usuario" class="form-label">
                        Usuario
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="usuario"
                        name="usuario"
                        placeholder="Ingresa tu usuario"
                        required
                    >

                </div>

                <div class="mb-4">

                    <label for="password" class="form-label">
                        Contraseña
                    </label>

                    <input
                        type="password"
                        class="form-control"
                        id="password"
                        name="password"
                        placeholder="Ingresa tu contraseña"
                        required
                    >

                </div>

                <button
                    type="submit"
                    class="btn btn-primary btn-login w-100"
                >
                    Iniciar sesión
                </button>

            </form>

        </div>

    </div>

</body>

</html>