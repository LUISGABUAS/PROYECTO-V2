<?php

/*==========================
    DETALLE DE VENTA
==========================*/

/* =========================
   INCLUYENDO ARCHIVOS
========================= */
include('../app/config.php');
include('../layout/sesion.php');
include('../layout/parte1.php');

/* =========================
   PERMISO DE ACCESO
========================= */
if (!in_array(24, $_SESSION['permisos'])) {
    include('../layout/parte2.php');
    echo "<script>Swal.fire('Acceso denegado','','error')</script>";
    exit;
}

/* =========================
   VALIDAR ID
========================= */
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: vendidos.php');
    exit;
}

$id_venta = (int)$_GET['id'];

/* =========================
   OBTENER DATOS DE LA VENTA
========================= */
try {
    // Información general de la venta
    $sqlVenta = $pdo->prepare("SELECT 
            v.id_venta,
            v.fecha,
            v.total,
            v.estado_logistico,
            c.nombre_completo AS cliente_nombre,
            c.telefono AS cliente_telefono,
            c.direccion AS cliente_direccion,
            u.nombres AS vendedor_nombre,
            u.email AS vendedor_email
        FROM tb_ventas v
        JOIN clientes c ON v.cliente = c.id_cliente
        JOIN tb_usuario u ON v.id_usuario = u.id
        WHERE v.id_venta = :id");
    
    $sqlVenta->execute([':id' => $id_venta]);
    $venta = $sqlVenta->fetch(PDO::FETCH_ASSOC);

    if (!$venta) {
        $_SESSION['mensaje'] = 'Venta no encontrada';
        header('Location: vendidos.php');
        exit;
    }

    // Detalle de productos de la venta
    $sqlDetalle = $pdo->prepare("SELECT 
            dv.id_detalle_venta,
            dv.cantidad,
            dv.precio_unitario,
            dv.subtotal,
            p.nombre AS producto_nombre,
            p.codigo AS producto_codigo,
            p.imagen AS producto_imagen
        FROM tb_detalle_venta dv
        JOIN tb_almacen p ON dv.id_producto = p.id_producto
        WHERE dv.id_venta = :id
        ORDER BY dv.id_detalle_venta");
    
    $sqlDetalle->execute([':id' => $id_venta]);
    $detalles = $sqlDetalle->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "<script>Swal.fire('Error al cargar la venta', '" . htmlspecialchars($e->getMessage()) . "', 'error')</script>";
    exit;
}
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Detalle de Venta #<?= htmlspecialchars($venta['id_venta']) ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="vendidos.php">Vendidos</a></li>
                        <li class="breadcrumb-item active">Detalle</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">

            <!-- BOTONES DE ACCIÓN -->
            <div class="row mb-3">
                <div class="col-12">
                    <a href="vendidos.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="fas fa-print"></i> Imprimir
                    </button>
                    <button onclick="exportarPDF()" class="btn btn-danger">
                        <i class="fas fa-file-pdf"></i> Exportar PDF
                    </button>
                </div>
            </div>

            <div class="row">
                <!-- INFORMACIÓN DE LA VENTA -->
                <div class="col-md-4">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-info-circle"></i> Información General
                            </h3>
                        </div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-sm-5">N° Venta:</dt>
                                <dd class="col-sm-7">#<?= htmlspecialchars($venta['id_venta']) ?></dd>

                                <dt class="col-sm-5">Fecha:</dt>
                                <dd class="col-sm-7"><?= date('d/m/Y H:i', strtotime($venta['fecha'])) ?></dd>

                                <dt class="col-sm-5">Estado:</dt>
                                <dd class="col-sm-7">
                                    <span class="badge badge-success">
                                        <?= htmlspecialchars($venta['estado_logistico']) ?>
                                    </span>
                                </dd>

                                <dt class="col-sm-5">Método Pago:</dt>
                                <dd class="col-sm-7">
                                    <?= htmlspecialchars($venta['metodo_pago'] ?? 'No especificado') ?>
                                </dd>

                                <dt class="col-sm-5">Total:</dt>
                                <dd class="col-sm-7">
                                    <strong class="text-success" style="font-size: 1.2em;">
                                        $<?= number_format($venta['total'], 2, '.', ',') ?>
                                    </strong>
                                </dd>
                            </dl>

                            <?php if (!empty($venta['notas'])): ?>
                                <hr>
                                <dt>Notas:</dt>
                                <dd><?= nl2br(htmlspecialchars($venta['notas'])) ?></dd>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- INFORMACIÓN DEL CLIENTE -->
                <div class="col-md-4">
                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-user"></i> Datos del Cliente
                            </h3>
                        </div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-sm-4">Nombre:</dt>
                                <dd class="col-sm-8">
                                    <?= htmlspecialchars($venta['cliente_nombre']) ?>
                                </dd>

                                <dt class="col-sm-4">Teléfono:</dt>
                                <dd class="col-sm-8">
                                    <a href="tel:<?= htmlspecialchars($venta['cliente_telefono']) ?>">
                                        <i class="fas fa-phone"></i> 
                                        <?= htmlspecialchars($venta['cliente_telefono']) ?>
                                    </a>
                                </dd>

                                <dt class="col-sm-4">Email:</dt>
                                <dd class="col-sm-8">
                                    <a href="mailto:<?= htmlspecialchars($venta['cliente_email']) ?>">
                                        <i class="fas fa-envelope"></i> 
                                        <?= htmlspecialchars($venta['cliente_email']) ?>
                                    </a>
                                </dd>

                                <dt class="col-sm-4">Dirección:</dt>
                                <dd class="col-sm-8">
                                    <i class="fas fa-map-marker-alt"></i> 
                                    <?= htmlspecialchars($venta['cliente_direccion']) ?>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- INFORMACIÓN DEL VENDEDOR -->
                <div class="col-md-4">
                    <div class="card card-outline card-warning">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-user-tie"></i> Datos del Vendedor
                            </h3>
                        </div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-sm-4">Nombre:</dt>
                                <dd class="col-sm-8">
                                    <?= htmlspecialchars($venta['vendedor_nombre']) ?>
                                </dd>

                                <dt class="col-sm-4">Email:</dt>
                                <dd class="col-sm-8">
                                    <a href="mailto:<?= htmlspecialchars($venta['vendedor_email']) ?>">
                                        <i class="fas fa-envelope"></i> 
                                        <?= htmlspecialchars($venta['vendedor_email']) ?>
                                    </a>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DETALLE DE PRODUCTOS -->
            <div class="row">
                <div class="col-12">
                    <div class="card card-outline card-success">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-shopping-cart"></i> Productos
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th width="80">Imagen</th>
                                            <th>Código</th>
                                            <th>Producto</th>
                                            <th class="text-center" width="100">Cantidad</th>
                                            <th class="text-right" width="120">Precio Unit.</th>
                                            <th class="text-right" width="120">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $subtotal_general = 0;
                                        foreach ($detalles as $detalle): 
                                            $subtotal_general += $detalle['subtotal'];
                                        ?>
                                            <tr>
                                                <td class="text-center">
                                                    <?php if (!empty($detalle['producto_imagen'])): ?>
                                                        <img src="../almacen/img_productos/<?= htmlspecialchars($detalle['producto_imagen']) ?>" 
                                                             alt="<?= htmlspecialchars($detalle['producto_nombre']) ?>"
                                                             class="img-thumbnail"
                                                             style="max-width: 60px; max-height: 60px;">
                                                    <?php else: ?>
                                                        <i class="fas fa-image fa-3x text-muted"></i>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($detalle['producto_codigo']) ?></td>
                                                <td><?= htmlspecialchars($detalle['producto_nombre']) ?></td>
                                                <td class="text-center">
                                                    <span class="badge badge-primary">
                                                        <?= number_format($detalle['cantidad'], 0) ?>
                                                    </span>
                                                </td>
                                                <td class="text-right">
                                                    $<?= number_format($detalle['precio_unitario'], 2, '.', ',') ?>
                                                </td>
                                                <td class="text-right">
                                                    <strong>$<?= number_format($detalle['subtotal'], 2, '.', ',') ?></strong>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-light">
                                            <td colspan="5" class="text-right">
                                                <strong>TOTAL:</strong>
                                            </td>
                                            <td class="text-right">
                                                <strong class="text-success" style="font-size: 1.2em;">
                                                    $<?= number_format($venta['total'], 2, '.', ',') ?>
                                                </strong>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php
include('../layout/mensajes.php');
include('../layout/parte2.php');
?>

<!-- ESTILOS PARA IMPRESIÓN -->
<style>
@media print {
    .content-wrapper {
        margin: 0 !important;
        padding: 0 !important;
    }
    
    .btn, .breadcrumb, .card-header {
        display: none !important;
    }
    
    .card {
        border: 1px solid #000 !important;
        box-shadow: none !important;
        page-break-inside: avoid;
    }
    
    .card-body {
        padding: 10px !important;
    }
    
    body {
        font-size: 12px;
    }
}

/* Estilos generales */
.img-thumbnail {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 5px;
}

dl.row dt {
    font-weight: 600;
    color: #495057;
}

dl.row dd {
    color: #6c757d;
}

.table thead th {
    vertical-align: middle;
}

.table tbody td {
    vertical-align: middle;
}
</style>

<!-- SCRIPT PARA EXPORTAR PDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function exportarPDF() {
    const element = document.querySelector('.content');
    const opt = {
        margin: 10,
        filename: 'venta_<?= $venta['id_venta'] ?>.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    
    // Ocultar botones antes de generar PDF
    const botones = document.querySelector('.row.mb-3');
    botones.style.display = 'none';
    
    html2pdf().set(opt).from(element).save().then(() => {
        botones.style.display = 'block';
    });
}
</script