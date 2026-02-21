# PROYECTO

Este proyecto es una aplicación web para la gestión de almacenes, clientes, ventas, proveedores, roles y usuarios. Está desarrollado en PHP y utiliza Composer para la gestión de dependencias.

## Estructura del Proyecto

- `almacen/`: CRUD de productos en almacén.
- `app/`: Lógica de negocio, controladores y configuración.
- `categorias/`: Gestión de categorías.
- `clientes/`: Gestión de clientes (locales y foráneos).
- `dashboard/`: Panel de administración y reportes.
- `layout/`: Plantillas y componentes de interfaz.
- `login/`: Autenticación de usuarios.
- `provedores/`: Gestión de proveedores.
- `public/`: Recursos públicos y plantillas.
- `roles/`: Gestión de roles.
- `stock/`: Control de inventario.
- `usuarios/`: Gestión de usuarios.
- `ventas/`: Gestión de ventas y cotizaciones.
- `vendor/`: Dependencias gestionadas por Composer.

## Instalación

1. Clona el repositorio.
2. Instala las dependencias con Composer:

```bash
composer install
```

3. Configura la base de datos en `app/config.php`.
4. Inicia el servidor local:

```bash
php -S localhost:8000
```

## Ejemplo de Uso

### Crear un producto en almacén

```php
// almacen/create.php
$nombre = $_POST['nombre'];
$cantidad = $_POST['cantidad'];
$precio = $_POST['precio'];
// Validación de parámetros
if (empty($nombre) || $cantidad < 0 || $precio < 0) {
    echo 'Parámetros inválidos';
    return;
}
// Lógica para guardar el producto
```

### Parámetros

- `nombre` (string): Nombre del producto. Obligatorio.
- `cantidad` (int): Cantidad en inventario. Debe ser >= 0.
- `precio` (float): Precio unitario. Debe ser >= 0.

### Retorno

- Éxito: Mensaje de confirmación.
- Error: Mensaje de error detallado.

### Casos límite

- Cantidad o precio negativos: Retorna error.
- Nombre vacío: Retorna error.
- Duplicidad de producto: Validar antes de guardar.

## Autenticación

La autenticación se realiza en `login/index.php` y `app/controllers/login/ingreso.php`. Se recomienda usar sesiones seguras y validar credenciales.

## Ejemplo de Autenticación

```php
// login/index.php
session_start();
if ($_POST['usuario'] && $_POST['password']) {
    // Validar credenciales
    // ...
    $_SESSION['usuario'] = $usuario;
}
```

## Manejo de Sesiones

- Iniciar sesión: `session_start()`.
- Cerrar sesión: `login/cerrar_sesion.php`.
- Validar sesión: Comprobar `$_SESSION['usuario']`.

## Buenas Prácticas

- Validar todos los parámetros de entrada.
- Usar sentencias preparadas para consultas SQL.
- Manejar errores y excepciones.
- Documentar funciones y métodos.
- Separar lógica de negocio y presentación.

## Ejemplo de Documentación de Función


## Documentación: Gestión de Clientes

La gestión de clientes permite registrar, editar, eliminar y consultar clientes locales y foráneos.

### Crear Cliente

**Parámetros:**

- `nombre` (string): Nombre completo del cliente. Obligatorio.
- `tipo` (string): Tipo de cliente (`local` o `foráneo`). Obligatorio.
- `telefono` (string): Teléfono del cliente. Opcional.
- `email` (string): Correo electrónico. Opcional.

**Retorno:**

- Éxito: `true` y mensaje de confirmación.
- Error: `false` y mensaje de error.

**Edge Cases:**

- Nombre vacío o nulo: Retorna error.
- Tipo inválido: Retorna error.
- Duplicidad de cliente: Validar antes de guardar.
- Email inválido: Retorna error.

**Ejemplo:**

