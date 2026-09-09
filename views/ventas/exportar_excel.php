<?php

session_start();

require_once "../../config/database.php";
require_once "../../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;


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
// CONSULTA
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

    $parametros[] = (int) $usuarioFiltro;

}


// =====================================================
// FILTRO ESTADO
// =====================================================

if ($estadoFiltro !== "") {

    $sql .= "
        AND v.estado = ?
    ";

    $tipos .= "s";

    $parametros[] = $estadoFiltro;

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

$stmt = $conexion->prepare($sql);


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


$resultado = $stmt->get_result();


// =====================================================
// CREAR EXCEL
// =====================================================

$spreadsheet = new Spreadsheet();

$hoja = $spreadsheet->getActiveSheet();

$hoja->setTitle("Ventas");


// =====================================================
// COLORES
// =====================================================

$morado = "FF6F42C1";

$blanco = "FFFFFFFF";

$negro = "FF000000";

$borde = "FFD9D9D9";


// =====================================================
// TÍTULO
// =====================================================

$hoja->mergeCells("A1:F1");

$hoja->setCellValue(
    "A1",
    "SISTEMA DE INVENTARIO - VENTAS"
);


$hoja->getStyle("A1:F1")->applyFromArray([

    "font" => [

        "bold" => true,

        "color" => [
            "rgb" => $blanco
        ],

        "size" => 12

    ],

    "fill" => [

        "fillType" => Fill::FILL_SOLID,

        "startColor" => [
            "rgb" => $morado
        ]

    ],

    "alignment" => [

        "horizontal" =>
            Alignment::HORIZONTAL_CENTER,

        "vertical" =>
            Alignment::VERTICAL_CENTER

    ]

]);


$hoja->getRowDimension(1)->setRowHeight(24);


// =====================================================
// ENCABEZADOS
// =====================================================

$encabezados = [

    "A3" => "Venta",

    "B3" => "Fecha",

    "C3" => "Usuario",

    "D3" => "Productos",

    "E3" => "Total",

    "F3" => "Estado"

];


foreach (
    $encabezados
    as $celda => $texto
) {

    $hoja->setCellValue(
        $celda,
        $texto
    );

}


// =====================================================
// FORMATO ENCABEZADOS
// =====================================================

$hoja->getStyle("A3:F3")->applyFromArray([

    "font" => [

        "bold" => true,

        "color" => [
            "rgb" => $blanco
        ]

    ],

    "fill" => [

        "fillType" =>
            Fill::FILL_SOLID,

        "startColor" => [
            "rgb" => $morado
        ]

    ],

    "alignment" => [

        "horizontal" =>
            Alignment::HORIZONTAL_CENTER,

        "vertical" =>
            Alignment::VERTICAL_CENTER

    ],

    "borders" => [

        "top" => [

            "borderStyle" =>
                Border::BORDER_THIN,

            "color" => [
                "rgb" => $borde
            ]

        ],

        "bottom" => [

            "borderStyle" =>
                Border::BORDER_THIN,

            "color" => [
                "rgb" => $borde
            ]

        ],

        "left" => [

            "borderStyle" =>
                Border::BORDER_THIN,

            "color" => [
                "rgb" => $borde
            ]

        ],

        "right" => [

            "borderStyle" =>
                Border::BORDER_THIN,

            "color" => [
                "rgb" => $borde
            ]

        ]

    ]

]);


// =====================================================
// DATOS
// =====================================================

$fila = 4;


while (
    $venta =
    $resultado->fetch_assoc()
) {


    // =================================================
    // OBTENER PRODUCTOS
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

        $productosVenta[] =
            $detalle["nombre"]
            . " x "
            . $detalle["cantidad"];

    }


    $stmtDetalle->close();


    // =================================================
    // ESCRIBIR DATOS
    // =================================================

    $hoja->setCellValue(
        "A" . $fila,
        "#" . $venta["id"]
    );


    $hoja->setCellValue(
        "B" . $fila,
        date(
            "d/m/Y H:i",
            strtotime(
                $venta["fecha"]
            )
        )
    );


    $hoja->setCellValue(
        "C" . $fila,
        $venta["usuario"]
    );


    $hoja->setCellValue(
        "D" . $fila,
        implode(
            ", ",
            $productosVenta
        )
    );


    $hoja->setCellValue(
        "E" . $fila,
        (float) $venta["total"]
    );


    $estadoTexto =
        $venta["estado"] === "realizada"
            ? "Realizada"
            : "Anulada";


    $hoja->setCellValue(
        "F" . $fila,
        $estadoTexto
    );


    // =================================================
    // FORMATO FILA
    // =================================================

    $hoja->getStyle(
        "A" . $fila . ":F" . $fila
    )->applyFromArray([

        "font" => [

            "color" => [
                "rgb" => $negro
            ]

        ],

        "borders" => [

            "top" => [

                "borderStyle" =>
                    Border::BORDER_THIN,

                "color" => [
                    "rgb" => $borde
                ]

            ],

            "bottom" => [

                "borderStyle" =>
                    Border::BORDER_THIN,

                "color" => [
                    "rgb" => $borde
                ]

            ],

            "left" => [

                "borderStyle" =>
                    Border::BORDER_THIN,

                "color" => [
                    "rgb" => $borde
                ]

            ],

            "right" => [

                "borderStyle" =>
                    Border::BORDER_THIN,

                "color" => [
                    "rgb" => $borde
                ]

            ]

        ]

    ]);


    // =================================================
    // ALINEACIÓN
    // =================================================

    $hoja->getStyle(
        "A" . $fila . ":F" . $fila
    )->getAlignment()->setVertical(
        Alignment::VERTICAL_CENTER
    );


    $hoja->getStyle(
        "A" . $fila
    )->getAlignment()->setHorizontal(
        Alignment::HORIZONTAL_CENTER
    );


    $hoja->getStyle(
        "E" . $fila . ":F" . $fila
    )->getAlignment()->setHorizontal(
        Alignment::HORIZONTAL_CENTER
    );


    // =================================================
    // FORMATO MONEDA
    // =================================================

    $hoja->getStyle(
        "E" . $fila
    )->getNumberFormat()
     ->setFormatCode('$#,##0');


    $fila++;

}


