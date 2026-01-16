<?php

include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');
include('../app/controllers/permisos/permisos.php');

if (isset($_SESSION['mensaje'])):
?>
<script>
Swal.fire({
    icon: 'success',
    title: 'Atención',
    text: <?= json_encode($_SESSION['mensaje']) ?>,
    confirmButtonText: 'Aceptar'
});
</script>
<?php
unset($_SESSION['mensaje']);
endif;
?>



  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Create Rule</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?php echo $URL;?>">Home</a></li>
              <li class="breadcrumb-item active">Starter Page</li>
              <li class="breadcrumb-item active">Create Rule</li>
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <div class="content">
      <div class="container-fluid">
        
      <div class="row">
        <div class="col-md-6">
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">Create Rule</h3>

                <div class="card-tools">
                  <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i>
                  </button>
                </div>
                <!-- /.card-tools -->
              </div>
              <!-- /.card-header -->
              <div class="card-body" style="display: block;">
                
                <div class="row">
                    <div class="col-md-12">
                        <form action="../app/controllers/roles/create.php" method="post">
                                <div class="form-group">
                                    <label for="">Name Rule</label>
                                    <input type="text" name="rol" class="form-control" placeholder="Write a New Rule..." required>
                                </div>

                                <hr>

                                <!-- Aquí van los checkboxes de permisos -->
                                <div class="form-group">
                                    <label>Assign Permissions</label>
                                    <div>
                                        <?php foreach($permisos as $seccion => $permisos_seccion): ?>
                                          <div class="card card-outline card-secondary mb-3">
                                              <div class="card-header">
                                                  <h5><?= $seccion ?></h5>
                                              </div>
                                              <div class="card-body">
                                                  <?php foreach($permisos_seccion as $p): ?>
                                                      <div class="form-check">
                                                          <input class="form-check-input" type="checkbox" name="permisos[]" value="<?= $p['id_permiso'] ?>" id="permiso<?= $p['id_permiso'] ?>">
                                                          <label class="form-check-label" for="permiso<?= $p['id_permiso'] ?>">
                                                              <?= $p['nombre'] ?>
                                                          </label>
                                                      </div>
                                                  <?php endforeach; ?>
                                              </div>
                                          </div>
                                      <?php endforeach; ?>

                                    </div>
                                </div>

                                <hr>

                                <div class="form-group">
                                    <a href="index.php" class="btn btn-danger">Cancel</a>
                                    <button type="submit" class="btn btn-primary">Create</button>
                                </div>
                            </form>

                    </div>
                </div>

              </div>
              <!-- /.card-body -->
            </div>
        </div>
      </div>


      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
<?php include('../layout/mensajes.php') ?>
<?php include('../layout/parte2.php'); ?>