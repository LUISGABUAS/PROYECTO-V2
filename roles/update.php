<?php
include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');

/* ==========================
   Traer datos del rol y permisos
========================== */
include('../app/controllers/roles/update_roles.php');
?>

<div class="content-wrapper">

  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Update Role</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="<?= $URL ?>">Home</a></li>
            <li class="breadcrumb-item active">Roles</li>
            <li class="breadcrumb-item active">Update Role</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-8">
          <div class="card card-success">
            <div class="card-header">
              <h3 class="card-title">Edit Role</h3>
            </div>

            <div class="card-body">
              <form action="../app/controllers/roles/update.php" method="post">

                <!-- ID del rol -->
                <input type="hidden" name="id_rol" value="<?= $id_rol_get ?? '' ?>">

                <!-- Nombre del rol -->
                <div class="form-group">
                  <label>Name Role</label>
                 <input type="text"
                  name="rol"
                  class="form-control"
                  value="<?= htmlspecialchars($rol ?? '') ?>"
                  required>

                </div>

                <hr>

                <!-- Checkboxes de permisos -->
                <h5>Assign Permissions</h5>
                <div>
                  <?php foreach ($permisos as $seccion => $permisos_seccion): ?>
                    <div class="card card-outline card-secondary mb-3">
                      <div class="card-header">
                        <h5><?= $seccion ?></h5>
                      </div>
                      <div class="card-body">
                        <?php foreach ($permisos_seccion as $p): ?>
                          <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="permisos[]"
                                   value="<?= $p['id_permiso'] ?>"
                                   id="permiso<?= $p['id_permiso'] ?>"
                                   <?= in_array($p['id_permiso'], $permisos_rol) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="permiso<?= $p['id_permiso'] ?>">
                              <?= $p['nombre'] ?>
                            </label>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>

                <hr>

                <div class="form-group">
                  <a href="index.php" class="btn btn-danger">Cancel</a>
                  <button type="submit" class="btn btn-success">Update</button>
                </div>

              </form>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>

</div>

<?php include('../layout/mensajes.php') ?>
<?php include('../layout/parte2.php'); ?>