// =====================================================
// ANCHOS DE COLUMNAS
// =====================================================

$hoja->getColumnDimension("A")
     ->setWidth(12);


$hoja->getColumnDimension("B")
     ->setWidth(20);


$hoja->getColumnDimension("C")
     ->setWidth(25);


$hoja->getColumnDimension("D")
     ->setWidth(45);


$hoja->getColumnDimension("E")
     ->setWidth(18);


$hoja->getColumnDimension("F")
     ->setWidth(15);


// =====================================================
// AJUSTAR TEXTO PRODUCTOS
// =====================================================

if ($fila > 4) {

    $hoja->getStyle(
        "D4:D" . ($fila - 1)
    )->getAlignment()->setWrapText(true);

}


// =====================================================
// CONGELAR ENCABEZADOS
// =====================================================

$hoja->freezePane("A4");


// =====================================================
// FILTRO DE EXCEL
// =====================================================

$ultimaFila =
    max(3, $fila - 1);


$hoja->setAutoFilter(
    "A3:F" . $ultimaFila
);


// =====================================================
// NOMBRE ARCHIVO
// =====================================================

$nombreArchivo =
    "ventas_"
    . date("Y-m-d_H-i-s")
    . ".xlsx";


// =====================================================
// DESCARGA
// =====================================================

header(
    "Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
);


header(
    'Content-Disposition: attachment; filename="'
    . $nombreArchivo
    . '"'
);


header(
    "Cache-Control: max-age=0"
);


// =====================================================
// GENERAR ARCHIVO
// =====================================================

$writer =
    new Xlsx($spreadsheet);


$writer->save(
    "php://output"
);


// =====================================================
// CERRAR
// =====================================================

$stmt->close();

exit;

?>