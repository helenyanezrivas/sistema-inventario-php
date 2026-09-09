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
- Gestión de estado de las categorías.

### 👥 Usuarios

- Gestión de usuarios.
- Creación y edición de usuarios.
- Roles de administrador y vendedor.
- Activación y desactivación de usuarios.
- Restricción de acceso a la administración de usuarios para administradores.
- Contraseñas almacenadas mediante hash.

### 🛒 Ventas

- Registro de ventas.
- Carrito de productos.
- Validación de stock disponible.
- Descuento automático del stock al registrar una venta.
- Consulta del detalle de cada venta.
- Filtros por fecha, usuario y estado.
- Exportación de ventas a Excel y PDF.
- Anulación de ventas.
- Restauración automática del stock al anular una venta.
- Conservación del historial de ventas anuladas.

### 📊 Panel de control

- Resumen general del inventario.
- Cantidad de productos activos.
- Cantidad de categorías activas.
- Resumen de ventas.
- Indicadores de ventas del día.
- Productos con stock bajo.
- Resumen de ventas de los últimos días.
- Resumen mensual.
- Gráficos estadísticos.

### 🔐 Seguridad y control de acceso

- Inicio de sesión mediante usuario y contraseña.
- Verificación de contraseñas mediante `password_verify()`.
- Contraseñas almacenadas mediante `password_hash()`.
- Protección de páginas internas mediante sesiones.
- Control de acceso según rol.
- Regeneración del ID de sesión después del inicio de sesión.
- Cierre de sesión y destrucción de la sesión.

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
│   └── navbar.php
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

Las relaciones entre las tablas permiten mantener la integridad de la información y controlar la asociación entre productos, categorías y ventas.

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

Crear una base de datos MySQL llamada:

```text
inventario
```

Luego importar la estructura de la base de datos.

### 4. Configurar la conexión

Editar:

```text
config/database.php
```

y configurar los datos correspondientes al servidor MySQL local.

### 5. Instalar dependencias

Desde la carpeta del proyecto ejecutar:

```bash
composer install
```

### 6. Iniciar XAMPP

Activar:

- Apache
- MySQL

### 7. Acceder al sistema

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

Las exportaciones incluyen información relacionada con productos y ventas según los filtros seleccionados.

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
- Integración de librerías mediante Composer.

## Autor

**Helen Yáñez Rivas**

Ingeniería en Ejecución en Computación e Informática

GitHub: [helenyanezrivas](https://github.com/helenyanezrivas)
