<?php

include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');

include('../app/controllers/usuarios/show_usuario.php');

?>
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Delete User</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?php echo $URL;?>">Home</a></li>
              <li class="breadcrumb-item active">Starter Page</li>
              <li class="breadcrumb-item active">Delete User</li>
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
            <div class="card card-danger">
              <div class="card-header">
                <h3 class="card-title">Delete User</h3>

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
                        
                            <form action="../app/controllers/usuarios/delete.php" method="post">
                                <input type="text" value="<?php echo $id_usuario_get?>" hidden name="id">
                                <div class="form-group">
                                <label for="">Nombres</label>
                                <input type="text" name="nombres" class="form-control" value="<?php echo $nombres;?>" disabled>
                            </div>
                            <div class="form-group">
                                <label for="">Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo $email;?>" disabled>
                            </div>
                            <div class="form-group">
                                <label for="">Role</label>
                                <input type="text" name="rol" class="form-control" value="<?php echo $rol;?>" disabled>
                            </div>

                            <hr>
                            <div class="form-group">
                                <a href="index.php"class="btn btn-secondary" >Back</a>  
                                <button class="btn btn-danger">Eliminate</button>
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
<?php include('../layout/mensajes.php'); ?>
<?php include('../layout/parte2.php'); ?>