```php
/**
 * Crea un nuevo cliente.
 *
 * @param string $nombre Nombre del cliente.
 * @param string $tipo Tipo de cliente (local/foráneo).
 * @param string|null $telefono Teléfono del cliente.
 * @param string|null $email Correo electrónico.
 * @return bool True si se creó correctamente, False en caso de error.
 * @throws Exception Si los parámetros son inválidos.
 */
function crearCliente($nombre, $tipo, $telefono = null, $email = null) {
    if (empty($nombre) || ($tipo !== 'local' && $tipo !== 'foráneo')) {
        throw new Exception('Parámetros inválidos');
    }
    // Validar duplicidad, email, etc.
    // Guardar cliente en base de datos
    return true;
}
```

### Editar Cliente

**Parámetros:**
- `id` (int): ID del cliente. Obligatorio.
- `nombre`, `tipo`, `telefono`, `email`: Campos a actualizar.

**Retorno:**
- Éxito: `true` y mensaje de confirmación.
- Error: `false` y mensaje de error.

### Eliminar Cliente

**Parámetros:**
- `id` (int): ID del cliente. Obligatorio.

**Retorno:**
- Éxito: `true` y mensaje de confirmación.
- Error: `false` y mensaje de error.

### Consultar Cliente

**Parámetros:**
- `id` (int): ID del cliente. Opcional para consulta individual.

**Retorno:**
- Cliente(s) encontrado(s) o mensaje de error.

### Ejemplo de Uso

```php
$cliente = crearCliente('Juan Pérez', 'local', '8112345678', 'juan@example.com');
if ($cliente) {
    echo 'Cliente creado correctamente';
} else {
    echo 'Error al crear cliente';
}
```

## Edge Cases

- Parámetros nulos o vacíos.
- Duplicidad de registros.
- Errores de conexión a base de datos.
- Sesiones expiradas.

## Licencia

Este proyecto está bajo la licencia Luis Gabuardi.

# Manual de Uso

Este manual describe cómo utilizar la aplicación web de gestión de almacenes, clientes, ventas, proveedores, roles y usuarios.

## 1. Acceso al sistema

1. Abre el navegador y accede a la URL del servidor (por ejemplo, http://pacasyadira.com).
2. Ingresa tus credenciales en la pantalla de login.
3. Si los datos son correctos, accederás al panel principal.

## 2. Módulos principales

### Almacén
- Crear, editar, eliminar y consultar productos.
- Subir imágenes de productos.
- Controlar inventario y movimientos.

### Clientes
- Registrar clientes locales y foráneos.
- Editar y eliminar clientes.
- Consultar información y reportes.

### Ventas
- Crear cotizaciones y ventas.
- Editar y consultar ventas.
- Generar comprobantes y guías PDF.

### Proveedores
- Registrar, editar y eliminar proveedores.
- Consultar lista de proveedores.

### Roles y Usuarios
- Crear, editar y eliminar usuarios.
- Asignar roles y permisos.
- Controlar acceso a módulos.

### Dashboard
- Visualizar reportes de ventas, inventario y clientes.
- Acceder a paneles de administración.

## 3. Operaciones comunes

### Crear un producto
1. Ve a la sección "Almacén".
2. Haz clic en "Agregar producto".
3. Completa el formulario y guarda.

### Registrar un cliente
1. Ve a "Clientes".
2. Haz clic en "Agregar cliente".
3. Completa los datos y guarda.

### Realizar una venta
1. Ve a "Ventas".
2. Haz clic en "Nueva venta".
3. Selecciona productos y cliente.
4. Guarda y genera comprobante.

### Editar o eliminar registros
1. Ve al módulo correspondiente.
2. Selecciona el registro.
3. Haz clic en "Editar" o "Eliminar".

## 4. Recomendaciones
- Valida los datos antes de guardar.
- Utiliza contraseñas seguras.
- Realiza respaldos periódicos de la base de datos.
- Consulta reportes para monitorear el negocio.

## 5. Solución de problemas
- Si no puedes iniciar sesión, verifica usuario y contraseña.
- Si hay errores de conexión, revisa la configuración en `app/config.php`.
- Para problemas de permisos, revisa la asignación de roles.

## 6. Contacto y soporte
Para dudas o soporte, contactar a: 8119058201
