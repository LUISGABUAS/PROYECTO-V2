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
  <link rel="stylesheet" href="<?php echo $URL?>/https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
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
        <a href="https://wa.me/5218119058201?text=Hola,%20necesito%20ayuda%20con%20el%20sistema%20de%20ventas%20de%20Pacas%20Yadira" class="nav-link">Contact</a>
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
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img src="<?php echo $URL?>/public/templates/AdminLTE-3.2.0/dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image">
        </div>
        <div class="info">
          <a href="#" class="d-block"><?php echo $sesion_nombres;?></a>
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
                                    <p>Admin</p>
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

