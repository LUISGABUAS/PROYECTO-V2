<style>
.stock-selected {
    background-color: #fff3cd !important; /* amarillo suave */
}
</style>

<?php
include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');

/* =========================
   RECIBIR ID DEL PRODUCTO
========================= */
$id_producto = $_GET['id'] ?? null;
if (!$id_producto) {
    echo "<h3 style='color:red'>Producto no válido</h3>";
    exit;
}

/* =========================
   CONTROLLER STOCK
========================= */
include('../app/controllers/stock/list_stock.php');

if (isset($_SESSION['mensaje'])) {
    $respuesta = $_SESSION['mensaje']; ?>
    <script>
    Swal.fire({
        position: 'top-end',
        icon: 'success',
        title: '<?php echo $respuesta ?>',
        showConfirmButton: false,
        timer: 2000
    });
    </script>
<?php
    unset($_SESSION['mensaje']);
}

if (!in_array(11, $_SESSION['permisos'])):
    include('../layout/parte2.php');
    echo '<script>
      Swal.fire({
        icon: "error",
        title: "Access Denied",
        text: "No tienes permiso para acceder a esta página.",
        showConfirmButton: false,
        timer: 3000
      }).then(() => { window.location = "'.$URL.'"; });
    </script>';
    exit;
endif;
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">Stock</h1>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">

            <!-- FILTRO -->
            <form method="get" class="row mb-3">
                <input type="hidden" name="id" value="<?= $id_producto ?>">
                <div class="col-md-3">
                    <input type="date" name="desde" class="form-control" value="<?= $_GET['desde'] ?? '' ?>">
                </div>
                <div class="col-md-3">
                    <input type="date" name="hasta" class="form-control" value="<?= $_GET['hasta'] ?? '' ?>">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary">Filtrar</button>
                </div>
            </form>

            <!-- TABLA STOCK -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-md-6">
                                    <button id="select-not-scanned" class="btn btn-warning btn-sm">
                                        <i class="fa fa-check-square"></i> Seleccionar no escaneados
                                    </button>
                                    <button id="print-pdf" class="btn btn-primary btn-sm ml-2">
                                        <i class="fa fa-print"></i> PDF Códigos Seleccionados
                                    </button>
                                    <button id="print-zebra" class="btn btn-dark btn-sm ml-2">
                                        <i class="fa fa-barcode"></i> Imprimir Zebra
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <h3 class="card-title">
                                        Total en bodega: <?= count(array_filter($datos_stock, fn($s)=>$s['estado']=='EN BODEGA')) ?><br>
                                        Total sin escanear: <?= count(array_filter($datos_stock, fn($s)=>$s['estado']=='SIN ESCANEAR')) ?>
                                    </h3>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="table table-responsive">
                                <table id="example1" class="table table-bordered table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Seleccionar</th>
                                            <th>Codigo</th>
                                            <th>Estado</th>
                                            <th>Categoria</th>
                                            <th>Producto</th>
                                            <th>Fecha Ingreso</th>
                                            <th>Fecha Venta</th>
                                            <th>Creado Por</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $contador = 0;
                                        foreach ($datos_stock as $dato):
                                            $contador++;
                                            $estado = $dato['estado'];
                                            $color = $estado === 'EN BODEGA' ? 'success' : ($estado === 'SIN ESCANEAR' ? 'warning' : ($estado === 'VENDIDO' ? 'danger' : 'secondary'));
                                        ?>
                                        <tr class="stock-row">
                                            <td><?= $contador ?></td>
                                            <td>
                                                <input type="checkbox" class="select-stock" value="<?= $dato['id_stock'] ?>" data-estado="<?= $dato['estado'] ?>">
                                            </td>
                                            <td><?= $dato['codigo_unico'] ?></td>
                                            <td><span class="badge badge-<?= $color ?>"><?= $estado ?></span></td>
                                            <td><?= $dato['nombre_categoria'] ?></td>
                                            <td><?= $dato['nombre_producto'] ?></td>
                                            <td><?= $dato['fecha_ingreso'] ?></td>
                                            <td>
                                                <?php if (!empty($dato['fecha_salida']) && $dato['fecha_salida'] !== '0000-00-00'): ?>
                                                    <span class="badge badge-success"><?= $dato['fecha_salida'] ?></span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">Sin vender</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $dato['creado_por'] ?></td>
                                            <td>
                                                <?php if (in_array(13, $_SESSION['permisos'])): ?>
                                                    <button class="btn btn-danger btn-sm delete-stock" data-id="<?= $dato['id_stock'] ?>" data-estado="<?= $estado; ?>"><i class="fa fa-trash"></i> Eliminar</button>
                                                <?php endif ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div><!-- /.container-fluid -->
    </div><!-- /.content -->
