<?php

include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');
include('../app/controllers/roles/list_rules.php');
include('../layout/mensajes.php');


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

      if (in_array(3, $_SESSION['permisos'])):
?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Rules List</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?php echo $URL;?>">Home</a></li>
              <li class="breadcrumb-item active">Starter Page</li>
              <li class="breadcrumb-item active">Rules List</li>
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
                <h3 class="card-title">Rules</h3>

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
                        <th><center>Name Rule</center></th>
                        <th><center>Acciones</center></th>
                    </tr>
                  </thead>
                  <tbody>
                        
                        <?php
                        $contador = 0;
                        foreach ($datos_roles as $dato) {
                          $id_rol = $dato['id_rol'];
                        ?>
                        <tr>
                            <td><center><?php echo $contador= $contador +1 ; ?></center></td>
                            <td><center><?php echo $dato['rol']; ?></center></td>
                            <td>
                              <center>
                                <div >
                                <a href="update.php?id=<?php echo $id_rol;?>" type="button" class="btn btn-success btn-sm"><i class="fa fa-pencil-alt"></i> Edit</a>
                                <button class="btn btn-danger btn-sm delete-rol" data-id="<?= $dato['id_rol'] ?>"><i class="fa fa-trash"></i> Eliminar</button>
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
                        <th><center>Name Rule</center></th>
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

<script>
$(document).on('click', '.delete-rol', function () {
    let idRol = $(this).data('id');

    Swal.fire({
        title: '¿Eliminar rol?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {

            $.post('../app/controllers/roles/delete.php', {
                id_rol: idRol
            }, function (resp) {

                Swal.fire({
                    icon: resp.icon,
                    title: resp.message,
                    showConfirmButton: false,
                    timer: 2000
                });

                if (resp.success) {
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                }

            }, 'json');

        }
    });
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