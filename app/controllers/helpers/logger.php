<?php
/**
 * Sistema centralizado de logging
 * Registra: errores 500, errores 400, cambios en BD, accesos, etc.
 */

class Logger {
    private static $logDir = '';
    private static $initialized = false;

    public static function init($logDirectory = '') {
        if (self::$initialized) return;
        
        self::$logDir = empty($logDirectory) ? __DIR__ . '/../../logs' : $logDirectory;
        
        // Crear directorio si no existe
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }
        
        self::$initialized = true;
    }

    /**
     * Log de error 500 (excepciones del servidor)
     */
    public static function error500($exception, $context = []) {
        self::init();
        
        $data = [
            'timestamp'   => date('Y-m-d H:i:s'),
            'level'       => 'ERROR_500',
            'exception'   => get_class($exception),
            'message'     => $exception->getMessage(),
            'code'        => $exception->getCode(),
            'file'        => $exception->getFile(),
            'line'        => $exception->getLine(),
            'url'         => $_SERVER['REQUEST_URI'] ?? '',
            'method'      => $_SERVER['REQUEST_METHOD'] ?? '',
            'ip'          => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'user_id'     => $_SESSION['id_usuario_sesion'] ?? $_SESSION['id_usuario'] ?? null,
            'context'     => $context,
            'trace'       => $exception->getTraceAsString()
        ];
        
        self::writeLog('error_500', $data);
    }

    /**
     * Log de error 400 (solicitud mala/validación fallida)
     */
    public static function error400($message, $context = []) {
        self::init();
        
        $data = [
            'timestamp'   => date('Y-m-d H:i:s'),
            'level'       => 'ERROR_400',
            'message'     => $message,
            'url'         => $_SERVER['REQUEST_URI'] ?? '',
            'method'      => $_SERVER['REQUEST_METHOD'] ?? '',
            'ip'          => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_id'     => $_SESSION['id_usuario_sesion'] ?? $_SESSION['id_usuario'] ?? null,
            'context'     => $context
        ];
        
        self::writeLog('error_400', $data);
    }

    /**
     * Log de cambios en BD (CREATE, UPDATE, DELETE)
     */
    public static function database($action, $table, $id, $user_id, $user_name, $details = []) {
        self::init();
        
        $data = [
            'timestamp'   => date('Y-m-d H:i:s'),
            'level'       => 'DATABASE',
            'action'      => $action, // CREATE, UPDATE, DELETE
            'table'       => $table,
            'record_id'   => $id,
            'user_id'     => $user_id,
            'user_name'   => $user_name,
            'ip'          => $_SERVER['REMOTE_ADDR'] ?? '',
            'details'     => $details
        ];
        
        self::writeLog('database', $data);
    }

    /**
     * Log de acceso/autenticación
     */
    public static function auth($action, $email, $success, $reason = '') {
        self::init();
        
        $data = [
            'timestamp'   => date('Y-m-d H:i:s'),
            'level'       => 'AUTH',
            'action'      => $action, // LOGIN, LOGOUT, FAILED_LOGIN, PASSWORD_CHANGE
            'email'       => $email,
            'success'     => $success ? 'TRUE' : 'FALSE',
            'ip'          => $_SERVER['REMOTE_ADDR'] ?? '',
            'reason'      => $reason
        ];
        
        self::writeLog('auth', $data);
    }

    /**
     * Log de operaciones críticas
     */
    public static function critical($operation, $context = []) {
        self::init();
        
        $data = [
            'timestamp'   => date('Y-m-d H:i:s'),
            'level'       => 'CRITICAL',
            'operation'   => $operation,
            'url'         => $_SERVER['REQUEST_URI'] ?? '',
            'ip'          => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_id'     => $_SESSION['id_usuario_sesion'] ?? $_SESSION['id_usuario'] ?? null,
            'context'     => $context
        ];
        
        self::writeLog('critical', $data);
    }

    /**
     * Log informativo (cambios normales)
     */
    public static function info($message, $context = []) {
        self::init();
        
        $data = [
            'timestamp'   => date('Y-m-d H:i:s'),
            'level'       => 'INFO',
            'message'     => $message,
            'user_id'     => $_SESSION['id_usuario_sesion'] ?? $_SESSION['id_usuario'] ?? null,
            'context'     => $context
        ];
        
        self::writeLog('info', $data);
    }

    /**
     * Escribe el log en archivo JSON
     */
    private static function writeLog($type, $data) {
        try {
            // Crear archivo con nombre por fecha
            $date = date('Y-m-d');
            $filename = self::$logDir . "/log_{$type}_{$date}.jsonl";
            
            // Escribir como JSON Line (una línea por entrada)
            $line = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

            $umask_prev = umask(0);
            file_put_contents($filename, $line, FILE_APPEND | LOCK_EX);
            if (file_exists($filename)) chmod($filename, 0666);
            umask($umask_prev);
            
            // También escribir en error.log de PHP para compatibilidad
            error_log(json_encode($data));
            
        } catch (Exception $e) {
            // Fallar silenciosamente para no romper la aplicación
            error_log("Error escribiendo log: " . $e->getMessage());
        }
    }

    /**
     * Obtener últimos logs de un tipo
     */
    public static function getLogs($type, $limit = 100, $date = null) {
        self::init();
        
        if ($date === null) {
            $date = date('Y-m-d');
        }
        
        $filename = self::$logDir . "/log_{$type}_{$date}.jsonl";
        
        if (!file_exists($filename)) {
            return [];
        }
        
        $logs = [];
        $lines = array_reverse(explode("\n", file_get_contents($filename)));
        
        foreach ($lines as $line) {
            if (trim($line) === '') continue;
            $logs[] = json_decode($line, true);
            if (count($logs) >= $limit) break;
        }
        
        return $logs;
    }
}

// Inicializar logger
Logger::init();
