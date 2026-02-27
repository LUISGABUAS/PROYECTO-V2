<!DOCTYPE html>
<!--
This is a starter template page. Use this page to start your new project from
scratch. This page gets rid of all links and provides the needed markup only.
-->
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sales System</title>
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
</head>
<body class="hold-transition sidebar-mini layout-fixed">


<div class="wrapper">

  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="https://wa.me/5218119058201?text=Hola,%20necesito%20ayuda%20con%20el%20sistema%20de%20inventario%20de%20Pacas%20Yadira" class="nav-link">Contact</a>
      </li>
    </ul>
  </nav>
  <!-- /.navbar -->

  <!-- Main Sidebar Container -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="<?php echo $URL?>/index.php" class="brand-link">
      <img src="<?php echo $URL?>/pacasyadira.png" alt="AdminLTE Logo" class="brand-image img-circle f elevation-3" style="opacity: .8">
      <span class="brand-text font-weight-light">Pacas Yadira</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar user panel (optional) -->
      <!-- Sidebar user panel (optional) -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex" id="userPanelProfile" style="cursor: pointer;" title="Click para editar tu perfil">
          <div class="image">
            <img src="<?php 
              if (!empty($sesion_foto)) {
                echo $URL . '/' . $sesion_foto;
              } else {
                echo $URL . '/public/templates/AdminLTE-3.2.0/dist/img/user2-160x160.jpg';
              }
            ?>" id="userProfileImage" class="img-circle elevation-2" alt="User Image" style="width: 40px; height: 40px; object-fit: cover;">
          </div>
          <div class="info">
            <a href="#" class="d-block" id="userProfileName"><?php echo $sesion_nombres;?></a>
          </div>
        </div>

      <!-- SidebarSearch Form -->
      <div class="form-inline">
        <div class="input-group" data-widget="sidebar-search">
          <input class="form-control form-control-sidebar" type="search" placeholder="Search" aria-label="Search">
          <div class="input-group-append">
            <button class="btn btn-sidebar">
              <i class="fas fa-search fa-fw"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Sidebar Menu -->
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          <!-- Add icons to the links using the .nav-icon class
               with font-awesome or any other icon font library -->

               <?php if(in_array(24, $_SESSION['permisos']) || in_array(2, $_SESSION['permisos'])): ?>
                    <li class="nav-item">
                        <a href="#" class="nav-link active" stylebackground-color: rgba(16, 32, 177, 1);>
                            <i class="nav-icon fas fa-chart-line"></i>
                            <p>
                                Dashboards
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        
                        <ul class="nav nav-treeview">
                      
                        <?php if(in_array(24, $_SESSION['permisos'])): ?>
                            <li class="nav-item">
                                <a href="<?php echo $URL;?>/dashboard/admin.php" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Administrador</p>
                                </a>
                            </li>
                            <?php endif; ?>

                            <?php if(in_array(24, $_SESSION['permisos'])): ?>
                            <li class="nav-item">
                                <a href="<?php echo $URL;?>/dashboard/foraneos.php" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Ventas Foraneos</p>
                                </a>
                            </li>
                            <?php endif; ?>

                            <?php if(in_array(24, $_SESSION['permisos'])): ?>
                            <li class="nav-item">
                                <a href="<?php echo $URL;?>/dashboard/locales.php" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Ventas Locales</p>
                                </a>
                            </li>
                            <?php endif; ?>

                            <li class="nav-item">
                                <a href="<?php echo $URL;?>/dashboard/vendidos.php" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Vendidos Detalle</p>
                                </a>
                            </li>


                        </ul>
                    </li>
                    

                    <?php endif; ?>
          
          
          
            <?php if(in_array(1, $_SESSION['permisos']) || in_array(2, $_SESSION['permisos'])): ?>
                    <li class="nav-item">
                        <a href="#" class="nav-link active" stylebackground-color: rgba(16, 32, 177, 1);>
                            <i class="nav-icon fas fa-user-plus"></i>
                            <p>
                                Users
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        
                        <ul class="nav nav-treeview">
                            <?php if(in_array(1, $_SESSION['permisos'])): ?>
                            <li class="nav-item">
                                <a href="<?php echo $URL;?>/usuarios" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>List</p>
                                </a>
                            </li>
                            <?php endif; ?>

                            <?php if(in_array(2, $_SESSION['permisos'])): ?>
                            <li class="nav-item">
                                <a href="<?php echo $URL;?>/usuarios/create.php" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Create</p>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    

                    <?php endif; ?>


              
          <?php if(in_array(3, $_SESSION['permisos']) || in_array(4, $_SESSION['permisos'])):?>
          <li class="nav-item ">
            <a href="#" class="nav-link active" style-background-color: rgba(16, 32, 177, 1);>
              <i class="nav-icon fas fa-address-card"></i>
              <p>
                Rules
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <?php if(in_array(3,$_SESSION['permisos'])): ?>
              <li class="nav-item">
                <a href="<?php echo $URL;?>/roles" class="nav-link ">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Rules List</p>
                </a>
              </li>
              <?php endif;?>

              <?php if(in_array(4,$_SESSION['permisos'])): ?>
              <li class="nav-item">
                <a href="<?php echo $URL;?>/roles/create.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Create Rule</p>
                </a>
              </li>
              <?php endif;?>
            
            </ul>
          </li>
          
              
          <?php endif;?>

                <?php if(in_array(5, $_SESSION['permisos']) || in_array(6, $_SESSION['permisos']) || in_array(7, $_SESSION['permisos'])): ?>
          <li class="nav-item ">
            <a href="#" class="nav-link active" style-background-color: rgba(16, 32, 177, 1);>
              <i class="nav-icon fas fa-tags"></i>
              <p>
                Categories
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="<?php echo $URL;?>/categorias" class="nav-link ">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Categories List</p>
                </a>
              </li>
            </ul>
          </li>

          <?php endif; ?>

              
                  <?php if(in_array(8, $_SESSION['permisos']) || in_array(9, $_SESSION['permisos']) || in_array(10, $_SESSION['permisos'])): ?>
          <li class="nav-item ">
            <a href="#" class="nav-link active" style-background-color: rgba(16, 32, 177, 1);>
              <i class="nav-icon fas fa-store"></i>
              <p>
                Products
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <?php if(in_array(8, $_SESSION['permisos'])):?>
              <li class="nav-item">
                <a href="<?php echo $URL;?>/almacen" class="nav-link ">
                  <i class="far fa-circle nav-icon"></i>
                  <p>List Products</p>
                </a>
              </li>
              <?php endif; ?>
              <?php if(in_array(9, $_SESSION['permisos'])):?>
              <li class="nav-item">
                <a href="<?php echo $URL;?>/almacen/create.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Create Product</p>
                </a>
              </li>
              <?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>
          



                <?php if(in_array(12, $_SESSION['permisos']) || in_array(13, $_SESSION['permisos']) || in_array(14, $_SESSION['permisos'])): ?>
            <li class="nav-item ">
            <a href="#" class="nav-link active" style-background-color: rgba(16, 32, 177, 1);>
              <i class="nav-icon fas fa-list"></i>
              <p>
                Store
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>

            <ul class="nav nav-treeview">
              <?php if(in_array(12, $_SESSION['permisos'])):?>
              <li class="nav-item">
                <a href="<?php echo $URL;?>/stock/create.php" class="nav-link ">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Create</p>
                </a>
              </li>
              <?php endif; ?>

                <?php if(in_array(14, $_SESSION['permisos'])):?>
              <li class="nav-item">
                <a href="<?php echo $URL;?>/stock/scan.php" class="nav-link ">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Scan Nuevos</p>
                </a>
              </li>
              <?php endif; ?>

                <?php if(in_array(13, $_SESSION['permisos'])):?>

              <li class="nav-item">
                <a href="<?php echo $URL;?>/stock/salida.php" class="nav-link ">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Scan Salida</p>
                </a>
              </li>
              <?php endif; ?>

            </ul>
            <?php endif; ?>


                  <?php if(in_array(15, $_SESSION['permisos'])): ?>
            <li class="nav-item ">
            <a href="#" class="nav-link active" style-background-color: rgba(16, 32, 177, 1);>
              <i class="nav-icon fas fa-building"></i>
              <p>
                Prooveders
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="<?php echo $URL;?>/provedores" class="nav-link ">
                  <i class="far fa-circle nav-icon"></i>
                  <p>List Prooveders</p>
                </a>
              </li>
            </ul>
            <?php endif; ?>
                    
            <?php if(in_array(20, $_SESSION['permisos'])): ?>
            <li class="nav-item ">
            <a href="#" class="nav-link active" style-background-color: rgba(16, 32, 177, 1);>
              <i class="nav-icon fas fa-dollar-sign"></i>
              <p>
                Sales
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="<?php echo $URL;?>/ventas" class="nav-link ">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Report</p>
                </a>
              </li>

              <li class="nav-item">
                <a href="<?php echo $URL;?>/ventas/create.php" class="nav-link ">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Crear Venta</p>
                </a>
              </li>


              <li class="nav-item">
                <a href="<?php echo $URL;?>/ventas/cotizaciones.php" class="nav-link ">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Cotizar</p>
                </a>
              </li>


            </ul>
            <?php endif; ?>

              <?php if(in_array(23, $_SESSION['permisos'])): ?>
            <li class="nav-item ">
            <a href="#" class="nav-link active" style-background-color: rgba(16, 32, 177, 1);>
              <i class="nav-icon fas fa-users"></i>
              <p>
                Clients
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="<?php echo $URL;?>/clientes" class="nav-link ">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Report</p>
                </a>
              </li>
            </ul>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="<?php echo $URL;?>/clientes/locales.php" class="nav-link ">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Locals</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?php echo $URL;?>/clientes/foraneos.php" class="nav-link ">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Foreign</p>
                </a>
              </li>

              <li>
                <a href="<?php echo $URL;?>/clientes/create.php" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Create</p>
                </a>
              </li>
            </ul>
            <?php endif; ?>

          <li class="nav-item">
            <a href="<?php echo $URL;?>/app/controllers/login/cerrar_sesion.php" class="nav-link" style="background-color: rgba(177, 16, 16, 1);">
              <i class="nav-icon fas fa-door-closed"></i>
              <p>
                Log Out
              </p>
            </a>
          </li>
        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>

  <!-- Modal para editar perfil del usuario -->
  <div class="modal fade" id="editProfileModal" tabindex="-1" role="dialog" aria-labelledby="editProfileLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header bg-primary">
          <h5 class="modal-title" id="editProfileLabel">
            <i class="fas fa-user-circle"></i> Editar Mi Perfil
          </h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form id="editProfileForm" enctype="multipart/form-data">
          <div class="modal-body">
            <div class="row">
              <div class="col-md-4 text-center">
                <div class="form-group">
                  <img id="previewProfileImage" src="<?php echo $URL?>/public/templates/AdminLTE-3.2.0/dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image" style="width: 150px; height: 150px; object-fit: cover; border: 3px solid #007bff;">
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
                  <label for="email">Email</label>
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
                  <small class="text-muted">Déjalo en blanco si no deseas cambiar</small>
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
    // Hacer el panel de usuario clickeable
    document.getElementById('userPanelProfile').addEventListener('click', function(e) {
      e.preventDefault();
      openEditProfileModal();
    });

    // Cargar datos del usuario en el modal
    function openEditProfileModal() {
      fetch('<?php echo $URL; ?>/app/controllers/usuarios/get_perfil.php')
        .then(response => response.json())
        .then(data => {
          console.log('Datos recibidos del servidor:', data);
          if (data.success) {
            document.getElementById('nombres').value = data.nombres;
            document.getElementById('email').value = data.email;
            document.getElementById('rolDisplay').textContent = data.rol || 'Sin rol asignado';
            // Limpiar campos de contraseña
            document.getElementById('password_actual').value = '';
            document.getElementById('password_nueva').value = '';
            document.getElementById('password_confirmacion').value = '';
            if (data.imagen) {
              const urlImagen = '<?php echo $URL; ?>/' + data.imagen.replace(/^\/+/, '');
              console.log('URL de imagen construida:', urlImagen);
              document.getElementById('previewProfileImage').src = urlImagen;
            } else {
              console.log('No hay imagen guardada para este usuario');
            }
          }
          // Mostrar el modal usando Bootstrap 4
          $('#editProfileModal').modal('show');
        })
        .catch(error => {
          console.error('Error:', error);
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al cargar los datos'
          });
        });
    }

    // Manejar preview de imagen
    document.getElementById('profileImage').addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file) {
        // Validar que sea una imagen
        if (!file.type.startsWith('image/')) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Por favor selecciona una imagen válida'
          });
          this.value = '';
          return;
        }
        
        // Validar tamaño (máximo 5MB)
        if (file.size > 5 * 1024 * 1024) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'La imagen no debe superar 5MB'
          });
          this.value = '';
          return;
        }
        
        const reader = new FileReader();
        reader.onload = function(event) {
          document.getElementById('previewProfileImage').src = event.target.result;
        };
        reader.readAsDataURL(file);
      }
    });

    // Manejar envío del formulario
    document.getElementById('editProfileForm').addEventListener('submit', function(e) {
      e.preventDefault();
      
      const formData = new FormData(this);
      
      // Validar contraseñas
      const password_nueva = document.getElementById('password_nueva').value;
      const password_confirmacion = document.getElementById('password_confirmacion').value;
      
      if (password_nueva && password_nueva !== password_confirmacion) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Las contraseñas no coinciden'
        });
        return;
      }
      
      if (password_nueva && !document.getElementById('password_actual').value) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Debes ingresar tu contraseña actual para cambiarla'
        });
        return;
      }

      fetch('<?php echo $URL; ?>/app/controllers/usuarios/editar_perfil.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Actualizar el nombre en el panel de usuario
          document.getElementById('userProfileName').textContent = data.nombres;
          
          // Actualizar la imagen en el panel si cambió
          if (data.imagen) {
            document.getElementById('userProfileImage').src = '<?php echo $URL; ?>/' + data.imagen.replace(/^\/+/, '') + '?t=' + new Date().getTime();
          }
          
          // Cerrar el modal
          $('#editProfileModal').modal('hide');
          
          Swal.fire({
            icon: 'success',
            title: 'Éxito',
            text: data.mensaje
          }).then(() => {
            location.reload();
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: data.mensaje
          });
        }
      })
      .catch(error => {
        console.error('Error:', error);
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Error al procesar la solicitud'
        });
      });
    });
  </script>

