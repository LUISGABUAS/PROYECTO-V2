<?php
/**
 * Sistema de licencia mensual.
 * El sistema se bloquea a partir del día 22 de cada mes si no se ha registrado el pago.
 * Solo el propietario (PROPIETARIO_ID) puede desbloquearlo.
 */

function licencia_bloqueada(PDO $pdo): bool
{
    $hoy = new DateTime('now', new DateTimeZone('America/Monterrey'));

    // Antes del día 22: siempre libre
    if ((int)$hoy->format('d') < 22) return false;

    $periodo = $hoy->format('Y-m');
    try {
        $stmt = $pdo->prepare("SELECT pagado FROM tb_licencia WHERE periodo = ?");
        $stmt->execute([$periodo]);
        $row = $stmt->fetch();
        return !($row && (int)$row['pagado'] === 1);
    } catch (Exception $e) {
        return false; // fail open: si la tabla no existe aún, no bloquear
    }
}

function licencia_verificar(PDO $pdo, string $URL): void
{
    $uri = $_SERVER['REQUEST_URI'] ?? '';

    // Páginas exentas del bloqueo
    if (
        strpos($uri, '/licencia/') !== false ||
        strpos($uri, 'registrar_pago') !== false ||
        strpos($uri, 'cerrar_sesion') !== false
    ) return;

    if (licencia_bloqueada($pdo)) {
        header("Location: {$URL}/licencia/bloqueado.php");
        exit;
    }
}

function es_propietario(): bool
{
    return (int)($_SESSION['id_usuario_sesion'] ?? 0) === PROPIETARIO_ID;
}

function licencia_periodo_actual(): string
{
    return (new DateTime('now', new DateTimeZone('America/Monterrey')))->format('Y-m');
}
