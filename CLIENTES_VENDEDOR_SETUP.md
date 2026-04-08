# Implementación: Filtrado de Clientes por Vendedor

## Cambios Realizados

### 1. **Migración de Base de Datos**
- Archivo: `app/database/migrations/add_id_vendedor_to_clientes.php`
- Agrega columna `id_vendedor` a la tabla `clientes`
- Se ejecuta automáticamente al cargar la app

### 2. **Filtrado en Listados**
Los archivos fueron actualizados para filtrar automáticamente:
- `app/controllers/clientes/list_foraneos.php` - Clientes foráneos
- `app/controllers/clientes/list_locales.php` - Clientes locales

**Lógica:**
- **Si es Vendedor (id_rol = 3)**: Ve solo sus clientes
- **Si es Admin**: Ve todos los clientes

### 3. **Creación de Clientes**
- Archivo: `clientes/create.php` (vista)
- Si es **Vendedor**: Se asigna automáticamente su ID
- Si es **Admin**: Puede seleccionar un vendedor o dejar sin asignar

### 4. **Edición de Clientes**
- Archivo: `clientes/edit.php` (vista)
- Si es **Vendedor**: No ve el campo (mantiene su ID)
- Si es **Admin**: Puede cambiar el vendedor asignado

## ⚠️ IMPORTANTE: Verificar ID del Rol

En el código se asume que **id_rol = 3** corresponde al rol de VENDEDOR.

**Para verificar**, ejecuta en MySQL:
```sql
SELECT id_rol, rol FROM tb_roles WHERE rol LIKE '%vendedor%' OR rol LIKE '%Vendedor%' OR rol LIKE '%sales%';
```

Si el ID es diferente, actualiza el número 3 en los siguientes archivos:
- `app/controllers/clientes/list_foraneos.php` (línea con `if ($id_rol_sesion == 3)`)
- `app/controllers/clientes/list_locales.php` (línea con `if ($id_rol_sesion == 3)`)
- `app/controllers/clientes/create.php` (línea con `if ($id_rol_sesion == 3)`)
- `app/controllers/clientes/edit.php` (línea con `if ($id_rol_sesion == 3)`)
- `clientes/create.php` (línea con `<?php if ($_SESSION['id_rol_sesion'] != 3)`)
- `clientes/edit.php` (línea con `<?php if ($_SESSION['id_rol_sesion'] != 3)`)

## Comportamiento Esperado

### Para Vendedores:
- ✅ Solo ven sus propios clientes
- ✅ Sus clientes se asignan automáticamente
- ❌ No ven opción de seleccionar vendedor

### Para Administradores:
- ✅ Ven todos los clientes
- ✅ Pueden asignar/cambiar vendedor
- ✅ Puede dejar sin vendedor asignado (opcional)

## Migraciones Automáticas
La migración se ejecuta automáticamente cuando se carga `app/config.php`. Si tienes problemas, verifica los logs:
- `tail -f app/logs/log_error_*.jsonl`
