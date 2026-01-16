<?php

include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');


include('../app/controllers/provedores/list_provedores.php');


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

      if (in_array(16, $_SESSION['permisos'])):
?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Proveeders List 
              <?php if(in_array(17, $_SESSION['permisos'])): ?>
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modal-create">
                 <i class="fa fa-plus"></i> New Prooveders
                </button>
              <?php endif; ?>
            </h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?php echo $URL;?>">Home</a></li>
              <li class="breadcrumb-item active">Starter Page</li>
              <li class="breadcrumb-item active">Prooveders List</li>
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
                <h3 class="card-title">Prooveders Registred</h3>

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
                        <th><center>Name Prooveders</center></th>
                        <th><center>Celular</center></th>
                        <th><center>Telefono</center></th>
                        <th><center>Empresa</center></th>
                        <th><center>Direccion</center></th>
                        <th><center>Email</center></th>
                        <?php if(in_array(18, $_SESSION['permisos']) || in_array(19, $_SESSION['permisos'])): ?>
                        <th><center>Actions</center></th>
                        <?php endif; ?>
                    </tr>
                  </thead>
                  <tbody>
                        
                        <?php
                        $contador = 0;
                        foreach ($proovedores_datos as $dato) {
                          $id_proveedor = $dato['id_proovedor'];
                          $nombre_proovedor = $dato['nombre_proveedor'];
                          $celular = $dato['celular'];
                          $telefono = $dato['telefono'];
                          $empresa = $dato['empresa'];
                            $direccion = $dato['direccion'];
                            $email = $dato['email'];
                        ?>
                        <tr>
                            <td><center><?php echo $contador= $contador +1 ; ?></center></td>
                            <td><center><?php echo $dato['nombre_proveedor']; ?></center></td>
                            <td><center><?php echo $dato['celular']; ?></center></td>
                            <td><center><?php echo $dato['telefono']; ?></center></td>
                            <td><center><?php echo $dato['empresa']; ?></center></td>
                            <td><center><?php echo $dato['direccion']; ?></center></td>
                            <td><center><?php echo $dato['email']; ?></center></td>
                          <?php if(in_array(18, $_SESSION['permisos']) || in_array(19, $_SESSION['permisos'])): ?>
                            <td>
                              <center>
                                <div class="btn-group">
                                  <?php if(in_array(18, $_SESSION['permisos'])): ?>
                                  <button type="button" class="btn btn-success" data-toggle="modal" 
                                  data-target="#modal-update-proveedor<?php echo $id_proveedor;?>">
                                   <i class="fa fa-pencil-alt"></i> Edit
                                    </button>
                                  <?php endif; ?>
                                  <!-- Modal Update Prooveders-->
                                  <div class="modal fade" id="modal-update-proveedor<?php echo $id_proveedor?>">
                                          <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                              <div class="modal-header" style="background-color: #03d141ff; color: white;">
                                                <h4 class="modal-title">Update Prooveder</h4>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                  <span aria-hidden="true">&times;</span>
                                                </button>
                                              </div>
                                              <div class="modal-body">

                                                   <div class="row">
                                                    <div class="col-md-6">

                                                        <div class="form-group">
                                                            <label for="">Prooveder Name <b>*</b></label>
                                                            <input type="text" id="nombre_proovedor<?php echo $id_proveedor?>"  class="form-control" value="<?php echo $nombre_proovedor?>" required>
                                                            <small style="color: red; display: none; " id="lbl_nombre<?php echo $id_proveedor;?>">* Este campo es requerido</small>
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="">Telefono <b>*</b></label>
                                                            <input type="number" id="telefono<?php echo $id_proveedor?>"  class="form-control" value="<?php echo $telefono;?>">
                                                            <small style="color: red; display: none; " id="lbl_telefono<?php echo $id_proveedor;?>">* Este campo es requerido</small>
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="">Email <b>*</b></label>
                                                            <input type="email" id="email<?php echo $id_proveedor?>"  class="form-control" value="<?php echo $email;?>">
                                                            <small style="color: red; display: none; " id="lbl_email<?php echo $id_proveedor;?>">* Este campo es requerido</small>
                                                        </div>

                                                      

                                                    </div>

                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="">Celular <b>*</b></label>
                                                            <input type="number" id="celular<?php echo $id_proveedor?>"  class="form-control" value="<?php echo $celular;?>">
                                                            <small style="color: red; display: none; " id="lbl_celular<?php echo $id_proveedor;?>">* Este campo es requerido</small>
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="">Empresa <b>*</b></label>
                                                            <input type="text" id="empresa<?php echo $id_proveedor?>"  class="form-control" value="<?php echo $empresa;?>">
                                                            <small style="color: red; display: none; " id="lbl_empresa<?php echo $id_proveedor;?>">* Este campo es requerido</small>
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="">Direccion <b>*</b></label>
                                                          <textarea name="" id="direccion<?php echo $id_proveedor?>" cols="30" rows="3" class="form-control"><?php echo $direccion?></textarea>
                                                            <small style="color: red; display: none; " id="lbl_direccion<?php echo $id_proveedor;?>">* Este campo es requerido</small>
                                                        </div>


                                                    </div>
                                                </div>

                                              </div>
                                              <div class="modal-footer justify-content-between">
                                                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                                <button type="button" class="btn btn-success" id="btn_update<?php echo $id_proveedor;?>">Save changes</button>
                                              </div>
                                              
                                            </div>
                                            <!-- /.modal-content -->
                                          </div>
                                          <!-- /.modal-dialog -->
                                        </div>
                                        <!-- /.modal -->


                                        <script>
                                          $('#btn_update<?php echo $id_proveedor;?>').click(function(){


                                              var categoryName = $('#nombre_proovedor<?php echo $id_proveedor;?>').val();
                                              var celular = $('#celular<?php echo $id_proveedor;?>').val();
                                              var telefono = $('#telefono<?php echo $id_proveedor;?>').val();
                                              var email = $('#email<?php echo $id_proveedor;?>').val();
                                              var empresa = $('#empresa<?php echo $id_proveedor;?>').val();
                                              var direccion = $('#direccion<?php echo $id_proveedor;?>').val();
                                              var id_proveedor = '<?php echo $id_proveedor?>';

                                              if(categoryName == ""){
                                              $('#nombre_proovedor<?php echo $id_proveedor;?>').focus();
                                                $('#lbl_nombre<?php echo $id_proveedor;?>').css('display', 'block');
                                              }else if(celular == ""){
                                                $('#celular<?php echo $id_proveedor;?>').focus();
                                                $('#lbl_celular<?php echo $id_proveedor;?>').css('display', 'block');
                                              }else if(telefono == ""){
                                                $('#telefono<?php echo $id_proveedor;?>').focus();
                                                $('#lbl_telefono<?php echo $id_proveedor;?>').css('display', 'block');
                                              }else if(email == ""){
                                                    $('#email<?php echo $id_proveedor;?>').focus();
                                                    $('#lbl_email<?php echo $id_proveedor;?>').css('display', 'block');
                                                }else if(empresa == ""){
                                                    $('#empresa<?php echo $id_proveedor;?>').focus();
                                                    $('#lbl_empresa<?php echo $id_proveedor;?>').css('display', 'block');
                                                }else if(direccion == ""){
                                                    $('#direccion<?php echo $id_proveedor;?>').focus();
                                                    $('#lbl_direccion<?php echo $id_proveedor;?>').css('display', 'block');
                                            }else{
                                            var URL = "../app/controllers/provedores/update_proveedores.php";
                                            $.get(URL,{id_proovedor: id_proveedor, nombre_proovedor: categoryName, celular: celular, telefono: telefono, email: email, empresa: empresa, direccion: direccion },function(data){
                                                $('#respuesta_update<?php echo $id_proveedor?>').html(data);
                                            });
                                          }
                                              
                                          });
                                        </script>

                                          <div id="respuesta_update<?php echo $id_proveedor;?>"></div>
                              </div>

                              <div class="btn-group">
                                <?php if(in_array(19, $_SESSION['permisos'])): ?>
                                  <button type="button" class="btn btn-danger" data-toggle="modal" 
                                  data-target="#modal-delete-proveedor<?php echo $id_proveedor;?>">
                                   <i class="fa fa-pencil-alt"></i> Delete
                                    </button>
                                  <?php endif; ?>
                                  <!-- Modal Delete Prooveders-->
                                  <div class="modal fade" id="modal-delete-proveedor<?php echo $id_proveedor?>">
                                          <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                              <div class="modal-header" style="background-color: #d10303ff; color: white;">
                                                <h4 class="modal-title">Delete Prooveder</h4>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                  <span aria-hidden="true">&times;</span>
                                                </button>
                                              </div>
                                              <div class="modal-body">

                                                   <div class="row">
                                                    <div class="col-md-6">

                                                        <div class="form-group">
                                                            <label for="">Prooveder Name <b>*</b></label>
                                                            <input type="text" id="nombre_proovedor<?php echo $id_proveedor?>"  class="form-control" value="<?php echo $nombre_proovedor?>" disabled>
                                                            <small style="color: red; display: none; " id="lbl_nombre<?php echo $id_proveedor;?>">* Este campo es requerido</small>
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="">Telefono <b>*</b></label>
                                                            <input type="number" id="telefono<?php echo $id_proveedor?>"  class="form-control" value="<?php echo $telefono;?>" disabled>
                                                            <small style="color: red; display: none; " id="lbl_telefono<?php echo $id_proveedor;?>">* Este campo es requerido</small>
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="">Email <b>*</b></label>
                                                            <input type="email" id="email<?php echo $id_proveedor?>"  class="form-control" value="<?php echo $email;?>" disabled>
                                                            <small style="color: red; display: none; " id="lbl_email<?php echo $id_proveedor;?>">* Este campo es requerido</small>
                                                        </div>

                                                      

                                                    </div>

                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="">Celular <b>*</b></label>
                                                            <input type="number" id="celular<?php echo $id_proveedor?>"  class="form-control" value="<?php echo $celular;?>" disabled>
                                                            <small style="color: red; display: none; " id="lbl_celular<?php echo $id_proveedor;?>">* Este campo es requerido</small>
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="">Empresa <b>*</b></label>
                                                            <input type="text" id="empresa<?php echo $id_proveedor?>"  class="form-control" value="<?php echo $empresa;?>" disabled>
                                                            <small style="color: red; display: none; " id="lbl_empresa<?php echo $id_proveedor;?>">* Este campo es requerido</small>
                                                        </div>

                                                        <div class="form-group">
                                                            <label for="">Direccion <b>*</b></label>
                                                          <textarea name="" id="direccion<?php echo $id_proveedor?>" cols="30" rows="3" class="form-control" disabled><?php echo $direccion?></textarea>
                                                            <small style="color: red; display: none; " id="lbl_direccion<?php echo $id_proveedor;?>">* Este campo es requerido</small>
                                                        </div>


                                                    </div>
                                                </div>

                                              </div>
                                              <div class="modal-footer justify-content-between">
                                                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                                <button type="button" class="btn btn-danger" id="btn_delete<?php echo $id_proveedor;?>">Delete</button>
                                              </div>
                                              
                                            </div>
                                            <!-- /.modal-content -->
                                          </div>
                                          <!-- /.modal-dialog -->
                                        </div>
                                        <!-- /.modal -->


                                        <script>
                                          $('#btn_delete<?php echo $id_proveedor;?>').click(function(){

                                              var id_proveedor = '<?php echo $id_proveedor?>';

                                              
                                            var URL = "../app/controllers/provedores/delete_proveedores.php";
                                            $.get(URL,{id_proovedor: id_proveedor},function(data){
                                                $('#respuesta_delete<?php echo $id_proveedor?>').html(data);
                                            });
                                          });
                                        </script>

                                          <div id="respuesta_delete<?php echo $id_proveedor;?>"></div>
                              </div>
                              </center>
                            </td>
                          <?php endif; ?>
                        </tr>
                        <?php
                        }
                        ?>
                    </tbody>
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

