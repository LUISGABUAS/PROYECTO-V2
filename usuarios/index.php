<?php

include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');
include('../app/controllers/usuarios/listado_de_usuarios.php');
include('../app/controllers/roles/list_rules.php');

if (isset($_SESSION['mensaje'])) {
    $respuesta = $_SESSION['mensaje'];?>
    <script>
    Swal.fire({
            position: 'top-end',
            icon: 'success',
            title: '<?php echo $respuesta ?>',
            showConfirmButton: false,
            timer: 2000
   })
   </script>
    
<?php
    unset($_SESSION['mensaje']);
     }

      if (in_array(1, $_SESSION['permisos'])):
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
              <li class="breadcrumb-item active">List User</li>
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
        <div class="col-md-12">
            <div class="card card-outline card-primary">
              <div class="card-header">
                <h3 class="card-title">Users</h3>

                <div class="card-tools">
                  <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i>
                  </button>
                </div>
                <!-- /.card-tools -->
              </div>
              <!-- /.card-header -->
              <div class="card-body" style="display: block;">
                
                <table id="example1" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                        <th><center>ID</center></th>
                        <th><center>Name</center></th>
                        <th><center>Email</center></th>
                        <th><center>Role</center></th>
                        <th><center>Acciones</center></th>
                    </tr>
                  </thead>
                  <tbody>
                        
                        <?php
                        $contador = 0;
                        foreach ($datos_usuarios as $dato) {
                          $id_usuario = $dato['id'];
                        ?>
                        <tr>
                            <td><center><?php echo $contador= $contador +1 ; ?></center></td>
                            <td><center><?php echo $dato['nombres']; ?></center></td>
                            <td><center><?php echo $dato['email']; ?></center></td>
                            <td><center><?php echo $dato['rol'] ?></center></td>
                            <td>
                              <center>
                                <div class="btn-group">
                                <a href="show.php?id=<?php echo $id_usuario;?>" type="button" class="btn btn-info"><i class="fa fa-eye"></i> Show</a>
                                <a href="update.php?id=<?php echo $id_usuario;?>" type="button" class="btn btn-success"><i class="fa fa-pencil-alt"></i> Edit</a>
                                <a href="delete.php?id=<?php echo $id_usuario;?>" type="button" class="btn btn-danger"><i class="fa fa-trash"></i> Eliminate</a>
                              </div>
                              </center>
                            </td>
                        </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                  <tfoot>
                  <tr>
                        <th><center>ID</center></th>
                        <th><center>Name</center></th>
                        <th><center>Email</center></th>
                        <th><center>Role</center></th>
                        <th><center>Acciones</center></th>
                    </tr>
                  </tfoot>
                </table>
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

<!-- Page specific script -->
<script>
  $(function () {
    $("#example1").DataTable({
      "responsive": true, "lengthChange": false, "autoWidth": false,
      "buttons": [{ 
        extend: 'collection',
        text: 'Export',
        orientation: 'landscape',
        buttons: [{
          text: 'Copy',
          extend: 'copy',
        },{
          text: 'Excel',
          extend: 'excel',
        },{
          text: 'PDF',
          extend: 'pdf',
        },{
          text: 'Print',
          extend: 'print',
        }]
      },
      {
        extend: 'colvis',
        text: 'Columns',
        collectionLayout: 'fixed three-column'
      }
      ],
    }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
  });
</script>

<?php 
  else:

    include('../layout/parte2.php'); 
    echo '<script>
      Swal.fire({
        icon: "error",
        title: "Access Denied",
        text: "You do not have permission to access this page.",
        showConfirmButton: false,
        timer: 3000
      }).then(() => {
        window.location = "'.$URL.'";
      });
    </script>';
  endif;