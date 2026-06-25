<?php
include('../app/config.php');
include('../layout/sesion.php');

$id_venta = (int)($_GET['id'] ?? 0);
if (!$id_venta) die('Venta no válida');

// Datos de la venta y cliente
$stmt = $pdo->prepare("
    SELECT v.id_venta, v.fecha, v.envio, v.paqueteria, v.notas,
           c.nombre_completo AS cliente, c.telefono,
           COALESCE(d.nombre_destinatario, c.nombre_completo) AS destinatario,
           COALESCE(d.calle_numero, c.calle_numero) AS calle,
           COALESCE(d.colonia,   c.colonia)   AS colonia,
           COALESCE(d.municipio, c.municipio) AS municipio,
           COALESCE(d.estado,   c.estado)     AS estado,
           COALESCE(d.cp,       c.cp)         AS cp,
           COALESCE(d.referencias, c.referencias) AS referencias,
           u.nombres AS vendedor
    FROM tb_ventas v
    JOIN clientes c ON c.id_cliente = v.cliente
    JOIN tb_usuario u ON u.id = v.id_usuario
    LEFT JOIN clientes_direcciones d ON d.id = v.id_direccion_entrega
    WHERE v.id_venta = ?
");
$stmt->execute([$id_venta]);
$venta = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$venta) die('Venta no encontrada');