<!-- Modal Create Prooveders-->
 <div class="modal fade" id="modal-create">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header" style="background-color: #1149e2ff; color: white;">
              <h4 class="modal-title">Create Proveedor</h4>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">

            
                <div class="row">
                    <div class="col-md-6">

                        <div class="form-group">
                            <label for="">Prooveder Name <b>*</b></label>
                            <input type="text" id="nombre_proovedor"  class="form-control">
                            <small style="color: red; display: none; " id="lbl_nombre">* Este campo es requerido</small>
                        </div>

                        <div class="form-group">
                            <label for="">Telefono <b>*</b></label>
                            <input type="number" id="telefono"  class="form-control">
                            <small style="color: red; display: none; " id="lbl_telefono">* Este campo es requerido</small>
                        </div>

                        <div class="form-group">
                            <label for="">Email <b>*</b></label>
                            <input type="email" id="email"  class="form-control">
                            <small style="color: red; display: none; " id="lbl_email">* Este campo es requerido</small>
                        </div>

                       

                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="">Celular <b>*</b></label>
                            <input type="number" id="celular"  class="form-control">
                            <small style="color: red; display: none; " id="lbl_celular">* Este campo es requerido</small>
                        </div>

                        <div class="form-group">
                            <label for="">Empresa <b>*</b></label>
                            <input type="text" id="empresa"  class="form-control">
                            <small style="color: red; display: none; " id="lbl_empresa">* Este campo es requerido</small>
                        </div>

                         <div class="form-group">
                            <label for="">Direccion <b>*</b></label>
                           <textarea name="" id="direccion" cols="30" rows="3" class="form-control" ></textarea>
                            <small style="color: red; display: none; " id="lbl_direccion">* Este campo es requerido</small>
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
        var categoryName = $('#nombre_proovedor').val();
        var celular = $('#celular').val();
        var telefono = $('#telefono').val();
        var email = $('#email').val();
        var empresa = $('#empresa').val();
        var direccion = $('#direccion').val();

        if(categoryName == ""){
            $('#nombre_proovedor').focus();
            $('#lbl_nombre').css('display', 'block');
          }else if(celular == ""){
            $('#celular').focus();
            $('#lbl_celular').css('display', 'block');
          }else if(telefono == ""){
            $('#telefono').focus();
            $('#lbl_telefono').css('display', 'block');
          }else if(email == ""){
                $('#email').focus();
                $('#lbl_email').css('display', 'block');
            }else if(empresa == ""){
                $('#empresa').focus();
                $('#lbl_empresa').css('display', 'block');
            }else if(direccion == ""){
                $('#direccion').focus();
                $('#lbl_direccion').css('display', 'block');
        }else{
        var URL = "../app/controllers/provedores/create.php";
        $.get(URL,{nombre_proovedor: categoryName, celular: celular, telefono: telefono, email: email, empresa: empresa, direccion: direccion },function(data){
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