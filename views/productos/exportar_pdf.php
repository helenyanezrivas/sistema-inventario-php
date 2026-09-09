<?php

session_start();

require_once "../../config/database.php";
require_once "../../vendor/autoload.php";

use Dompdf\Dompdf;
use Dompdf\Options;


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
// PREPARAR CONSULTA
// =====================================================

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    die("Error al preparar la consulta: " . $conexion->error);
}


// =====================================================
// ASIGNAR PARÁMETROS
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

if (!$stmt->execute()) {
    die("Error al obtener los productos: " . $stmt->error);
}

$resultado = $stmt->get_result();


// =====================================================
// CREAR DOMPDF
// =====================================================

$options = new Options();

$options->set("isRemoteEnabled", true);
$options->set("defaultFont", "DejaVu Sans");

$dompdf = new Dompdf($options);


// =====================================================
// CARGAR CSS EXTERNO
// =====================================================

$rutaCss = __DIR__ . "/../../assets/css/exportar_pdf.css";

if (file_exists($rutaCss)) {
    $css = file_get_contents($rutaCss);
} else {
    $css = "";
}


// =====================================================
// CREAR HTML
// =====================================================

$html = '
<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <title>Reporte de Productos</title>

    <style>
        ' . $css . '
    </style>

</head>

<body>

    <div class="encabezado">

        <div class="titulo">
            Sistema de Inventario
        </div>

        <div class="subtitulo">
            Reporte de productos
        </div>

        <div class="fecha">
            Fecha de emisión: ' . date("d/m/Y H:i") . '
        </div>

    </div>


    <div class="resumen">

        <strong>Total de productos:</strong>
        ' . $resultado->num_rows . '

    </div>


    <table>

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

            </tr>

        </thead>

        <tbody>
';


// =====================================================
// PRODUCTOS
// =====================================================

while ($producto = $resultado->fetch_assoc()) {

    $estado = ((int) $producto["estado"] === 1)
        ? "Activo"
        : "Inactivo";


    if ((int) $producto["stock"] === 0) {

        $stockTexto = "Agotado";

    } elseif (
        (int) $producto["stock"] <=
        (int) $producto["stock_minimo"]
    ) {

        $stockTexto =
            $producto["stock"] . " bajo";

    } else {

        $stockTexto =
            $producto["stock"] . " disponibles";
    }


    $html .= '

            <tr>

                <td>
                    ' . htmlspecialchars(
                        $producto["id"],
                        ENT_QUOTES,
                        "UTF-8"
                    ) . '
                </td>

                <td>
                    ' . htmlspecialchars(
                        $producto["codigo"],
                        ENT_QUOTES,
                        "UTF-8"
                    ) . '
                </td>

                <td>
                    <strong>
                        ' . htmlspecialchars(
                            $producto["nombre"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) . '
                    </strong>
    ';


    if (!empty($producto["descripcion"])) {

        $html .= '
                    <br>

                    <span class="descripcion">
                        ' . htmlspecialchars(
                            $producto["descripcion"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) . '
                    </span>
        ';
    }


    $html .= '

                </td>

                <td>
                    ' . htmlspecialchars(
                        $producto["categoria"],
                        ENT_QUOTES,
                        "UTF-8"
                    ) . '
                </td>

                <td>
                    $' . number_format(
                        (float) $producto["precio_compra"],
                        0,
                        ",",
                        "."
                    ) . '
                </td>

                <td>
                    <strong>
                        $' . number_format(
                            (float) $producto["precio_venta"],
                            0,
                            ",",
                            "."
                        ) . '
                    </strong>
                </td>

                <td>
                    ' . htmlspecialchars(
                        $stockTexto,
                        ENT_QUOTES,
                        "UTF-8"
                    ) . '
                </td>

                <td>
                    ' . htmlspecialchars(
                        $estado,
                        ENT_QUOTES,
                        "UTF-8"
                    ) . '
                </td>

            </tr>

    ';
}


$html .= '

        </tbody>

    </table>


    <div class="pie">

        Sistema de Inventario - Reporte generado automáticamente

    </div>

</body>

</html>
';


// =====================================================
// GENERAR PDF
// =====================================================

$dompdf->loadHtml($html);

$dompdf->setPaper("A4", "landscape");

$dompdf->render();


// =====================================================
// DESCARGAR PDF
// =====================================================

$dompdf->stream(
    "reporte_productos.pdf",
    [
        "Attachment" => true
    ]
);


$stmt->close();

?>