<?php
/**
 * Helper para validación y manejo de errores 400
 * Registra errores de validación, datos inválidos, etc.
 */

function validarDatos($datos_requeridos, $datos_enviados = null) {
    if ($datos_enviados === null) {
        $datos_enviados = $_POST;
    }
    
    $errores = [];
    
    foreach ($datos_requeridos as $campo) {
        if (empty($datos_enviados[$campo]) || !isset($datos_enviados[$campo])) {
            $errores[$campo] = "El campo $campo es obligatorio";
        }
    }
    
    if (!empty($errores)) {
        Logger::error400("Validación fallida - Datos incompletos", [
            'campos_requeridos' => $datos_requeridos,
            'campos_recibidos'  => array_keys($datos_enviados),
            'errores'           => $errores,
            'url'               => $_SERVER['REQUEST_URI'] ?? ''
        ]);
    }
    
    return $errores;
}

/**
 * Error 400 - Validación fallida
 */
function error400($mensaje, $datos = []) {
    Logger::error400($mensaje, $datos);
    
    http_response_code(400);
    
    $_SESSION['mensaje'] = "❌ " . $mensaje;
    $_SESSION['icono']   = "error";
    
    // Retornar como JSON si es AJAX
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $mensaje]);
        exit;
    }
}

/**
 * Error 500 - Error interno
 */
function error500($mensaje, $datos = []) {
    Logger::critical($mensaje, $datos);
    
    http_response_code(500);
    
    $_SESSION['mensaje'] = "❌ Error interno del servidor";
    $_SESSION['icono']   = "error";
    
    // Retornar como JSON si es AJAX
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => "Error interno del servidor"]);
        exit;
    }
}

/**
 * Validar email
 */
function validarEmail($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        Logger::error400("Email inválido", ['email' => $email]);
        return false;
    }
    return true;
}

/**
 * Validar token CSRF
 */
function validarCSRF($token) {
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        Logger::error400("Token CSRF inválido", [
            'recibido' => substr($token, 0, 10) . '...',
            'sesion'   => empty($_SESSION['csrf_token']) ? 'no_generado' : 'existe'
        ]);
        return false;
    }
    return true;
}
