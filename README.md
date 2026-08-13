# Sistema Pacas Yadira

Sistema web de gestión de almacén, ventas y logística para negocio de pacas (ropa de paca). Desarrollado en PHP con AdminLTE 3, MySQL/MariaDB bajo XAMPP.

---

## Stack técnico

| Capa | Tecnología |
|---|---|
| Backend | PHP 7.4+ |
| Base de datos | MySQL / MariaDB (XAMPP) |
| Frontend | AdminLTE 3.2 (Bootstrap 4) |
| Dependencias | Composer |
| Impresión | Zebra ZPL (etiquetas físicas) |

---

## Módulos

| Módulo | Descripción |
|---|---|
| `almacen/` | CRUD de productos: nombre, código, categoría, proveedor, precios, imagen |
| `stock/` | Ciclo de vida de pacas: generación, escaneo de entrada/salida, especiales |
| `ventas/` | Cotizaciones, ventas locales y foráneas, comprobantes, guías de envío |
| `clientes/` | Clientes locales y foráneos, múltiples direcciones de entrega |
| `dashboard/` | Detalle de venta, foráneos, reportes, subida de guías |
| `repartos/` | Asignación de repartos a conductores con guías |
| `tickets/` | Sistema de incidencias/soporte interno |
| `roles/` | Roles y permisos granulares por usuario |
| `usuarios/` | Gestión de usuarios del sistema |
| `categorias/` | Categorías de productos |
| `provedores/` | Proveedores de mercancía |
| `auditoria/` | Log de todos los cambios del sistema |
| `changelog/` | Historial de versiones del sistema |

---

## Ciclo de vida de una paca

```
GENERAR STOCK → SIN ESCANEAR → EN BODEGA → VENDIDO
                    ↑                ↑
              (escaneo entrada)  (escaneo salida en venta)
```

### Código único (`codigo_unico`)

Se genera automáticamente al crear stock:

```
codigo_unico = codigo_producto + id_stock (5 dígitos con ceros)

Ejemplo:  P-00001  +  00042  →  P-0000100042
          ^^^^^^^     ^^^^^
          tipo de    paca específica
          producto
```

- El **prefijo** (`P-00001`) identifica el tipo de producto — no cambia aunque el producto se renombre
- El **sufijo** (`00042`) es el `id_stock`, único en todo el sistema
- La baja (escaneo de salida) siempre se hace por `codigo_unico` completo, **nunca por nombre**
- Cambiar el nombre del producto en el sistema no afecta las pacas ya generadas

### Estados del stock

| Estado | Descripción |
|---|---|
| `SIN ESCANEAR` | Recién generado, no ha llegado a bodega |
| `EN BODEGA` | Escaneado en entrada, disponible para vender |
| `VENDIDO` | Escaneado en salida de una venta |

### Cancelar una venta

Al eliminar una venta, el sistema restaura automáticamente todo el stock:

1. Pacas ya escaneadas (en `tb_ventas_stock`) → regresan a `EN BODEGA`
2. Pacas reservadas pero no escaneadas → también regresan a `EN BODEGA`
3. Se eliminan detalles, comprobantes y guías de esa venta

Los `codigo_unico` quedan libres para usarse en nuevas ventas.

---

## Ventas

### Tipos de envío
- **Local**: Entrega en mano, sin guía
- **Foráneo**: Con paquetería (Estafeta, FedEx, Paquetería Express, J&T). Genera etiqueta Zebra por paca

### Tipos de pago
- Efectivo, comprobante bancario, ambos, o contra entrega

### Pacas especiales
Pacas marcadas como `FLEJADA` o `VIDEO` no pueden darse de baja en el flujo normal. Se venden desde **Bodega → Pacas Especiales**.

---

## Roles y permisos

El acceso a cada función está controlado por permisos numéricos asignados al rol del usuario. Los roles se gestionan desde `roles/` y se asignan por usuario en `usuarios/`.

---

## Temas visuales

El sistema soporta 3 temas configurables por usuario desde el perfil:

| Tema | Fondo | Acento |
|---|---|---|
| **Oscuro** (default) | `#0f172a` (azul marino) | Rosa `#e91e8c` |
| **Claro** | `#f1f5f9` (gris claro) | Rosa `#e91e8c` |
| **Rose** | `#1a0a14` (vino oscuro) | Rojo `#f43f5e` |

Los estilos viven en `public/css/premium.css` con variables CSS por tema (`data-theme`).

---

## Instalación (XAMPP)

1. Clonar o copiar en `htdocs/PROYECTO-V2/`
2. Crear la base de datos e importar el schema desde `database/migrations/`
3. Configurar credenciales en `app/config.php`:
   ```php
   $host = 'localhost';
   $db   = 'nombre_bd';
   $user = 'root';
   $pass = '';
   ```
4. Instalar dependencias:
   ```bash
   composer install
   ```
5. Acceder en `http://localhost/PROYECTO-V2`

---

## Estructura de carpetas relevante

```
PROYECTO-V2/
├── app/
│   ├── config.php              # Conexión BD y configuración global
│   └── controllers/
│       ├── stock/
│       │   ├── create_codigos.php   # Genera codigo_unico por paca
│       │   ├── salida.php           # Escaneo de salida (baja en venta)
│       │   ├── scan.php             # Escaneo de entrada (bodega)
│       │   └── devolver_paca.php    # Devolución de paca vendida
│       ├── ventas/
│       │   └── delete_venta.php     # Cancela venta y restaura stock
│       └── helpers/
│           ├── auditoria.php        # Registro de auditoría
│           └── print_zebra_empaque.php  # Genera ZPL para etiquetas
├── public/
│   └── css/premium.css         # Tema visual (dark/light/rose)
├── layout/
│   └── parte1.php              # Sidebar, navbar, badges de alerta
└── stock/
    └── salida.php              # UI de escaneo de salida
```

---

## Contacto y soporte

Luis Gabuardi — 8119058201