</div><!-- /.content-wrapper -->

<?php include('../layout/mensajes.php')?>
<?php include('../layout/parte2.php'); ?>

<!-- SCRIPTS -->
<script>
$(function () {
    $("#example1").DataTable({
        "responsive": true, "lengthChange": false, "autoWidth": false,
        "buttons": ["copy", "excel", "pdf", "print", "colvis"]
    }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
});

// RESALTAR FILAS
$(document).on('change', '.select-stock', function () {
    $(this).closest('tr').toggleClass('stock-selected', $(this).is(':checked'));
});

// SELECCIONAR NO ESCANEADOS
$('#select-not-scanned').click(function(){
    let count = 0;

    $('.select-stock').each(function(){
        let checkbox = $(this);
        let row = checkbox.closest('tr');

        if(checkbox.data('estado') === 'SIN ESCANEAR'){
            checkbox.prop('checked', true);
            row.addClass('stock-selected');
            count++;
        } else {
            checkbox.prop('checked', false);
            row.removeClass('stock-selected');
        }
    });

    Swal.fire('Listo', count + ' productos sin escanear seleccionados', 'success');
});


// ELIMINAR STOCK

$(document).on('click', '.delete-stock', function () {
    let id_stock = $(this).data('id');
    let estado  = $(this).data('estado');

    if(estado === 'VENDIDO'){
        Swal.fire({ 
            icon: 'error',
            title: 'No se puede eliminar',
            text: 'El stock vendido no puede ser eliminado'
        });
        return;
    }

    Swal.fire({
        title: '¿Eliminar stock?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {

            $.post(
                '../app/controllers/stock/delete_stock.php',
                { id_stock: id_stock },
                function (response) {
                    if (response.success) {
                        Swal.fire('Eliminado', response.message, 'success')
                        .then(() => location.reload());
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                'json'
            );

        }
    });
});


// IMPRIMIR ZEBRA
$('#print-zebra').click(function(){
    let selected = [];
    $('.select-stock:checked').each(function(){
        selected.push($(this).val());
    });

    if(selected.length === 0){
        Swal.fire('Atención','Debes seleccionar al menos un código','warning');
        return;
    }

    Swal.fire({
        title: 'Imprimir en Zebra',
        text: 'Se enviarán ' + selected.length + ' etiquetas a la impresora',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Imprimir',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if(result.isConfirmed){
            let url = <?php echo json_encode($URL . '/app/controllers/helpers/print_zebra_direct_prueba.php'); ?> + '?ids=' + selected.join(',');
            
            fetch(url)
            .then(res => res.json()) // Debe ser JSON
            .then(data => {
                if(data.status === 'success'){
                    Swal.fire('Listo', data.message, 'success');
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error', 'No se pudo imprimir: ' + err, 'error');
            });
        }
    });
});

</script>

<!--PDF SELECCIONADOS-->
<script>
  $('#print-pdf').click(function(){
    let selected = [];
    $('.select-stock:checked').each(function(){
        selected.push($(this).val());
    });

    if(selected.length === 0){
        Swal.fire('Atención','Debes seleccionar al menos un código','warning');
        return;
    }

    let url = <?= json_encode($URL . '/app/controllers/helpers/print_zebra_seleccion.php') ?> 
              + '?ids=' + selected.join(',');

    window.open(url, '_blank');
});
</script>

