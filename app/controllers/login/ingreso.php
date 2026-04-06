<?php

include('../../config.php');
include(__DIR__ . '/../helpers/validador.php');

session_start();

// Rate limiting: máx 5 intentos en 5 minutos
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$key = 'login_attempts_' . md5($ip);
if (!isset($_SESSION[$key])) {
    $_SESSION[$key] = ['count' => 0, 'time' => time()];
}
// Resetear si pasaron más de 5 minutos
if (time() - $_SESSION[$key]['time'] > 300) {
    $_SESSION[$key] = ['count' => 0, 'time' => time()];
}
if ($_SESSION[$key]['count'] >= 5) {
    $restante = 300 - (time() - $_SESSION[$key]['time']);
    $_SESSION['mensaje'] = "Demasiados intentos fallidos. Espera " . ceil($restante/60) . " minuto(s) para intentar nuevamente.";
    Logger::auth('RATE_LIMITED', $_POST['email'] ?? 'unknown', false, 'Demasiados intentos');
    header("Location: " . $URL . "/login/index.php");
    exit;
}

$email = $_POST['email'] ?? null;
$password_user = $_POST['password_user'] ?? null;

if (empty($email) || empty($password_user)) {
    error400('Email y contraseña son obligatorios');
    Logger::auth('LOGIN_ATTEMPT', $email ?? 'unknown', false, 'Datos incompletos');
    header("Location: " . $URL . "/login/index.php");
    exit;
}



$contador = 0;
$sql = "SELECT * FROM tb_usuario WHERE email = :email";
$query = $pdo->prepare($sql);
$query->bindParam(':email', $email);
$query->execute();
$usuarios = $query->fetchAll(PDO::FETCH_ASSOC);
foreach ($usuarios as $usuario) {
    $contador = $contador + 1;
    $email_tabla = $usuario['email'];
    $nombres = $usuario['nombres'];
    $password_user_tabla = $usuario['password_user'];
}

if(($contador > 0) && (password_verify($password_user, $password_user_tabla)))  {
    session_regenerate_id(true);
    unset($_SESSION[$key]); // limpiar intentos
    $_SESSION['sesion_email'] = $email_tabla;
    include('../../controllers/helpers/auditoria.php');
    registrarAuditoria($pdo, $usuario['id'] ?? null, $nombres, 'LOGIN', 'tb_usuario', null, 'Inicio de sesión exitoso');
    Logger::auth('LOGIN', $email_tabla, true);
    header("Location: " .$URL. "/index.php");
}else{
    $_SESSION[$key]['count']++;
    $_SESSION['mensaje'] = "❌ Datos incorrectos";
    Logger::auth('LOGIN', $email ?? 'unknown', false, 'Credenciales inválidas');
    header("Location: " .$URL. "/login/index.php");

}