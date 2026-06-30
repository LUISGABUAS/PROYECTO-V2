<?php
session_start();
include_once('../app/controllers/helpers/csrf.php');

$mensaje = $_SESSION['mensaje'] ?? null;
if (isset($_SESSION['mensaje'])) {
    unset($_SESSION['mensaje']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pacas Yadira — Iniciar sesión</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap">
  <link rel="stylesheet" href="../public/templates/AdminLTE-3.2.0/plugins/fontawesome-free/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Poppins', sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
      position: relative;
      overflow: hidden;
    }

    /* Círculos decorativos de fondo */
    body::before, body::after {
      content: '';
      position: absolute;
      border-radius: 50%;
      opacity: .08;
    }
    body::before {
      width: 600px; height: 600px;
      background: #e91e8c;
      top: -150px; right: -150px;
    }
    body::after {
      width: 400px; height: 400px;
      background: #00b4d8;
      bottom: -100px; left: -100px;
    }

    .login-wrapper {
      position: relative;
      z-index: 10;
      width: 100%;
      max-width: 420px;
      padding: 20px;
    }

    /* Logo / Marca */
    .brand {
      text-align: center;
      margin-bottom: 28px;
    }
    .brand-icon {
      width: 72px; height: 72px;
      background: linear-gradient(135deg, #e91e8c, #ff6b35);
      border-radius: 20px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 14px;
      box-shadow: 0 8px 24px rgba(233,30,140,.4);
    }
    .brand-icon i {
      font-size: 32px;
      color: #fff;
    }
    .brand h1 {
      color: #fff;
      font-size: 28px;
      font-weight: 700;
      letter-spacing: 1px;
    }
    .brand h1 span {
      color: #e91e8c;
    }
    .brand p {
      color: rgba(255,255,255,.5);
      font-size: 13px;
      margin-top: 4px;
    }

    /* Card */
    .login-card {
      background: rgba(255,255,255,.05);
      backdrop-filter: blur(18px);
      -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(255,255,255,.12);
      border-radius: 20px;
      padding: 36px 32px;
      box-shadow: 0 20px 60px rgba(0,0,0,.4);
    }

    .login-card h2 {
      color: #fff;
      font-size: 20px;
      font-weight: 600;
      margin-bottom: 6px;
    }
    .login-card p.sub {
      color: rgba(255,255,255,.45);
      font-size: 13px;
      margin-bottom: 28px;
    }

    /* Inputs */
    .field {
      margin-bottom: 18px;
    }
    .field label {
      display: block;
      color: rgba(255,255,255,.7);
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .8px;
      margin-bottom: 8px;
    }
    .input-wrap {
      position: relative;
    }
    .input-wrap i {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: rgba(255,255,255,.35);
      font-size: 15px;
    }
    .input-wrap input {
      width: 100%;
      background: rgba(255,255,255,.08);
      border: 1px solid rgba(255,255,255,.15);
      border-radius: 10px;
      padding: 12px 14px 12px 42px;
      color: #fff;
      font-size: 14px;
      font-family: 'Poppins', sans-serif;
      transition: border-color .2s, background .2s;
      outline: none;
    }
    .input-wrap input::placeholder { color: rgba(255,255,255,.3); }
    .input-wrap input:focus {
      border-color: #e91e8c;
      background: rgba(233,30,140,.08);
    }

    /* Botón */
    .btn-login {
      width: 100%;
      padding: 13px;
      background: linear-gradient(135deg, #e91e8c, #ff6b35);
      border: none;
      border-radius: 10px;
      color: #fff;
      font-size: 15px;
      font-weight: 600;
      font-family: 'Poppins', sans-serif;
      cursor: pointer;
      margin-top: 8px;
      transition: opacity .2s, transform .15s;
      box-shadow: 0 6px 20px rgba(233,30,140,.35);
    }
    .btn-login:hover  { opacity: .92; }
    .btn-login:active { transform: scale(.98); }

    /* Footer */
    .login-footer {
      text-align: center;
      margin-top: 22px;
      color: rgba(255,255,255,.25);
      font-size: 12px;
    }
  </style>
</head>
<body>

<?php if ($mensaje): ?>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    Swal.fire({ icon:'error', title:'Acceso denegado', text: <?= json_encode($mensaje) ?>, confirmButtonColor:'#e91e8c' });
  });
</script>
<?php endif; ?>

<div class="login-wrapper">

  <!-- Marca -->
  <div class="brand">
    <div class="brand-icon">
      <i class="fas fa-boxes"></i>
    </div>
    <h1>Pacas <span>Yadira</span></h1>
    <p>Sistema de gestión de inventario</p>
  </div>

  <!-- Card de login -->
  <div class="login-card">
    <h2>Bienvenido</h2>
    <p class="sub">Ingresa tus credenciales para continuar</p>

    <form action="../app/controllers/login/ingreso.php" method="post">
      <?= csrf_field() ?>

      <div class="field">
        <label>Correo electrónico</label>
        <div class="input-wrap">
          <i class="fas fa-envelope"></i>
          <input type="email" name="email" placeholder="tucorreo@ejemplo.com" required autofocus>
        </div>
      </div>

      <div class="field">
        <label>Contraseña</label>
        <div class="input-wrap">
          <i class="fas fa-lock"></i>
          <input type="password" name="password_user" placeholder="••••••••" required>
        </div>
      </div>

      <button type="submit" class="btn-login">
        <i class="fas fa-sign-in-alt mr-2"></i> Iniciar sesión
      </button>
    </form>
  </div>

  <div class="login-footer">
    &copy; <?= date('Y') ?> Pacas Yadira &mdash; Todos los derechos reservados
  </div>

</div>

</body>
</html>
