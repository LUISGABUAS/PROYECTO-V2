<?php

include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');
include('../app/controllers/categorias/list_categorias.php');


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

     
      if (in_array(5, $_SESSION['permisos'])):
?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Categories List 
               <?php if(in_array(6, $_SESSION['permisos'])): ?>
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modal-create-category">
                 <i class="fa fa-plus"></i> New Category
                </button>
                <?php endif; ?>
            </h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?php echo $URL;?>">Home</a></li>
              <li class="breadcrumb-item active">Starter Page</li>
              <li class="breadcrumb-item active">Categories List</li>
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
                <h3 class="card-title">Categories Registred</h3>

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
                        <th><center>Name Categories</center></th>
                        <th><center>Actions</center></th>
                    </tr>
                  </thead>
                  <tbody>
                        
                        <?php
                        $contador = 0;
                        foreach ($datos_categorias as $dato) {
                          $id_categoria = $dato['id_categoria'];
                          $nombre_categoria = $dato['nombre_categoria'];
                        
                        ?>
                        <tr>
                            <td><center><?php echo $contador= $contador +1 ; ?></center></td>
                            <td><center><?php echo $dato['nombre_categoria']; ?></center></td>
                            <td>
                              <center>
                                 <?php if(in_array(7, $_SESSION['permisos'])): ?>
                                <div class="btn-group">
                                  <button type="button" class="btn btn-success" data-toggle="modal" 
                                  data-target="#modal-update-category<?php echo $id_categoria?>">
                                   <i class="fa fa-pencil-alt"></i> Edit
                                    </button>
                                </div>
                                <?php endif; ?>
                                  <!-- Modal Update Categories-->
                                  <div class="modal fade" id="modal-update-category<?php echo $id_categoria?>">
                                          <div class="modal-dialog">
                                            <div class="modal-content">
                                              <div class="modal-header" style="background-color: #03d141ff; color: white;">
                                                <h4 class="modal-title">Update Categorie</h4>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                  <span aria-hidden="true">&times;</span>
                                                </button>
                                              </div>
                                              <div class="modal-body">

                                                  <div class="row">
                                                      <div class="col-md-12">
                                                          <div class="form-group">
                                                              <label for="">Category Name</label>
                                                              <input type="text" id="nombre_categoria<?php echo $id_categoria;?>" value="<?php echo $nombre_categoria;?>" class="form-control">
                                                              <small style="color: red; display: none; " id="lbl_update<?php echo $id_categoria?>">* Este campo es requerido</small>
                                                            </div>
                                                      </div>
                                                  </div>
                                              </div>
                                              <div class="modal-footer justify-content-between">
                                                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                                <button type="button" class="btn btn-success" id="btn_update<?php echo $id_categoria;?>">Save changes</button>
                                              </div>
                                              
                                            </div>
                                            <!-- /.modal-content -->
                                          </div>
                                          <!-- /.modal-dialog -->
                                        </div>
                                        <!-- /.modal -->
                                        <script>
                                          $('#btn_update<?php echo $id_categoria;?>').click(function(){
                                              var categoryName = $('#nombre_categoria<?php echo $id_categoria;?>').val();
                                              var id_categoria = '<?php echo $id_categoria;?>'

                                              if(categoryName.length == ""){
                                                $('#nombre_categoria<?php echo $id_categoria?>').focus();
                                                $('#lbl_update<?php echo $id_categoria?>').css('display', 'block');
                                              }else{
                                                
                                            var URL = "../app/controllers/categorias/update_categorias.php";
                                            $.get(URL,{nombre_categoria: categoryName, id_categoria:id_categoria},function(data){
                                                $('#respuesta_update<?php echo $id_categoria?>').html(data);
                                            });
                                          }
                                              
                                          });
                                        </script>

                                          <div id="respuesta_update<?php echo $id_categoria;?>"></div>
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
                        <th><center>Actions</center></th>
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

<!-- Modal Create Categories-->
 <div class="modal fade" id="modal-create-category">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header" style="background-color: #1149e2ff; color: white;">
              <h4 class="modal-title">Create Categorie</h4>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="">Category Name</label>
                            <input type="text" id="nombre_categoria"  class="form-control">
                            <small style="color: red; display: none; " id="lbl_create">* Este campo es requerido</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
              <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
              <button type="button" class="btn btn-primary" id="btn_create">Save changes</button>
            </div>
            
          </div>
          <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
      </div>
      <!-- /.modal -->

<script>
    $('#btn_create').click(function(){
        var categoryName = $('#nombre_categoria').val();

        if(categoryName.length == ""){
            $('#nombre_create').focus();
            $('#lbl_create').css('display', 'block');
          }else{
            
        var URL = "../app/controllers/categorias/registro_categorias.php";
        $.get(URL,{nombre_categoria: categoryName},function(data){
            $('#respuesta').html(data);
        });
      }
    });
</script>

<div id="respuesta"></div>

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