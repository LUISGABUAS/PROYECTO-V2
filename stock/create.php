<?php
include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');
include('../app/controllers/almacen/list_almacen.php');

if(in_array(12, $_SESSION['permisos'])):
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <h1 class="m-0">Ingreso de Stock</h1>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <div class="row">
        <div class="col-md-12">
          <div class="card card-primary">

            <div class="card-header">
              <h3 class="card-title">Generar códigos de stock</h3>
            </div>

            <form action="../app/controllers/stock/create_codigos.php" method="post">

              <div class="card-body">

              <!-- ESCANEO (FUTURO) -->
                <div class="row mb-3">
                  <div class="col-md-12">
                    <div class="alert alert-info p-2">
                      <i class="fa fa-info-circle"></i>
                      Puedes seleccionar el producto manualmente o escanearlo (próximamente).
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6">

                    <!-- PRODUCTO -->
                    <div class="form-group">
                      <label>Producto</label>
                      <select name="id_producto" class="form-control" required>
                        <option value="">Seleccione un producto</option>
                        <?php foreach ($datos_productos as $dato) { ?>
                          <option value="<?= $dato['id_producto'] ?>">
                            <?= $dato['codigo'] ?> - <?= $dato['nombre'] ?>
                          </option>
                        <?php } ?>
                      </select>
                    </div>

                  </div>

                  <div class="col-md-3">

                    <!-- CANTIDAD -->
                    <div class="form-group">
                      <label> <i class="fa fa-sort-numeric-up"></i> Cantidad</label>
                      <input type="number" name="cantidad" class="form-control text-center"  min="1" value="1" required>
                    </div>

                    <!-- BOTONES RAPIDOS -->
                     <div class="btn-group btn-group-sm w-100">
                       <button type="button" class="btn btn-secondary" onclick="sumar(1)">+1</button>
                      <button type="button" class="btn btn-secondary" onclick="sumar(5)">+5</button>
                      <button type="button" class="btn btn-secondary" onclick="sumar(10)">+10</button>
                     </div>

                  </div>

                  <div class="col-md-3">

                    <!-- USUARIO -->
                    <div class="form-group">
                      <label>Usuario</label>
                      <input type="text"  class="form-control" value="<?= $sesion_nombres ?>" disabled>
                      <input type="text" name="id_usuario" value="<?php echo $id_usuario_sesion?>" hidden>
                    </div>

                  </div>
                </div>

              </div>

              <div class="card-footer">
                <a href="<?= $URL ?>" class="btn btn-outline-danger"><i class="fa a-times"></i> Cancelar</a>
                <button type="submit" class="btn btn-primary">
                  <i class="fa fa-barcode"></i> Generar códigos
                </button>
              </div>

            </form>

          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
function sumar(valor) {
  const input = document.querySelector('input[name="cantidad"]');
  input.value = parseInt(input.value || 0) + valor;
}
</script>

<?php include('../layout/mensajes.php'); ?>
<?php include('../layout/parte2.php'); ?>

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
