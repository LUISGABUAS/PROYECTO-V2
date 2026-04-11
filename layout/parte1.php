<?php
// ========== BADGES SIDEBAR ==========
$badge_stock_bajo = 0;
$badge_ventas_pendientes = 0;

if (in_array(8, $_SESSION['permisos'] ?? []) || in_array(24, $_SESSION['permisos'] ?? [])) {
    $stmt_badge = $pdo->prepare("
        SELECT COUNT(*)
        FROM tb_almacen a
        LEFT JOIN (
            SELECT id_producto, COUNT(*) AS bodega
            FROM stock WHERE estado = 'EN BODEGA'
            GROUP BY id_producto
        ) sb ON sb.id_producto = a.id_producto
        LEFT JOIN (
            SELECT id_producto, SUM(cantidad - cantidad_entregada) AS pendiente
            FROM tb_ventas_detalle WHERE cantidad_entregada < cantidad
            GROUP BY id_producto
        ) sp ON sp.id_producto = a.id_producto
        WHERE a.stock_minimo > 0
        AND (COALESCE(sb.bodega, 0) - COALESCE(sp.pendiente, 0)) <= a.stock_minimo
    ");
    $stmt_badge->execute();
    $badge_stock_bajo = (int)$stmt_badge->fetchColumn();
}

if (in_array(24, $_SESSION['permisos'] ?? [])) {
    $stmt_badge2 = $pdo->prepare("SELECT COUNT(*) FROM tb_ventas WHERE estado_logistico IN ('SIN ENVIO', 'PENDIENTE GUIA')");
    $stmt_badge2->execute();
    $badge_ventas_pendientes = (int)$stmt_badge2->fetchColumn();
}

$badge_tickets = 0;
if (in_array(37, $_SESSION['permisos'] ?? [])) {
    try {
        $stmt_badge3 = $pdo->prepare("SELECT COUNT(*) FROM tb_tickets WHERE estado IN ('pendiente','en_progreso')");
        $stmt_badge3->execute();
        $badge_tickets = (int)$stmt_badge3->fetchColumn();
    } catch (Exception $e) { $badge_tickets = 0; }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title> Inventario Pacas Yadira</title>
  <!-- LOGO TAB -->
  <link rel="icon" type="image/png" href="<?php echo $URL; ?>/pacasyadira.png">

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="<?php echo $URL?>/public/templates/AdminLTE-3.2.0/plugins/fontawesome-free/css/all.min.css">
  <!-- DataTables -->
  <link rel="stylesheet" href="<?php echo $URL?>/public/templates/AdminLTE-3.2.0/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="<?php echo $URL?>/public/templates/AdminLTE-3.2.0/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="<?php echo $URL?>/public/templates/AdminLTE-3.2.0/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="<?php echo $URL?>/public/templates/AdminLTE-3.2.0/dist/css/adminlte.min.css">
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- jQuery -->
  <script src="<?php echo $URL?>/public/templates/AdminLTE-3.2.0/plugins/jquery/jquery.min.js"></script>
  <!-- CSRF Token -->
  <meta name="csrf-token" content="<?php echo csrf_token(); ?>">
  <script>
    $(function() {
      var csrfToken = $('meta[name="csrf-token"]').attr('content');
      $.ajaxSetup({
        headers: { 'X-CSRF-Token': csrfToken }
      });
    });
  </script>
</head>
<body class="hold-transition sidebar-mini layout-fixed">

<div class="wrapper">

  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="https://wa.me/5218119058201?text=Hola,%20necesito%20ayuda%20con%20el%20sistema%20de%20inventario%20de%20Pacas%20Yadira" class="nav-link">Contacto</a>
      </li>
    </ul>
  </nav>

  <!-- Sidebar -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="<?php echo $URL?>/index.php" class="brand-link">
      <img src="<?php echo $URL?>/pacasyadira.png" alt="Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
      <span class="brand-text font-weight-light">Pacas Yadira</span>
    </a>

    <div class="sidebar">

      <!-- Panel de usuario -->
      <div class="user-panel mt-3 pb-3 mb-3 d-flex" id="userPanelProfile" style="cursor: pointer;" title="Clic para editar tu perfil">
        <div class="image">
          <img src="<?php 
            if (!empty($sesion_foto)) {
              echo $URL . '/' . $sesion_foto;
            } else {
              echo $URL . '/public/templates/AdminLTE-3.2.0/dist/img/user2-160x160.jpg';
            }
          ?>" id="userProfileImage" class="img-circle elevation-2" alt="Foto de usuario" style="width: 40px; height: 40px; object-fit: cover;">
        </div>
        <div class="info">
          <a href="#" class="d-block" id="userProfileName"><?php echo $sesion_nombres; ?></a>
        </div>
      </div>

      <!-- Buscador del sidebar -->
      <div class="form-inline">
        <div class="input-group" data-widget="sidebar-search">
          <input class="form-control form-control-sidebar" type="search" placeholder="Buscar" aria-label="Buscar">
          <div class="input-group-append">
            <button class="btn btn-sidebar">
              <i class="fas fa-search fa-fw"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Menú del sidebar -->
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

          <!-- DASHBOARDS -->
          <?php if(in_array(24, $_SESSION['permisos']) || in_array(2, $_SESSION['permisos'])): ?>
          <li class="nav-item">
            <a href="#" class="nav-link active">
              <i class="nav-icon fas fa-chart-line"></i>
              <p>Reportes <i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
              <?php if(in_array(24, $_SESSION['permisos'])): ?>
              <li class="nav-item">
                <a href="<?php echo $URL;?>/dashboard/admin.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Administrador</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?php echo $URL;?>/dashboard/foraneos.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Ventas Foráneos</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?php echo $URL;?>/dashboard/locales.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Ventas Locales</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?php echo $URL;?>/ventas/reportes.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Reportes Ampliados</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?php echo $URL;?>/auditoria/index.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Historial de Cambios</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?php echo $URL;?>/dashboard/logs.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Logs</p>
                </a>
              </li>
              <?php endif; ?>
              <li class="nav-item">
                <a href="<?php echo $URL;?>/dashboard/vendidos.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Detalle Vendidos</p>
                </a>
              </li>
            </ul>
          </li>
          <?php endif; ?>

          <!-- USUARIOS -->
          <?php if(in_array(1, $_SESSION['permisos']) || in_array(2, $_SESSION['permisos'])): ?>
          <li class="nav-item">
            <a href="#" class="nav-link active">
              <i class="nav-icon fas fa-user-plus"></i>
              <p>Usuarios <i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
              <?php if(in_array(1, $_SESSION['permisos'])): ?>
              <li class="nav-item">
                <a href="<?php echo $URL;?>/usuarios" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Listado</p>
                </a>
              </li>
              <?php endif; ?>
              <?php if(in_array(2, $_SESSION['permisos'])): ?>
              <li class="nav-item">
                <a href="<?php echo $URL;?>/usuarios/create.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Crear</p>
                </a>
              </li>
              <?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>

          <!-- ROLES -->
          <?php if(in_array(3, $_SESSION['permisos']) || in_array(4, $_SESSION['permisos'])): ?>
          <li class="nav-item">
            <a href="#" class="nav-link active">
              <i class="nav-icon fas fa-address-card"></i>
              <p>Roles <i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
              <?php if(in_array(3, $_SESSION['permisos'])): ?>
              <li class="nav-item">
                <a href="<?php echo $URL;?>/roles" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Listado de Roles</p>
                </a>
              </li>
              <?php endif; ?>
              <?php if(in_array(4, $_SESSION['permisos'])): ?>
              <li class="nav-item">
                <a href="<?php echo $URL;?>/roles/create.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Crear Rol</p>
                </a>
              </li>
              <?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>

          <!-- CATEGORÍAS -->
          <?php if(in_array(5, $_SESSION['permisos']) || in_array(6, $_SESSION['permisos']) || in_array(7, $_SESSION['permisos'])): ?>
          <li class="nav-item">
            <a href="#" class="nav-link active">
              <i class="nav-icon fas fa-tags"></i>
              <p>Categorías <i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="<?php echo $URL;?>/categorias" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Listado</p>
                </a>
              </li>
            </ul>
          </li>
          <?php endif; ?>

          <!-- PRODUCTOS -->
          <?php if(in_array(8, $_SESSION['permisos']) || in_array(9, $_SESSION['permisos']) || in_array(10, $_SESSION['permisos'])): ?>
          <li class="nav-item">
            <a href="#" class="nav-link active" style="position:relative;">
              <i class="nav-icon fas fa-store"></i>
              <p>Productos <i class="right fas fa-angle-left"></i><?php if($badge_stock_bajo > 0): ?><span class="badge badge-danger" style="position:absolute;right:30px;top:50%;transform:translateY(-50%);"><?= $badge_stock_bajo ?></span><?php endif; ?></p>
            </a>
            <ul class="nav nav-treeview">
              <?php if(in_array(8, $_SESSION['permisos'])): ?>
              <li class="nav-item">
                <a href="<?php echo $URL;?>/almacen" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Listado</p>
                </a>
              </li>
              <?php endif; ?>
              <?php if(in_array(9, $_SESSION['permisos'])): ?>
              <li class="nav-item">
                <a href="<?php echo $URL;?>/almacen/create.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Crear Producto</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?php echo $URL;?>/almacen/import.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Importar CSV</p>
                </a>
              </li>
              <?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>

          <!-- BODEGA -->
          <?php if(in_array(12, $_SESSION['permisos']) || in_array(13, $_SESSION['permisos']) || in_array(14, $_SESSION['permisos'])): ?>
          <li class="nav-item">
            <a href="#" class="nav-link active">
              <i class="nav-icon fas fa-list"></i>
              <p>Bodega <i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
              <?php if(in_array(12, $_SESSION['permisos'])): ?>
              <li class="nav-item">
                <a href="<?php echo $URL;?>/stock/create.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Crear</p>
                </a>
              </li>
              <?php endif; ?>
              <?php if(in_array(14, $_SESSION['permisos'])): ?>
              <li class="nav-item">
                <a href="<?php echo $URL;?>/stock/scan.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Escanear Nuevos</p>
                </a>
              </li>
              <?php endif; ?>
              <?php if(in_array(13, $_SESSION['permisos'])): ?>
              <li class="nav-item">
                <a href="<?php echo $URL;?>/stock/salida.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Escanear Salida</p>
                </a>
              </li>
              <?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>

          <!-- PROVEEDORES -->
          <?php if(in_array(15, $_SESSION['permisos'])): ?>
          <li class="nav-item">
            <a href="#" class="nav-link active">
              <i class="nav-icon fas fa-building"></i>
              <p>Proveedores <i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="<?php echo $URL;?>/provedores" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Listado</p>
                </a>
              </li>
            </ul>
          </li>
          <?php endif; ?>

          <!-- VENTAS -->
          <?php if(in_array(20, $_SESSION['permisos'])): ?>
          <li class="nav-item">
            <a href="#" class="nav-link active" style="position:relative;">
              <i class="nav-icon fas fa-dollar-sign"></i>
              <p>Ventas <i class="right fas fa-angle-left"></i><?php if($badge_ventas_pendientes > 0): ?><span class="badge badge-warning" style="position:absolute;right:30px;top:50%;transform:translateY(-50%);"><?= $badge_ventas_pendientes ?></span><?php endif; ?></p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="<?php echo $URL;?>/ventas" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Reporte</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?php echo $URL;?>/ventas/create.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Crear Venta</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?php echo $URL;?>/ventas/cotizaciones.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Cotizar</p>
                </a>
              </li>
            </ul>
          </li>
          <?php endif; ?>

          <!-- CLIENTES -->
          <?php if(in_array(23, $_SESSION['permisos'])): ?>
          <li class="nav-item">
            <a href="#" class="nav-link active">
              <i class="nav-icon fas fa-users"></i>
              <p>Clientes <i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="<?php echo $URL;?>/clientes" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Reporte</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?php echo $URL;?>/clientes/locales.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Locales</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?php echo $URL;?>/clientes/foraneos.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Foráneos</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?php echo $URL;?>/clientes/create.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Crear</p>
                </a>
              </li>
            </ul>
          </li>
          <?php endif; ?>

          <!-- TICKETS DE SOPORTE -->
          <?php if(in_array(35, $_SESSION['permisos'] ?? []) || in_array(37, $_SESSION['permisos'] ?? [])): ?>
          <li class="nav-item">
            <a href="#" class="nav-link active" style="position:relative;">
              <i class="nav-icon fas fa-ticket-alt"></i>
              <p>Soporte <i class="right fas fa-angle-left"></i>
                <?php if($badge_tickets > 0): ?>
                <span class="badge badge-danger" style="position:absolute;right:30px;top:50%;transform:translateY(-50%);"><?= $badge_tickets ?></span>
                <?php endif; ?>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="<?php echo $URL;?>/tickets" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p><?= in_array(37, $_SESSION['permisos'] ?? []) ? 'Todos los Tickets' : 'Mis Tickets' ?></p>
                </a>
              </li>
              <?php if(in_array(36, $_SESSION['permisos'] ?? [])): ?>
              <li class="nav-item">
                <a href="<?php echo $URL;?>/tickets/create.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Nuevo Ticket</p>
                </a>
              </li>
              <?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>

          <!-- CHANGELOG -->
          <?php if(in_array(39, $_SESSION['permisos'] ?? [])): ?>
          <li class="nav-item">
            <a href="<?php echo $URL;?>/changelog" class="nav-link">
              <i class="nav-icon fab fa-git-alt"></i>
              <p>Changelog</p>
            </a>
          </li>
          <?php endif; ?>

          <!-- CERRAR SESIÓN -->
          <li class="nav-item">
            <a href="#" class="nav-link" style="background-color: rgba(177, 16, 16, 1);" onclick="confirmarLogout()">
              <i class="nav-icon fas fa-door-closed"></i>
              <p>Cerrar Sesión</p>
            </a>
          </li>

        </ul>
      </nav>
    </div>
  </aside>

  <!-- Modal editar perfil -->
  <div class="modal fade" id="editProfileModal" tabindex="-1" role="dialog" aria-labelledby="editProfileLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header bg-primary">
          <h5 class="modal-title" id="editProfileLabel">
            <i class="fas fa-user-circle"></i> Editar Mi Perfil
          </h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form id="editProfileForm" enctype="multipart/form-data">
          <div class="modal-body">
            <div class="row">
              <div class="col-md-4 text-center">
                <div class="form-group">
                  <img id="previewProfileImage" src="<?php echo $URL?>/public/templates/AdminLTE-3.2.0/dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="Foto de usuario" style="width: 150px; height: 150px; object-fit: cover; border: 3px solid #007bff;">
                  <div class="mt-3">
                    <label for="profileImage" class="btn btn-sm btn-info">
                      <i class="fas fa-camera"></i> Cambiar Foto
                    </label>
                    <input type="file" id="profileImage" name="profileImage" accept="image/*" style="display: none;">
                  </div>
                  <div class="mt-3">
                    <div class="form-group">
                      <label style="font-weight: bold;">Tu Rol</label>
                      <p id="rolDisplay" class="form-control-plaintext" style="border: 1px solid #dee2e6; padding: 0.5rem; border-radius: 0.25rem; background-color: #f8f9fa;">-</p>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-8">
                <div class="form-group">
                  <label for="nombres">Nombre Completo</label>
                  <input type="text" class="form-control" id="nombres" name="nombres" required>
                </div>
                <div class="form-group">
                  <label for="email">Correo Electrónico</label>
                  <input type="email" class="form-control" id="email" name="email" autocomplete="email" required>
                </div>
                <div class="form-group">
                  <label for="password_actual">Contraseña Actual</label>
                  <input type="password" class="form-control" id="password_actual" name="password_actual" autocomplete="current-password">
                  <small class="text-muted">Requerido solo si deseas cambiar tu contraseña</small>
                </div>
                <div class="form-group">
                  <label for="password_nueva">Nueva Contraseña</label>
                  <input type="password" class="form-control" id="password_nueva" name="password_nueva" autocomplete="new-password">
                  <small class="text-muted">Déjalo en blanco si no deseas cambiarla</small>
                </div>
                <div class="form-group">
                  <label for="password_confirmacion">Confirmar Contraseña</label>
                  <input type="password" class="form-control" id="password_confirmacion" name="password_confirmacion" autocomplete="new-password">
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save"></i> Guardar Cambios
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    // Panel de usuario clickeable
    document.getElementById('userPanelProfile').addEventListener('click', function(e) {
      e.preventDefault();
      abrirModalPerfil();
    });

    function abrirModalPerfil() {
      fetch('<?php echo $URL; ?>/app/controllers/usuarios/get_perfil.php')
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            document.getElementById('nombres').value = data.nombres;
            document.getElementById('email').value   = data.email;
            document.getElementById('rolDisplay').textContent = data.rol || 'Sin rol asignado';
            document.getElementById('password_actual').value      = '';
            document.getElementById('password_nueva').value       = '';
            document.getElementById('password_confirmacion').value = '';
            if (data.imagen) {
              const urlImagen = '<?php echo $URL; ?>/' + data.imagen.replace(/^\/+/, '');
              document.getElementById('previewProfileImage').src = urlImagen;
            }
          }
          $('#editProfileModal').modal('show');
        })
        .catch(error => {
          console.error('Error:', error);
          Swal.fire({ icon: 'error', title: 'Error', text: 'Error al cargar los datos del perfil' });
        });
    }

    // Preview de imagen
    document.getElementById('profileImage').addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file) {
        if (!file.type.startsWith('image/')) {
          Swal.fire({ icon: 'error', title: 'Error', text: 'Por favor selecciona una imagen válida' });
          this.value = '';
          return;
        }
        if (file.size > 5 * 1024 * 1024) {
          Swal.fire({ icon: 'error', title: 'Error', text: 'La imagen no debe superar 5MB' });
          this.value = '';
          return;
        }
        const reader = new FileReader();
        reader.onload = e => {
          document.getElementById('previewProfileImage').src = e.target.result;
        };
        reader.readAsDataURL(file);
      }
    });

    // Envío del formulario de perfil
    document.getElementById('editProfileForm').addEventListener('submit', function(e) {
      e.preventDefault();

      const password_nueva          = document.getElementById('password_nueva').value;
      const password_confirmacion   = document.getElementById('password_confirmacion').value;

      if (password_nueva && password_nueva !== password_confirmacion) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Las contraseñas no coinciden' });
        return;
      }
      if (password_nueva && !document.getElementById('password_actual').value) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Debes ingresar tu contraseña actual para cambiarla' });
        return;
      }

      fetch('<?php echo $URL; ?>/app/controllers/usuarios/editar_perfil.php', {
        method: 'POST',
        body: new FormData(this)
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          document.getElementById('userProfileName').textContent = data.nombres;
          if (data.imagen) {
            document.getElementById('userProfileImage').src = '<?php echo $URL; ?>/' + data.imagen.replace(/^\/+/, '') + '?t=' + new Date().getTime();
          }
          $('#editProfileModal').modal('hide');
          Swal.fire({ icon: 'success', title: '¡Éxito!', text: data.mensaje }).then(() => location.reload());
        } else {
          Swal.fire({ icon: 'error', title: 'Error', text: data.mensaje });
        }
      })
      .catch(error => {
        console.error('Error:', error);
        Swal.fire({ icon: 'error', title: 'Error', text: 'Error al procesar la solicitud' });
      });
    });

    // ✅ Confirmar cierre de sesión
    function confirmarLogout(){
      Swal.fire({
        icon: 'question',
        title: '¿Cerrar sesión?',
        text: '¿Seguro que quieres salir de la sesión?',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, salir',
        cancelButtonText: 'Cancelar'
      }).then((result) => {
        if(result.isConfirmed){
          window.location = '<?php echo $URL;?>/app/controllers/login/cerrar_sesion.php';
        }
      });
    }
  </script>