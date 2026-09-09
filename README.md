# Sistema de Inventario

Sistema web de gestión de inventario desarrollado en PHP y MySQL, orientado a la administración de productos, categorías, usuarios y ventas.

## Descripción

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
- Correo electrónico asociado a cada usuario.
- Inicio de sesión mediante correo electrónico.
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

### 🔐 Seguridad y autenticación

- Inicio de sesión mediante correo electrónico y contraseña.
- Verificación de contraseñas mediante `password_verify()`.
- Contraseñas protegidas mediante hash.
- Protección de páginas internas mediante sesiones.
- Control de acceso según rol.
- Regeneración del ID de sesión después del inicio de sesión.
- Cierre de sesión y destrucción de la sesión.
- Protección CSRF para formularios y operaciones sensibles.
- Uso de consultas preparadas para operaciones parametrizadas.
- Uso de transacciones para el registro y anulación de ventas.
- Bloqueo temporal después de múltiples intentos fallidos de inicio de sesión.
- Recuperación de contraseña mediante enlace temporal enviado por correo electrónico.
- Tokens de recuperación almacenados mediante hash y con expiración.

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
- **PHPMailer** — envío de correos mediante SMTP.

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
│   ├── database.php
│   └── correo.php              # Configuración local, ignorada por Git
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
├── logout.php
├── recuperar.php
└── restablecer.php
```

## Base de datos

El sistema utiliza MySQL para almacenar y gestionar la información.

Principales tablas:

- `usuarios`
- `categorias`
- `productos`
- `ventas`
- `detalle_ventas`

La tabla `usuarios` utiliza correo electrónico para autenticación y recuperación de contraseña. También contiene los campos temporales utilizados para los tokens de recuperación.

La estructura de la base de datos se encuentra disponible en:

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
2. Ir a **Importar**.
3. Seleccionar `inventario.sql`.
4. Ejecutar la importación.

El archivo creará las tablas necesarias para el funcionamiento del sistema.

### 5. Configurar la conexión a MySQL

Editar:

```text
config/database.php
```

Ejemplo para una instalación local de XAMPP:

```php
$host = "localhost";
$usuario = "root";
$password = "";
$base_datos = "inventario";
```

### 6. Instalar dependencias

Desde la carpeta del proyecto:

```bash
composer install
```

### 7. Configurar el correo para recuperación de contraseña

La recuperación de contraseña utiliza PHPMailer mediante SMTP.

Crear localmente:

```text
config/correo.php
```

Este archivo **no debe subirse a GitHub**, porque contiene las credenciales SMTP.

Ejemplo:

```php
<?php

$smtpHost = "smtp.gmail.com";
$smtpPort = 587;
$smtpUsername = "tu_correo@gmail.com";
$smtpPassword = "TU_CONTRASENA_DE_APLICACION";

$smtpFromEmail = "tu_correo@gmail.com";
$smtpFromName = "Sistema de Inventario";
```

Para Gmail se debe utilizar una contraseña de aplicación y no la contraseña normal de la cuenta.

### 8. Configurar el primer administrador

El proyecto no incluye un usuario administrador con datos reales dentro de `inventario.sql`.

Después de importar la base de datos, se debe crear o configurar el primer administrador en la tabla `usuarios`.

La contraseña debe almacenarse usando un hash generado con PHP y no como texto plano.

Una vez creado el administrador, los demás usuarios pueden registrarse desde:

```text
Usuarios → Crear usuario
```

### 9. Iniciar XAMPP

Activar:

- Apache
- MySQL

### 10. Acceder al sistema

Abrir:

```text
http://localhost/inventario/
```

## Autenticación y recuperación de contraseña

El inicio de sesión utiliza el correo electrónico del usuario:

```text
Correo electrónico
Contraseña
```

La opción:

```text
¿Olvidaste tu contraseña?
```

permite solicitar un enlace temporal de recuperación.

Flujo:

```text
Correo electrónico
        ↓
Enlace de recuperación
        ↓
Token temporal
        ↓
Nueva contraseña
        ↓
Token invalidado
```

El enlace de recuperación tiene una vigencia limitada. Una vez utilizada la recuperación, el token se invalida y no puede reutilizarse.

## Gestión de roles

El sistema contempla dos roles:

| Rol | Acceso |
|---|---|
| Administrador | Acceso completo y gestión de usuarios |
| Vendedor | Operaciones permitidas para ventas e inventario |

## Flujo de ventas

Las ventas registradas quedan almacenadas como parte del historial del sistema.

Una venta realizada no se elimina físicamente. En caso de existir un error, puede ser anulada.

Al anular una venta:

1. Se cambia su estado a `anulada`.
2. Se conservan sus datos históricos.
3. Se restauran las cantidades de productos al stock.
4. La venta queda disponible para consulta.

## Exportaciones

El sistema permite generar reportes en:

- Excel
- PDF

Las exportaciones incluyen información relacionada con productos y ventas según los filtros disponibles en cada módulo.

## Seguridad

Entre las medidas implementadas se incluyen:

- Hash de contraseñas.
- `password_verify()` para autenticación.
- Tokens CSRF.
- Consultas preparadas.
- Control de sesiones.
- Control de acceso por rol.
- Regeneración del ID de sesión.
- Bloqueo temporal de intentos de login.
- Tokens de recuperación aleatorios.
- Hash de tokens de recuperación almacenados en la base de datos.
- Expiración de enlaces de recuperación.
- Invalidación de tokens después de utilizarse.
- Exclusión de credenciales SMTP mediante `.gitignore`.

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
- Recuperación de contraseña mediante correo electrónico.
- Integración de librerías mediante Composer.

## Autor

**Helen Yáñez Rivas**

Ingeniería en Ejecución en Computación e Informática

GitHub: [helenyanezrivas](https://github.com/helenyanezrivas)
