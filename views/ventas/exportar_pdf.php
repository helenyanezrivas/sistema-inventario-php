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
// RECIBIR FILTROS
// =====================================================

$fechaDesde = $_GET["fecha_desde"] ?? "";

$fechaHasta = $_GET["fecha_hasta"] ?? "";

$usuarioFiltro = $_GET["usuario"] ?? "";

$estadoFiltro = $_GET["estado"] ?? "";


// =====================================================
// VALIDAR ESTADO
// =====================================================

$estadosPermitidos = [
    "",
    "realizada",
    "anulada"
];


if (!in_array($estadoFiltro, $estadosPermitidos, true)) {

    $estadoFiltro = "";

}


// =====================================================
// CONSULTA DE VENTAS
// =====================================================

$sql = "
    SELECT
        v.id,
        v.fecha,
        v.total,
        v.estado,
        u.nombre AS usuario
    FROM ventas v
    INNER JOIN usuarios u
        ON v.usuario_id = u.id
    WHERE 1 = 1
";


$tipos = "";

$parametros = [];


// =====================================================
// FILTRO FECHA DESDE
// =====================================================

if ($fechaDesde !== "") {

    $sql .= "
        AND DATE(v.fecha) >= ?
    ";

    $tipos .= "s";

    $parametros[] = $fechaDesde;

}


// =====================================================
// FILTRO FECHA HASTA
// =====================================================

if ($fechaHasta !== "") {

    $sql .= "
        AND DATE(v.fecha) <= ?
    ";

    $tipos .= "s";

    $parametros[] = $fechaHasta;

}


// =====================================================
// FILTRO USUARIO
// =====================================================

if (
    $usuarioFiltro !== ""
    && is_numeric($usuarioFiltro)
) {

    $sql .= "
        AND v.usuario_id = ?
    ";

    $tipos .= "i";

    $parametros[] =
        (int) $usuarioFiltro;

}


// =====================================================
// FILTRO ESTADO
// =====================================================

if ($estadoFiltro !== "") {

    $sql .= "
        AND v.estado = ?
    ";

    $tipos .= "s";

    $parametros[] =
        $estadoFiltro;

}


// =====================================================
// ORDEN
// =====================================================

$sql .= "
    ORDER BY v.id DESC
";


// =====================================================
// PREPARAR CONSULTA
// =====================================================

$stmt =
    $conexion->prepare($sql);


if (!$stmt) {

    die(
        "Error al preparar la consulta: "
        . $conexion->error
    );

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

    die(
        "Error al obtener las ventas: "
        . $stmt->error
    );

}


$resultado =
    $stmt->get_result();


// =====================================================
// CARGAR CSS EXTERNO
// =====================================================

$archivoCss =
    __DIR__
    . "/../../assets/css/exportar_pdf.css";


if (!file_exists($archivoCss)) {

    die(
        "No se encontró el archivo de estilos del PDF."
    );

}


$css =
    file_get_contents(
        $archivoCss
    );


// =====================================================
// DATOS DEL REPORTE
// =====================================================

$totalVentas =
    $resultado->num_rows;


$totalGeneral = 0;


// =====================================================
// TEXTO DEL FILTRO DE ESTADO
// =====================================================

if ($estadoFiltro === "realizada") {

    $textoEstado =
        "Realizadas";

} elseif ($estadoFiltro === "anulada") {

    $textoEstado =
        "Anuladas";

} else {

    $textoEstado =
        "Todas";

}


// =====================================================
// CREAR HTML
// =====================================================

$html = '

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <style>

        ' . $css . '

    </style>

</head>


<body>


    <div class="encabezado">


        <div class="titulo">

            SISTEMA DE INVENTARIO

        </div>


        <div class="subtitulo">

            REPORTE DE VENTAS

        </div>


        <div class="fecha">

            Fecha de generación:
            ' . date("d/m/Y H:i") . '

        </div>


    </div>


    <div class="resumen">


        <strong>

            Total de ventas:

        </strong>

        ' . $totalVentas . '


        &nbsp;&nbsp;&nbsp;


        <strong>

            Estado:

        </strong>

        ' . $textoEstado . '


    </div>


    <table>


        <thead>


            <tr>


                <th style="width: 8%;">

                    Venta

                </th>


                <th style="width: 16%;">

                    Fecha

                </th>


                <th style="width: 18%;">

                    Usuario

                </th>


                <th style="width: 32%;">

                    Productos

                </th>


                <th style="width: 13%;">

                    Estado

                </th>


                <th
                    style="width: 13%; text-align: right;"
                >

                    Total

                </th>


            </tr>


        </thead>


        <tbody>

';


// =====================================================
// RECORRER VENTAS
// =====================================================

