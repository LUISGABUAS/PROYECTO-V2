<?php
include('../app/config.php');
require_once('../app/controllers/helpers/licencia.php');

if (session_status() === PHP_SESSION_NONE) session_start();

// Si no hay sesión, mandar a login
if (!isset($_SESSION['sesion_email'])) {
    header("Location: {$URL}/login/index.php"); exit;
}

// Si el sistema ya está desbloqueado, redirigir al inicio
if (!licencia_bloqueada($pdo)) {
    header("Location: {$URL}/"); exit;
}

// Cargar datos mínimos del usuario si no están en caché
if (!isset($_SESSION['id_usuario_sesion'])) {
    $q = $pdo->prepare("SELECT us.id, us.nombres FROM tb_usuario us WHERE us.email = ? LIMIT 1");
    $q->execute([$_SESSION['sesion_email']]);
    $u = $q->fetch();
    if ($u) {
        $_SESSION['id_usuario_sesion'] = $u['id'];
        $_SESSION['sesion_nombres']    = $u['nombres'];
    }
}

$propietario = es_propietario();
$hoy = new DateTime('now', new DateTimeZone('America/Monterrey'));
$proximo_22 = clone $hoy;
if ((int)$hoy->format('d') >= 22) {
    $proximo_22->modify('first day of next month');
    $proximo_22->setDate((int)$proximo_22->format('Y'), (int)$proximo_22->format('m'), 22);
} else {
    $proximo_22->setDate((int)$hoy->format('Y'), (int)$hoy->format('m'), 22);
}
$dias_vencido = (int)$hoy->diff(
    (new DateTime($hoy->format('Y-m') . '-22', new DateTimeZone('America/Monterrey')))
)->days;
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sistema suspendido — Pacas Yadira</title>
  <link rel="icon" type="image/png" href="<?= $URL ?>/pacasyadira.png">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
  <script defer src="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/js/all.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <meta name="csrf-token" content="<?php
    require_once('../app/controllers/helpers/csrf.php');
    echo csrf_token();
  ?>">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Inter', sans-serif;
      background: #0f172a;
      color: #e2e8f0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .card {
      background: #1e293b;
      border: 1px solid rgba(255,255,255,.08);
      border-radius: 24px;
      padding: 48px 40px;
      max-width: 480px;
      width: 100%;
      text-align: center;
      box-shadow: 0 20px 60px rgba(0,0,0,.5);
    }
    .icon-lock {
      font-size: 56px;
      color: #ef4444;
      margin-bottom: 20px;
    }
    h1 {
      font-size: 24px;
      font-weight: 700;
      color: #f1f5f9;
      margin-bottom: 10px;
    }
    p {
      color: #94a3b8;
      font-size: 15px;
      line-height: 1.6;
      margin-bottom: 8px;
    }
    .dias-badge {
      display: inline-block;
      background: rgba(239,68,68,.15);
      color: #fca5a5;
      border: 1px solid rgba(239,68,68,.3);
      border-radius: 8px;
      padding: 6px 14px;
      font-size: 13px;
      font-weight: 600;
      margin: 16px 0 24px;
    }
    .btn-pagar {
      background: linear-gradient(135deg, #e91e8c, #ff6b35);
      color: #fff;
      border: none;
      border-radius: 14px;
      padding: 16px 32px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      width: 100%;
      transition: filter .2s, transform .15s;
      font-family: 'Inter', sans-serif;
    }
    .btn-pagar:hover { filter: brightness(1.1); transform: translateY(-2px); }
    .btn-pagar:active { transform: translateY(0); }
    .btn-salir {
      display: block;
      margin-top: 16px;
      color: #64748b;
      font-size: 13px;
      text-decoration: none;
    }
    .btn-salir:hover { color: #94a3b8; }
    .logo {
      width: 56px;
      height: 56px;
      object-fit: cover;
      border-radius: 50%;
      margin-bottom: 20px;
      opacity: .8;
    }
    .msg-no-propietario {
      background: rgba(245,158,11,.1);
      border: 1px solid rgba(245,158,11,.25);
      border-radius: 12px;
      color: #fcd34d;
      padding: 14px 18px;
      font-size: 14px;
      margin-top: 16px;
    }
  </style>
</head>
<body>
  <div class="card">
    <img src="<?= $URL ?>/pacasyadira.png" alt="Logo" class="logo">
    <div class="icon-lock"><i class="fas fa-lock"></i></div>
    <h1>Sistema suspendido</h1>
    <p>El acceso al sistema está bloqueado porque la mensualidad de <strong><?= $hoy->format('F Y') ?></strong> no ha sido registrada.</p>
    <div class="dias-badge">
      <i class="fas fa-calendar-times"></i>
      Vencido desde el día 22 de <?= $hoy->format('F') ?>
    </div>

    <?php if ($propietario): ?>
      <p style="color:#e2e8f0;font-size:14px;margin-bottom:20px;">
        Registra el pago para desbloquear el sistema para todos los usuarios.
      </p>
      <button class="btn-pagar" id="btnPagar" onclick="registrarPago()">
        <i class="fas fa-check-circle"></i>&nbsp; Registrar pago de mensualidad
      </button>
    <?php else: ?>
      <div class="msg-no-propietario">
        <i class="fas fa-info-circle"></i>
        Contacta al administrador del sistema para reactivar el acceso.
      </div>
    <?php endif; ?>

    <a href="<?= $URL ?>/login/cerrar_sesion.php" class="btn-salir">
      <i class="fas fa-sign-out-alt"></i> Cerrar sesión
    </a>
  </div>

  <script>
  function registrarPago() {
    Swal.fire({
      title: '¿Confirmar pago?',
      text: 'Se registrará la mensualidad de <?= $hoy->format('F Y') ?> y el sistema quedará activo.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, registrar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#e91e8c',
      background: '#1e293b',
      color: '#e2e8f0'
    }).then(result => {
      if (!result.isConfirmed) return;

      const btn = document.getElementById('btnPagar');
      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registrando...';

      fetch('<?= $URL ?>/app/controllers/licencia/registrar_pago.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: '_csrf=' + encodeURIComponent(document.querySelector('meta[name="csrf-token"]').content)
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          Swal.fire({
            title: '¡Listo!',
            text: data.message,
            icon: 'success',
            confirmButtonColor: '#e91e8c',
            background: '#1e293b',
            color: '#e2e8f0'
          }).then(() => { window.location.href = '<?= $URL ?>'; });
        } else {
          Swal.fire({ title: 'Error', text: data.message, icon: 'error', background: '#1e293b', color: '#e2e8f0' });
          btn.disabled = false;
          btn.innerHTML = '<i class="fas fa-check-circle"></i> Registrar pago de mensualidad';
        }
      });
    });
  }
  </script>

  <?php if (!$propietario): ?>
  <script>
  // Polling cada 15 s: si el sistema se desbloquea, redirigir automáticamente
  setInterval(function() {
    fetch('<?= $URL ?>/licencia/check_status.php', { cache: 'no-store' })
      .then(r => r.json())
      .then(data => { if (data.desbloqueado) window.location.href = '<?= $URL ?>/'; })
      .catch(() => {});
  }, 15000);
  </script>
  <?php endif; ?>
</body>
</html>
