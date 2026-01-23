<?php
include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');

/* =========================
   PERMISO DE ACCESO
========================= */
if (!in_array(20, $_SESSION['permisos'])) {
    include('../layout/parte2.php');
    echo "<script>Swal.fire('Acceso denegado','','error')</script>";
    exit;
}

/* =========================
   CONTROLLER
========================= */
include('../app/controllers/dashboard/vendidos_list.php');

/* =========================
   MENSAJES
========================= */
if (isset($_SESSION['mensaje'])) {
    $respuesta = htmlspecialchars($_SESSION['mensaje'], ENT_QUOTES, 'UTF-8');
?>
    <script>
        Swal.fire({
            position: 'top-end',
            icon: 'success',
            title: '<?= $respuesta ?>',
            showConfirmButton: false,
            timer: 2000
        });
    </script>
<?php 
    unset($_SESSION['mensaje']);
}
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">Reporte de Ventas Enviadas</h1>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">

            <!-- FILTRO FECHAS -->
            <div class="card card-outline card-secondary mb-3">
                <div class="card-body">
                    <form method="get" class="row align-items-end">
                        <div class="col-md-3">
                            <label for="desde">Fecha Desde:</label>
                            <input type="date" 
                                   id="desde"
                                   name="desde" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($desde, ENT_QUOTES, 'UTF-8') ?>"
                                   required>
                        </div>
                        <div class="col-md-3">
                            <label for="hasta">Fecha Hasta:</label>
                            <input type="date" 
                                   id="hasta"
                                   name="hasta" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($hasta, ENT_QUOTES, 'UTF-8') ?>"
                                   required>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Filtrar
                            </button>
                            <a href="vendidos.php" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Limpiar
                            </a>
                        </div>
                        <div class="col-md-3 text-right">
                            <strong>Total de ventas: <?= count($vendidos) ?></strong>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (in_array(24, $_SESSION['permisos'])): ?>

            <!-- TABLA VENTAS -->
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-truck"></i> Ventas Enviadas
                    </h3>
                </div>

                <div class="card-body">
                    <?php if (empty($vendidos)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            No hay ventas enviadas en el rango de fechas seleccionado.
                        </div>
                    <?php else: ?>
                        <table id="ventas" class="table table-bordered table-striped table-sm">
                            <thead>
                                <tr>    
                                    <th>ID</th>
                                    <th>Fecha</th>
                                    <th>Cliente</th>
                                    <th>Vendedor</th>
                                    <th>Total</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totalGeneral = 0;
                                foreach ($vendidos as $venta): 
                                    $totalGeneral += $venta['total'];
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($venta['id'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($venta['fecha'])) ?></td>
                                        <td><?= htmlspecialchars($venta['cliente_nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($venta['vendedor_nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-right">$<?= number_format($venta['total'], 2, '.', ',') ?></td>
                                        <td class="text-center">
                                            <a href="detalle_venta.php?id=<?= urlencode($venta['id']) ?>" 
                                               class="btn btn-info btn-sm" 
                                               title="Ver Detalle">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="font-weight-bold">
                                    <td colspan="4" class="text-right">TOTAL GENERAL:</td>
                                    <td class="text-right">$<?= number_format($totalGeneral, 2, '.', ',') ?></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    No tienes permisos para ver el reporte de ventas.
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php
include('../layout/mensajes.php');
include('../layout/parte2.php');
?>

<!-- DATATABLES -->
<script>
$(function () {
    const table = $("#ventas").DataTable({
        "responsive": true,
        "lengthChange": true,
        "autoWidth": false,
        "pageLength": 25,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json"
        },
        "order": [[1, "desc"]], // Ordenar por fecha descendente
        "columnDefs": [
            { "orderable": false, "targets": 5 } // Deshabilitar orden en columna acciones
        ],
        "buttons": [
            {
                extend: 'collection',
                text: '<i class="fas fa-download"></i> Exportar',
                className: 'btn btn-success btn-sm',
                buttons: [
                    {
                        extend: 'copy',
                        text: '<i class="fas fa-copy"></i> Copiar',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4]
                        }
                    },
                    {
                        extend: 'excel',
                        text: '<i class="fas fa-file-excel"></i> Excel',
                        title: 'Ventas Enviadas',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4]
                        }
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="fas fa-file-pdf"></i> PDF',
                        title: 'Ventas Enviadas',
                        orientation: 'landscape',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4]
                        }
                    },
                    {
                        extend: 'print',
                        text: '<i class="fas fa-print"></i> Imprimir',
                        title: 'Ventas Enviadas',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4]
                        }
                    }
                ]
            },
            {
                extend: 'colvis',
                text: '<i class="fas fa-columns"></i> Columnas',
                className: 'btn btn-secondary btn-sm'
            }
        ],
        "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
               '<"row"<"col-sm-12 col-md-6"B>>' +
               '<"row"<"col-sm-12"tr>>' +
               '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
    });
});
</script>

<style>
/* Estilos mejorados para los botones de DataTables */
.dt-buttons {
    margin-bottom: 15px;
}

.buttons-collection {
    margin-right: 5px;
}

/* Estilo para el total */
tfoot tr {
    background-color: #f8f9fa;
    font-size: 1.1em;
}
</style>