if ($resultado->num_rows > 0) {


    while (
        $venta =
        $resultado->fetch_assoc()
    ) {


        // =================================================
        // CONSULTAR PRODUCTOS
        // =================================================

        $sqlDetalle = "
            SELECT
                p.nombre,
                dv.cantidad
            FROM detalle_ventas dv
            INNER JOIN productos p
                ON dv.producto_id = p.id
            WHERE dv.venta_id = ?
            ORDER BY dv.id ASC
        ";


        $stmtDetalle =
            $conexion->prepare(
                $sqlDetalle
            );


        if (!$stmtDetalle) {

            die(
                "Error al preparar detalle: "
                . $conexion->error
            );

        }


        $stmtDetalle->bind_param(
            "i",
            $venta["id"]
        );


        if (!$stmtDetalle->execute()) {

            die(
                "Error al obtener detalle: "
                . $stmtDetalle->error
            );

        }


        $resultadoDetalle =
            $stmtDetalle->get_result();


        $productosVenta = [];


        while (
            $detalle =
            $resultadoDetalle->fetch_assoc()
        ) {


            $nombreProducto =
                htmlspecialchars(
                    $detalle["nombre"],
                    ENT_QUOTES,
                    "UTF-8"
                );


            $cantidadProducto =
                (int) $detalle["cantidad"];


            $productosVenta[] =
                $nombreProducto
                . " x "
                . $cantidadProducto;

        }


        $stmtDetalle->close();


        // =================================================
        // SUMAR TOTAL GENERAL
        // =================================================

        $totalGeneral +=
            (float) $venta["total"];


        // =================================================
        // DATOS SEGUROS
        // =================================================

        $idVenta =
            htmlspecialchars(
                $venta["id"],
                ENT_QUOTES,
                "UTF-8"
            );


        $usuario =
            htmlspecialchars(
                $venta["usuario"],
                ENT_QUOTES,
                "UTF-8"
            );


        $fechaVenta =
            date(
                "d/m/Y H:i",
                strtotime(
                    $venta["fecha"]
                )
            );


        $totalVenta =
            number_format(
                (float) $venta["total"],
                0,
                ",",
                "."
            );


        $productosHtml =
            implode(
                "<br>",
                $productosVenta
            );


        $estadoVenta =
            $venta["estado"] === "realizada"
                ? "Realizada"
                : "Anulada";


        // =================================================
        // AGREGAR FILA
        // =================================================

        $html .= '

            <tr>


                <td>

                    #' . $idVenta . '

                </td>


                <td>

                    ' . $fechaVenta . '

                </td>


                <td>

                    ' . $usuario . '

                </td>


                <td>

                    ' . $productosHtml . '

                </td>


                <td>

                    ' . $estadoVenta . '

                </td>


                <td
                    style="text-align: right;"
                >

                    $' . $totalVenta . '

                </td>


            </tr>

        ';

    }


} else {


    $html .= '

        <tr>


            <td
                colspan="6"
                style="text-align: center;"
            >

                No se encontraron ventas.

            </td>


        </tr>

    ';

}


// =====================================================
// TOTAL GENERAL
// =====================================================

$html .= '

        </tbody>


        <tfoot>


            <tr>


                <td
                    colspan="5"
                    style="
                        text-align: right;
                        font-weight: bold;
                        padding-top: 10px;
                    "
                >

                    TOTAL GENERAL

                </td>


                <td
                    style="
                        text-align: right;
                        font-weight: bold;
                        padding-top: 10px;
                    "
                >

                    $' . number_format(
                        $totalGeneral,
                        0,
                        ",",
                        "."
                    ) . '

                </td>


            </tr>


        </tfoot>


    </table>


    <div class="pie">

        Sistema de Inventario

    </div>


</body>

</html>

';


// =====================================================
// CONFIGURAR DOMPDF
// =====================================================

$options =
    new Options();


$options->set(
    "isHtml5ParserEnabled",
    true
);


$options->set(
    "isRemoteEnabled",
    true
);


$options->set(
    "defaultFont",
    "DejaVu Sans"
);


$options->setChroot(
    realpath(
        __DIR__ . "/../.."
    )
);


// =====================================================
// CREAR DOMPDF
// =====================================================

$dompdf =
    new Dompdf($options);


$dompdf->loadHtml(
    $html,
    "UTF-8"
);


// =====================================================
// TAMAÑO Y ORIENTACIÓN
// =====================================================

$dompdf->setPaper(
    "A4",
    "landscape"
);


// =====================================================
// RENDERIZAR
// =====================================================

$dompdf->render();


// =====================================================
// DESCARGAR PDF
// =====================================================

$nombreArchivo =
    "ventas_"
    . date("Y-m-d_H-i-s")
    . ".pdf";


$dompdf->stream(
    $nombreArchivo,
    [
        "Attachment" => true
    ]
);


$stmt->close();

exit;

?>