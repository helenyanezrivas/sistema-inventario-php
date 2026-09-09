<?php

session_start();

require_once "../../config/database.php";
require_once "../../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;


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
// EJECUTAR CONSULTA
// =====================================================

if (!$stmt->execute()) {
    die("Error al obtener los productos: " . $stmt->error);
}


$resultado = $stmt->get_result();


// =====================================================
// CREAR ARCHIVO EXCEL
// =====================================================

$spreadsheet = new Spreadsheet();

$hoja = $spreadsheet->getActiveSheet();

$hoja->setTitle("Inventario");


// =====================================================
// TÍTULO
// =====================================================

$hoja->mergeCells("A1:I1");

$hoja->setCellValue(
    "A1",
    "SISTEMA DE INVENTARIO - PRODUCTOS"
);


// =====================================================
// ENCABEZADOS
// =====================================================

$encabezados = [
    "ID",
    "Código",
    "Producto",
    "Descripción",
    "Categoría",
    "Precio compra",
    "Precio venta",
    "Stock",
    "Estado"
];


$columna = "A";

foreach ($encabezados as $encabezado) {

    $hoja->setCellValue(
        $columna . "3",
        $encabezado
    );

    $columna++;
}


// =====================================================
// DATOS
// =====================================================

$fila = 4;


while ($producto = $resultado->fetch_assoc()) {

    $hoja->setCellValue(
        "A" . $fila,
        $producto["id"]
    );

    $hoja->setCellValue(
        "B" . $fila,
        $producto["codigo"]
    );

    $hoja->setCellValue(
        "C" . $fila,
        $producto["nombre"]
    );

    $hoja->setCellValue(
        "D" . $fila,
        $producto["descripcion"] ?? ""
    );

    $hoja->setCellValue(
        "E" . $fila,
        $producto["categoria"]
    );

    $hoja->setCellValue(
        "F" . $fila,
        (float) $producto["precio_compra"]
    );

    $hoja->setCellValue(
        "G" . $fila,
        (float) $producto["precio_venta"]
    );

    $hoja->setCellValue(
        "H" . $fila,
        (int) $producto["stock"]
    );

    $estado = $producto["estado"] == 1
        ? "Activo"
        : "Inactivo";

    $hoja->setCellValue(
        "I" . $fila,
        $estado
    );

    $fila++;
}


// =====================================================
// ESTILO DEL TÍTULO
// =====================================================

$hoja->getStyle("A1:I1")->applyFromArray([

    "font" => [
        "bold" => true,
        "size" => 16,
        "color" => [
            "rgb" => "FFFFFF"
        ]
    ],

    "alignment" => [
        "horizontal" => Alignment::HORIZONTAL_CENTER,
        "vertical" => Alignment::VERTICAL_CENTER
    ],

    "fill" => [
        "fillType" => Fill::FILL_SOLID,
        "startColor" => [
            "rgb" => "6F42C1"
        ]
    ]

]);


$hoja->getRowDimension(1)->setRowHeight(28);


// =====================================================
// ESTILO DE ENCABEZADOS
// =====================================================

$hoja->getStyle("A3:I3")->applyFromArray([

    "font" => [
        "bold" => true,
        "color" => [
            "rgb" => "FFFFFF"
        ]
    ],

    "fill" => [
        "fillType" => Fill::FILL_SOLID,
        "startColor" => [
            "rgb" => "6F42C1"
        ]
    ],

    "alignment" => [
        "horizontal" => Alignment::HORIZONTAL_CENTER,
        "vertical" => Alignment::VERTICAL_CENTER
    ],

    "borders" => [
        "allBorders" => [
            "borderStyle" => Border::BORDER_THIN,
            "color" => [
                "rgb" => "D9D9D9"
            ]
        ]
    ]

]);


// =====================================================
// FORMATO DE MONEDA
// =====================================================

if ($fila > 4) {

    $hoja
        ->getStyle("F4:G" . ($fila - 1))
        ->getNumberFormat()
        ->setFormatCode('$#,##0');

}


// =====================================================
// BORDES DE LA TABLA
// =====================================================

if ($fila > 4) {

    $hoja
        ->getStyle("A3:I" . ($fila - 1))
        ->getBorders()
        ->getAllBorders()
        ->setBorderStyle(
            Border::BORDER_THIN
        );

}


// =====================================================
// ALINEACIÓN
// =====================================================

$hoja
    ->getStyle("A3:I" . max(3, $fila - 1))
    ->getAlignment()
    ->setVertical(
        Alignment::VERTICAL_CENTER
    );


// =====================================================
// ANCHO DE COLUMNAS
// =====================================================

$hoja->getColumnDimension("A")->setWidth(8);
$hoja->getColumnDimension("B")->setWidth(15);
$hoja->getColumnDimension("C")->setWidth(25);
$hoja->getColumnDimension("D")->setWidth(35);
$hoja->getColumnDimension("E")->setWidth(20);
$hoja->getColumnDimension("F")->setWidth(18);
$hoja->getColumnDimension("G")->setWidth(18);
$hoja->getColumnDimension("H")->setWidth(12);
$hoja->getColumnDimension("I")->setWidth(12);


// =====================================================
// CONGELAR ENCABEZADOS
// =====================================================

$hoja->freezePane("A4");


// =====================================================
// CERRAR CONSULTA
// =====================================================

$stmt->close();


// =====================================================
// DESCARGAR ARCHIVO
// =====================================================

$nombreArchivo =
    "inventario_" .
    date("Y-m-d_H-i-s") .
    ".xlsx";


header(
    "Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
);

header(
    "Content-Disposition: attachment; filename=\"" .
    $nombreArchivo .
    "\""
);

header("Cache-Control: max-age=0");


$writer = new Xlsx($spreadsheet);

$writer->save("php://output");

exit;