// Productos y pacas de la venta
$stmt2 = $pdo->prepare("
    SELECT a.nombre AS producto, a.codigo, vd.cantidad,
           vd.cantidad_entregada, vd.precio
    FROM tb_ventas_detalle vd
    JOIN tb_almacen a ON a.id_producto = vd.id_producto
    WHERE vd.id_venta = ?
    ORDER BY a.nombre
");
$stmt2->execute([$id_venta]);
$productos = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// Códigos escaneados (pacas ya dadas de baja)
$stmt3 = $pdo->prepare("
    SELECT s.codigo_unico, a.nombre AS producto, vs.fecha_scan
    FROM tb_ventas_stock vs
    JOIN stock s ON s.id_stock = vs.id_stock
    JOIN tb_almacen a ON a.id_producto = s.id_producto
    WHERE vs.id_venta = ?
    ORDER BY a.nombre, s.codigo_unico
");
$stmt3->execute([$id_venta]);
$codigos_escaneados = $stmt3->fetchAll(PDO::FETCH_ASSOC);

// Guías
$stmt4 = $pdo->prepare("SELECT numero, archivo FROM tb_ventas_guias WHERE id_venta = ? ORDER BY numero ASC");
$stmt4->execute([$id_venta]);
$guias = $stmt4->fetchAll(PDO::FETCH_ASSOC);
// Fallback guía antigua
if (empty($guias)) {
    $old = $pdo->prepare("SELECT guia_pdf FROM tb_ventas WHERE id_venta = ?");
    $old->execute([$id_venta]);
    $old_pdf = $old->fetchColumn();
    if ($old_pdf) $guias = [['numero' => 1, 'archivo' => $old_pdf]];
}

$total_pacas = array_sum(array_column($productos, 'cantidad'));
$multiplicador = ($venta['paqueteria'] === 'Estafeta') ? 2 : 1;
$guias_requeridas = $total_pacas * $multiplicador;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Hoja de Empaque — Venta #<?= $id_venta ?></title>
<style>
  * { box-sizing: border-box; }
  body { font-family: Arial, sans-serif; font-size: 13px; margin: 0; padding: 20px; color: #000; }
  h2 { margin: 0 0 4px; font-size: 18px; }
  .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 12px; }
  .info-block { display: flex; gap: 20px; margin-bottom: 12px; }
  .info-box { border: 1px solid #ccc; border-radius: 4px; padding: 8px 12px; flex: 1; }
  .info-box h4 { margin: 0 0 6px; font-size: 12px; text-transform: uppercase; color: #555; }
  .info-box p { margin: 2px 0; font-size: 13px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
  th { background: #222; color: #fff; padding: 6px 8px; text-align: left; font-size: 12px; }
  td { padding: 5px 8px; border-bottom: 1px solid #ddd; font-size: 12px; }
  tr:nth-child(even) td { background: #f9f9f9; }
  .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: bold; }
  .badge-ok { background: #d4edda; color: #155724; }
  .badge-pend { background: #fff3cd; color: #856404; }
  .badge-danger { background: #f8d7da; color: #721c24; }
  .guias-section { border: 2px solid #dc3545; border-radius: 6px; padding: 10px; margin-bottom: 12px; }
  .guias-section h4 { color: #dc3545; margin: 0 0 8px; }
  .guia-item { display: inline-block; margin: 4px; padding: 4px 10px; background: #dc3545; color: #fff; border-radius: 4px; font-size: 12px; }
  .footer { margin-top: 16px; border-top: 1px solid #ccc; padding-top: 8px; font-size: 11px; color: #666; }
  @media print {
    body { padding: 10px; }
    .no-print { display: none !important; }
    button { display: none; }
  }
</style>
</head>
<body>

<div class="no-print" style="margin-bottom:16px;">
  <button onclick="window.print()" style="background:#007bff;color:#fff;border:none;padding:8px 18px;border-radius:4px;cursor:pointer;font-size:14px;">
    🖨️ Imprimir hoja de empaque
  </button>
  <button onclick="window.close()" style="background:#6c757d;color:#fff;border:none;padding:8px 18px;border-radius:4px;cursor:pointer;font-size:14px;margin-left:8px;">
    ✕ Cerrar
  </button>
  <?php foreach ($guias as $g): ?>
  <a href="<?= $URL ?>/dashboard/guia_pdf/<?= $g['archivo'] ?>" target="_blank"
     style="display:inline-block;background:#dc3545;color:#fff;padding:8px 18px;border-radius:4px;font-size:14px;text-decoration:none;margin-left:8px;">
    📄 Ver Guía <?= $g['numero'] ?>
  </a>
  <?php endforeach; ?>
</div>

<div class="header">
  <div>
    <h2>HOJA DE EMPAQUE</h2>
    <div style="font-size:22px;font-weight:bold;color:#dc3545;">Venta #<?= $id_venta ?></div>
  </div>
  <div style="text-align:right;">
    <div style="font-size:13px;">Fecha: <strong><?= $venta['fecha'] ?></strong></div>
    <div style="font-size:13px;">Vendedor: <strong><?= htmlspecialchars($venta['vendedor']) ?></strong></div>
    <div style="font-size:13px;">Envío: <strong><?= strtoupper($venta['envio']) ?></strong></div>
    <?php if ($venta['paqueteria']): ?>
    <div style="font-size:13px;">Paquetería: <strong><?= htmlspecialchars($venta['paqueteria']) ?></strong></div>
    <?php endif; ?>
  </div>
</div>

<div class="info-block">
  <div class="info-box">
    <h4>👤 Cliente (factura)</h4>
    <p><strong><?= htmlspecialchars($venta['cliente']) ?></strong></p>
    <p><?= htmlspecialchars($venta['telefono']) ?></p>
    <?php if (!empty($venta['notas'])): ?>
    <p style="margin-top:6px;padding:5px 8px;background:#fff9c4;border-left:3px solid #f0ad00;border-radius:3px;">
      <strong>📝 Notas:</strong> <?= nl2br(htmlspecialchars($venta['notas'])) ?>
    </p>
    <?php endif; ?>
  </div>
  <div class="info-box" style="border-color:#dc3545;">
    <h4>📦 Destinatario (envío)</h4>
    <p><strong><?= htmlspecialchars($venta['destinatario']) ?></strong></p>
    <p><?= htmlspecialchars($venta['calle']) ?>, <?= htmlspecialchars($venta['colonia']) ?></p>
    <p><?= htmlspecialchars($venta['municipio']) ?>, <?= htmlspecialchars($venta['estado']) ?> CP <?= htmlspecialchars($venta['cp']) ?></p>
    <?php if ($venta['referencias']): ?>
    <p style="color:#666;font-style:italic;"><?= htmlspecialchars($venta['referencias']) ?></p>
    <?php endif; ?>
  </div>
</div>

<!-- GUÍAS -->
<?php if (!empty($guias)): ?>
<div class="guias-section">
  <h4>📄 GUÍAS DE ENVÍO (<?= count($guias) ?> / <?= $guias_requeridas ?> requeridas)</h4>
  <?php foreach ($guias as $g): ?>
  <span class="guia-item">Guía <?= $g['numero'] ?>: <?= $g['archivo'] ?></span>
  <?php endforeach; ?>
  <?php if (count($guias) < $guias_requeridas): ?>
  <div style="color:#dc3545;margin-top:6px;font-weight:bold;">
    ⚠️ Faltan <?= $guias_requeridas - count($guias) ?> guía(s)
  </div>
  <?php endif; ?>
</div>
<?php else: ?>
<div style="background:#fff3cd;border:1px solid #ffc107;padding:8px;border-radius:4px;margin-bottom:12px;">
  ⚠️ <strong>Sin guía registrada</strong> — Registrar antes de enviar
</div>
<?php endif; ?>

<!-- PRODUCTOS -->
<table>
  <thead>
    <tr>
      <th>Producto</th>
      <th>Código</th>
      <th style="text-align:center;">Vendidas</th>
      <th style="text-align:center;">Dadas de baja</th>
      <th style="text-align:center;">Estado</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($productos as $p):
      $entregadas = $p['cantidad_entregada'];
      $completo = $entregadas >= $p['cantidad'];
    ?>
    <tr>
      <td><?= htmlspecialchars($p['producto']) ?></td>
      <td><?= htmlspecialchars($p['codigo']) ?></td>
      <td style="text-align:center;"><?= $p['cantidad'] ?></td>
      <td style="text-align:center;"><?= $entregadas ?></td>
      <td style="text-align:center;">
        <?php if ($completo): ?>
          <span class="badge badge-ok">✓ COMPLETO</span>
        <?php else: ?>
          <span class="badge badge-pend">Faltan <?= $p['cantidad'] - $entregadas ?></span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    <tr style="background:#f0f0f0;font-weight:bold;">
      <td colspan="2">TOTAL PACAS</td>
      <td style="text-align:center;"><?= $total_pacas ?></td>
      <td style="text-align:center;"><?= array_sum(array_column($productos, 'cantidad_entregada')) ?></td>
      <td style="text-align:center;">
        <?php $total_ent = array_sum(array_column($productos, 'cantidad_entregada'));
        if ($total_ent >= $total_pacas): ?>
          <span class="badge badge-ok">✓ LISTO</span>
        <?php else: ?>
          <span class="badge badge-danger">Faltan <?= $total_pacas - $total_ent ?></span>
        <?php endif; ?>
      </td>
    </tr>
  </tbody>
</table>

<!-- ASIGNACIÓN PACA → GUÍA -->
<h4 style="margin-bottom:6px;color:#dc3545;">📦 Asignación de guía por paca</h4>
<table>
  <thead>
    <tr>
      <th>#Paca</th>
      <th>Código de paca</th>
      <th>Producto</th>
      <th style="background:#dc3545;">Guía(s) a pegar</th>
      <th>Escaneado</th>
    </tr>
  </thead>
  <tbody>
    <?php if (!empty($codigos_escaneados)):
      foreach ($codigos_escaneados as $i => $cod):
        $num_paca = $i + 1;
        if ($multiplicador === 2) {
            $idx1 = ($num_paca * 2) - 2;
            $idx2 = ($num_paca * 2) - 1;
            $guias_paca = array_filter([$guias[$idx1] ?? null, $guias[$idx2] ?? null]);
        } else {
            $guias_paca = isset($guias[$i]) ? [$guias[$i]] : [];
        }
    ?>
    <tr>
      <td style="text-align:center;font-weight:bold;font-size:16px;"><?= $num_paca ?></td>
      <td><strong><?= htmlspecialchars($cod['codigo_unico']) ?></strong></td>
      <td><?= htmlspecialchars($cod['producto']) ?></td>
      <td style="background:#fff3cd;">
        <?php if (!empty($guias_paca)): ?>
          <?php foreach ($guias_paca as $g): ?>
          <a href="<?= $URL ?>/dashboard/guia_pdf/<?= $g['archivo'] ?>" target="_blank"
             style="display:inline-block;padding:3px 8px;background:#dc3545;color:#fff;border-radius:4px;text-decoration:none;margin:2px;font-size:12px;">
            Guía <?= $g['numero'] ?>
          </a>
          <?php endforeach; ?>
        <?php else: ?>
          <span style="color:#856404;">Sin guía aún</span>
        <?php endif; ?>
      </td>
      <td><?= $cod['fecha_scan'] ?></td>
    </tr>
    <?php endforeach;
    else: ?>
    <tr><td colspan="5" style="text-align:center;color:#666;">Aún no se han escaneado pacas para esta venta</td></tr>
    <?php endif; ?>
  </tbody>
</table>

<div class="footer">
  Generado: <?= date('d/m/Y H:i') ?> — Sistema Pacas Yadira
</div>

</body>
</html>
