<?php
include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');

if(in_array(14, $_SESSION['permisos'])):
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <h1 class="m-0">Ingreso de Stock</h1>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <div class="row justify-content-center">
        <div class="col-md-6">

          <div class="card card-success">

            <div class="card-header">
              <h3 class="card-title">
                <i class="fa fa-barcode"></i> Escanear producto
              </h3>
            </div>

            <div class="card-body text-center">

              <!-- INFO -->
              <div class="alert alert-info p-2">
                <i class="fa fa-info-circle"></i>
                Escanea el código de barras para ingresar el producto a bodega.
              </div>

              <!-- FORM SCAN -->
              <form action="../app/controllers/stock/scan.php" method="post" autocomplete="off">
                <div class="form-group">
                  <input 
                    type="text" 
                    name="codigo_unico"
                    class="form-control form-control-lg text-center"
                    placeholder="Escanea aquí..."
                    autofocus
                  >
                </div>
              </form>

              <!-- MENSAJE -->
              <?php if (isset($_SESSION['mensaje'])) { ?>
                <div class="alert alert-<?= 
                  $_SESSION['icono'] === 'success' ? 'success' :
                  ($_SESSION['icono'] === 'warning' ? 'warning' : 'danger')
                ?> mt-3 text-center">
                  <?= $_SESSION['mensaje']; ?>
                </div>
              <?php } else { ?>
                <div class="mt-3 text-muted">
                  Esperando escaneo...
                </div>
              <?php } ?>

              <!-- 🔊 SONIDOS -->
              <?php if (isset($_SESSION['icono'])) { ?>
                <audio id="sound-ok" src="<?= $URL ?>/app/controllers/sounds/ok.mp3"></audio>
                <audio id="sound-error" src="<?= $URL ?>/app/controllers/sounds/error.mp3"></audio>

                <script>
                  window.onload = function () {
                    <?php if ($_SESSION['icono'] === 'success') { ?>
                      document.getElementById('sound-ok').play();
                    <?php } else { ?>
                      document.getElementById('sound-error').play();
                    <?php } ?>
                  }
                </script>
              <?php 
                // 🔥 LIMPIAMOS DESPUÉS DE USAR
                unset($_SESSION['mensaje'], $_SESSION['icono']);
              } ?>

            </div>

            <div class="card-footer text-center">
              <a href="<?= $URL ?>/index.php" class="btn btn-outline-secondary btn-sm">
                <i class="fa fa-arrow-left"></i> Volver
              </a>
            </div>

          </div>

        </div>
      </div>

    </div>
  </div>
</div>

<script>
  setTimeout(() => {
    document.querySelector('input[name="codigo_unico"]').focus();
  }, 300);
</script>

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
