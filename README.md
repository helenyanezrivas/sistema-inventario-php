# Sistema de Inventario

Sistema web de gestión de inventario desarrollado en PHP y MySQL, orientado a la administración de productos, categorías, usuarios y ventas.

## Descripción

Aplicación web desarrollada como proyecto de portafolio para gestionar de manera centralizada el inventario de una empresa.

El sistema permite administrar productos y categorías, controlar el stock, registrar ventas y gestionar usuarios según su rol de acceso.

## Funcionalidades

### 📦 Productos

- Crear productos.
- Editar productos.
- Eliminar productos cuando no existen ventas asociadas.
- Generación automática de códigos de producto.
- Gestión de categorías.
- Control de stock.
- Definición de stock mínimo.
- Filtros por nombre, código, stock y estado.
- Exportación de productos a Excel y PDF.

### 🏷️ Categorías

- Crear categorías.
- Editar categorías.
- Eliminar categorías cuando no tienen productos asociados.
- Gestión del estado de las categorías.
- Visualización de la cantidad de productos asociados a cada categoría.

### 👥 Usuarios

- Gestión de usuarios.
- Creación y edición de usuarios.
- Roles de administrador y vendedor.
- Activación y desactivación de usuarios.
- Restricción de acceso a la administración de usuarios para administradores.
- Contraseñas almacenadas mediante hash.
- Protección de acciones sensibles mediante tokens CSRF.

### 🛒 Ventas

- Registro de ventas.
- Selección de productos para registrar una venta.
- Validación de stock disponible.
- Descuento automático del stock al registrar una venta.
- Consulta del detalle de cada venta.
- Filtros por fecha, usuario y estado.
- Exportación de ventas a Excel y PDF.
- Anulación de ventas.
- Restauración automática del stock al anular una venta.
- Conservación del historial de ventas anuladas.
- Protección de operaciones sensibles mediante POST y CSRF.

### 📊 Panel de control

- Resumen general del inventario.
- Cantidad de productos activos.
- Cantidad de categorías activas.
- Cantidad de ventas realizadas.
- Indicadores de ventas del día.
- Total vendido durante el día.
- Productos con stock bajo.
- Visualización de las últimas ventas registradas.
- Gráfico de ventas de los últimos 7 días.
- Gráfico de ventas por categoría del mes actual.
- Resumen mensual con cantidad de ventas, total vendido y promedio por venta.

### 🔐 Seguridad y control de acceso

- Inicio de sesión mediante usuario y contraseña.
- Verificación de contraseñas mediante `password_verify()`.
- Contraseñas protegidas mediante hash.
- Protección de páginas internas mediante sesiones.
- Control de acceso según rol.
- Regeneración del ID de sesión después del inicio de sesión.
- Cierre de sesión y destrucción de la sesión.
- Protección CSRF para formularios y operaciones sensibles.
- Uso de consultas preparadas para operaciones parametrizadas.
- Uso de transacciones para el registro y anulación de ventas.

## Tecnologías utilizadas

- PHP
- MySQL
- HTML5
- CSS3
- JavaScript
- Bootstrap
- Bootstrap Icons
- Chart.js
- Composer

### Librerías

- **PhpSpreadsheet** — generación de archivos Excel.
- **Dompdf** — generación de documentos PDF.

## Estructura del proyecto

```text
inventario/
│
├── assets/
│   ├── css/
│   ├── img/
│   └── js/
│
├── config/
│   └── database.php
│
├── includes/
│   ├── navbar.php
│   └── security.php
│
├── views/
│   ├── categorias/
│   ├── productos/
│   ├── usuarios/
│   └── ventas/
│
├── .gitignore
├── composer.json
├── composer.lock
├── index.php
├── inventario.sql
├── login.php
└── logout.php
```

## Base de datos

El sistema utiliza MySQL para almacenar y gestionar la información.

Principales tablas:

- `usuarios`
- `categorias`
- `productos`
- `ventas`
- `detalle_ventas`

Las relaciones entre las tablas permiten mantener la integridad de la información y controlar la asociación entre usuarios, productos, categorías y ventas.

La estructura de la base de datos se encuentra disponible en el archivo:

```text
inventario.sql
```

Este archivo contiene la estructura de las tablas y sus relaciones, pero no incluye los datos actuales del sistema.

## Instalación

### Requisitos

- XAMPP
- PHP
- MySQL
- Composer
- Navegador web

### 1. Clonar el repositorio

```bash
git clone https://github.com/helenyanezrivas/sistema-inventario-php.git
```

### 2. Ubicar el proyecto

Colocar la carpeta dentro del directorio `htdocs` de XAMPP:

```text
C:\xampp\htdocs\inventario
```

### 3. Crear la base de datos

Iniciar **Apache** y **MySQL** desde el panel de XAMPP.

Luego abrir **phpMyAdmin** y crear una base de datos llamada:

```text
inventario
```

### 4. Importar la estructura de la base de datos

En phpMyAdmin:

1. Seleccionar la base de datos `inventario`.
2. Ir a la pestaña **Importar**.
3. Seleccionar el archivo `inventario.sql` del proyecto.
4. Ejecutar la importación.

El archivo `inventario.sql` creará las tablas necesarias para el funcionamiento del sistema.

### 5. Configurar la conexión

Editar:

```text
config/database.php
```

y configurar los datos correspondientes al servidor MySQL local.

Ejemplo para una instalación local de XAMPP:

```php
$host = "localhost";
$usuario = "root";
$password = "";
$base_datos = "inventario";
```

### 6. Instalar dependencias

Desde la carpeta del proyecto ejecutar:

```bash
composer install
```

### 7. Iniciar XAMPP

Activar:

- Apache
- MySQL

### 8. Acceder al sistema

Abrir en el navegador:

```text
http://localhost/inventario/
```

## Flujo de ventas

Las ventas registradas quedan almacenadas como parte del historial del sistema.

Una venta realizada no se elimina físicamente. En caso de existir un error, puede ser anulada.

Al anular una venta:

1. Se cambia su estado a `anulada`.
2. Se conservan sus datos históricos.
3. Se restauran las cantidades de productos al stock.
4. La venta queda disponible para consulta.

## Gestión de roles

El sistema contempla dos roles:

| Rol | Acceso |
|---|---|
| Administrador | Acceso completo y gestión de usuarios |
| Vendedor | Operaciones permitidas para ventas e inventario |

## Exportaciones

El sistema permite generar reportes en:

- Excel
- PDF

Las exportaciones incluyen información relacionada con productos y ventas según los filtros disponibles en cada módulo.

## Objetivo del proyecto

Este proyecto fue desarrollado para aplicar conocimientos de:

- Desarrollo web con PHP.
- Gestión de bases de datos MySQL.
- Consultas SQL.
- Control de sesiones y autenticación.
- Gestión de inventario.
- Manejo de transacciones.
- Generación de reportes.
- Control de roles y permisos.
- Protección CSRF.
- Integración de librerías mediante Composer.

## Autor

**Helen Yáñez Rivas**

Ingeniería en Ejecución en Computación e Informática

GitHub: [helenyanezrivas](https://github.com/helenyanezrivas)