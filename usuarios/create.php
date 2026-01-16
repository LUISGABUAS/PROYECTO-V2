<?php

include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');

include('../app/controllers/roles/list_rules.php');

?>
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">User List</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?php echo $URL;?>">Home</a></li>
              <li class="breadcrumb-item active">Starter Page</li>
              <li class="breadcrumb-item active">Create User</li>
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
                <h3 class="card-title">Create User</h3>

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
                        <form action="../app/controllers/usuarios/create.php" method="post">
                            <div class="form-group">
                                <label for="">Nombres</label>
                                <input type="text" name="nombres" class="form-control" placeholder="Write a Username..." required>
                            </div>
                            <div class="form-group">
                                <label for="">Email</label>
                                <input type="email" name="email" class="form-control" placeholder="Write a Email..." required>
                            </div>
                            <div class="form-group">
                                <label for="">Rule</label>
                                <select name="rol" id="" class="form-control">
                                  <?php 
                                  foreach ($datos_roles as $dato_rol) {?>
                                    <option value="<?php echo $dato_rol['id_rol'] ; ?>"><?php echo $dato_rol['rol'] ; ?></option>
                                    <?php
                                  }
                                  ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="">Password</label>
                                <input type="text" name="password_user" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="">Repeat Password</label>
                                <input type="text" name="password_repeat" class="form-control" required>
                            </div>
                            <hr>
                            <div class="form-group">
                                <a href="index.php"class="btn btn-danger" >Cancel</a>
                                <button type="submit" class="btn btn-primary">Create User</button>
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
<?php include('../layout/parte2.php'); ?>