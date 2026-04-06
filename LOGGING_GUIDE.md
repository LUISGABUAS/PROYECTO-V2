# 📊 Sistema de Observabilidad y Logging

## 📋 Descripción

Sistema centralizado de logging que captura y registra:
- ❌ **Errores 500**: Excepciones del servidor, errores no capturados
- ⚠️ **Errores 400**: Validaciones fallidas, solicitudes malformadas
- 💾 **Cambios en BD**: CREATE, UPDATE, DELETE de registros
- 🔐 **Autenticación**: LOGIN, LOGOUT, intentos fallidos
- 🚨 **Críticos**: Operaciones sensibles del sistema
- ℹ️ **Informativos**: Cambios normales de la aplicación

## 🗂️ Archivos del Sistema

```
app/controllers/helpers/
├── logger.php          # Clase Logger Principal
├── validador.php       # Funciones para validación y errores 400
└── auditoria.php       # Mejorado para usar Logger

app/config.php          # Integración global del logger
logs/
├── log_error_500_YYYY-MM-DD.jsonl
├── log_error_400_YYYY-MM-DD.jsonl
├── log_database_YYYY-MM-DD.jsonl
├── log_auth_YYYY-MM-DD.jsonl
├── log_critical_YYYY-MM-DD.jsonl
└── log_info_YYYY-MM-DD.jsonl

dashboard/logs.php      # Panel de visualización de logs
```

## 🔧 Uso en Controladores

### Registrar Error 500 (Excepciones)
```php
try {
    // Código que puede fallar
} catch (Exception $e) {
    Logger::error500($e, ['contexto' => 'información adicional']);
}
```

### Registrar Error 400 (Validación)
```php
$errores = validarDatos(['nombre', 'email']);
if (!empty($errores)) {
    error400("Datos incompletos", $errores);
}
```

### Registrar Cambio en BD
```php
Logger::database('UPDATE', 'usuarios', $id_usuario, $usuario_id, $usuario_nombre, [
    'campo_modificado' => 'email',
    'valor_anterior' => 'viejo@email.com',
    'valor_nuevo' => 'nuevo@email.com'
]);
```

### Registrar Autenticación
```php
Logger::auth('LOGIN', $email, $success === true, $success ? '' : 'credenciales inválidas');
```

### Registrar Operación Crítica
```php
Logger::critical('ELIMINACION_MASIVA_USUARIOS', [
    'cantidad' => 50,
    'motivo' => 'limpieza de base de datos'
]);
```

### Registrar Información
```php
Logger::info("Importación de CSV completada", [
    'archivo' => 'productos.csv',
    'registros' => 150
]);
```

## 📊 Panel de Observabilidad

Accede a: `/dashboard/logs.php`

**Características:**
- Visualizar logs por tipo y fecha
- Filtros en tiempo real
- Detalles expandibles en JSON
- Estadísticas de errores
- Limpiar logs antiguos

## 📁 Formato de Logs

Cada log se guarda en formato **JSON Lines** (una línea JSON por entrada):

```json
{
  "timestamp": "2026-04-06 14:30:45",
  "level": "ERROR_500",
  "exception": "PDOException",
  "message": "SQLSTATE[HY000]...",
  "code": 0,
  "file": "/var/www/app/config.php",
  "line": 25,
  "url": "/clientes/create.php",
  "method": "POST",
  "ip": "192.168.1.100",
  "user_agent": "Mozilla/5.0...",
  "user_id": 5,
  "context": {},
  "trace": "Stack trace..."
}
```

## 🔐 Seguridad

- ✅ Solo acceso para administradores
- ✅ IPs registradas en cada log
- ✅ User agent para detectar bots
- ✅ No se registran contraseñas
- ✅ Logs organizados por fecha
- ✅ Permisos de archivo restrictivos

## 🎯 Buenas Prácticas

1. **En cada controlador importante, rodea el código con try-catch**
   ```php
   try {
       // Tu código
   } catch (Exception $e) {
       Logger::error500($e);
       error500("Error procesando la solicitud");
   }
   ```

2. **Valida entrada del usuario**
   ```php
   $errores = validarDatos(['email', 'nombre']);
   if (!empty($errores)) {
       error400("Datos incompletos", $errores);
   }
   ```

3. **Registra cambios importantes en BD**
   ```php
   Logger::database('DELETE', 'productos', $id, $user_id, $user_name, [
       'motivo' => 'producto discontinuado'
   ]);
   ```

4. **Revisa logs regularmente**
   - Accede a `/dashboard/logs.php`
   - Revisa errores 500 y 400
   - Identifica patrones de problemas

## 📈 Monitoreo Recomendado

- **Diarios**: Revisar errores 500
- **Semanales**: Analizar errores 400 y patrones
- **Mensuales**: Limpiar logs antiguos
- **Críticos**: Alertas automáticas por email (opcional)

## 🚀 Próximas Mejoras

- [ ] Exportar logs a CSV/Excel
- [ ] Alertas por email en errores críticos
- [ ] Dashboard en tiempo real con WebSockets
- [ ] Integración con Slack/Discord
- [ ] Rotación automática de logs
- [ ] Compresión de logs antiguos
