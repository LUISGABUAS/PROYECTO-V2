<?php
require_once(dirname(__DIR__, 2) . '/config.php');
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

if (empty($_SESSION['permisos'])) {
    echo json_encode(['success' => false, 'message' => 'Sin sesión']);
    exit;
}

$id_venta = intval($_GET['id_venta'] ?? 0);
if (!$id_venta) {
    echo json_encode(['success' => false, 'message' => 'Venta inválida']);
    exit;
}

// Transliterar UTF-8 → ASCII para ZPL seguro
function zt($str) {
    $out = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string)$str);
    return ($out !== false) ? $out : preg_replace('/[^\x20-\x7E]/', '?', (string)$str);
}

// ── Datos de la venta ────────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT v.id_venta, v.fecha, v.envio, v.paqueteria, v.notas,
           c.nombre_completo AS cliente, c.telefono,
           COALESCE(d.nombre_destinatario, c.nombre_completo) AS destinatario,
           COALESCE(d.calle_numero, c.calle_numero)  AS calle,
           COALESCE(d.colonia,   c.colonia)           AS colonia,
           COALESCE(d.municipio, c.municipio)         AS municipio,
           COALESCE(d.estado,   c.estado)             AS estado,
           COALESCE(d.cp,       c.cp)                 AS cp,
           u.nombres AS vendedor
    FROM tb_ventas v
    JOIN clientes c  ON c.id_cliente = v.cliente
    JOIN tb_usuario u ON u.id = v.id_usuario
    LEFT JOIN clientes_direcciones d ON d.id = v.id_direccion_entrega
    WHERE v.id_venta = ?
");
$stmt->execute([$id_venta]);
$v = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$v) {
    echo json_encode(['success' => false, 'message' => 'Venta no encontrada']);
    exit;
}

// Productos
$stmt2 = $pdo->prepare("
    SELECT a.nombre AS producto, vd.cantidad, vd.cantidad_entregada
    FROM tb_ventas_detalle vd
    JOIN tb_almacen a ON a.id_producto = vd.id_producto
    WHERE vd.id_venta = ? ORDER BY a.nombre
");
$stmt2->execute([$id_venta]);
$productos = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// Guías (solo foráneo)
$guias = [];
if ($v['envio'] === 'foraneo') {
    $stmt3 = $pdo->prepare("SELECT numero FROM tb_ventas_guias WHERE id_venta = ? ORDER BY numero ASC");
    $stmt3->execute([$id_venta]);
    $guias = $stmt3->fetchAll(PDO::FETCH_ASSOC);
}

$total_pacas      = array_sum(array_column($productos, 'cantidad'));
$total_entregadas = array_sum(array_column($productos, 'cantidad_entregada'));

// ── Constructor ZPL ──────────────────────────────────────────────────
// Label: 839 dots ancho (4"), altura dinámica, 8 dpmm
$W  = 839;
$LM = 20;           // margen izquierdo
$y  = 15;           // cursor Y
$L  = [];           // líneas ZPL

$sep = function() use (&$L, &$y, $W, $LM) {
    $L[] = "^FO{$LM},{$y}^GB" . ($W - $LM * 2) . ",2,2^FS";
    $y  += 10;
};

// ── CABECERA ─────────────────────────────────────────────────────────
$L[] = "^FO{$LM},{$y}^A0N,40,26^FDHOJA DE EMPAQUE^FS";
$L[] = "^FO620,{$y}^A0N,40,24^FD#" . $id_venta . "^FS";
$y  += 48;
$sep();

// Fecha / Vendedor
$L[] = "^FO{$LM},{$y}^A0N,23,13^FDFecha: " . zt($v['fecha']) . "^FS";
$L[] = "^FO430,{$y}^A0N,23,13^FDVendedor: " . substr(zt($v['vendedor']), 0, 17) . "^FS";
$y  += 30;

// Envío / Paquetería
$L[] = "^FO{$LM},{$y}^A0N,23,13^FDEnvio: " . strtoupper(zt($v['envio'])) . "^FS";
if ($v['paqueteria']) {
    $L[] = "^FO430,{$y}^A0N,23,13^FDPaqueteria: " . zt($v['paqueteria']) . "^FS";
}
$y += 30;
$sep();

// ── CLIENTE ──────────────────────────────────────────────────────────
$L[] = "^FO{$LM},{$y}^A0N,26,15^FDCLIENTE: " . substr(zt($v['cliente']), 0, 33) . "^FS";
$y  += 32;
$L[] = "^FO{$LM},{$y}^A0N,22,12^FDTel: " . zt($v['telefono']) . "^FS";
$y  += 28;

if (!empty($v['notas'])) {
    $nota_lines = str_split(zt($v['notas']), 55);
    $L[] = "^FO{$LM},{$y}^A0N,20,11^FDNotas: " . $nota_lines[0] . "^FS";
    $y  += 24;
    if (isset($nota_lines[1])) {
        $L[] = "^FO{$LM},{$y}^A0N,20,11^FD       " . $nota_lines[1] . "^FS";
        $y  += 24;
    }
}
$sep();

// ── DESTINATARIO (solo foráneo) ───────────────────────────────────────
if ($v['envio'] === 'foraneo') {
    $L[] = "^FO{$LM},{$y}^A0N,26,15^FDDESTINO: " . substr(zt($v['destinatario']), 0, 33) . "^FS";
    $y  += 32;
    $dir1 = substr(zt($v['calle'] . ', ' . $v['colonia']), 0, 52);
    $L[] = "^FO{$LM},{$y}^A0N,21,12^FD{$dir1}^FS";
    $y  += 26;
    $dir2 = substr(zt($v['municipio'] . ', ' . $v['estado'] . ' CP ' . $v['cp']), 0, 52);
    $L[] = "^FO{$LM},{$y}^A0N,21,12^FD{$dir2}^FS";
    $y  += 26;
    $sep();
}

// ── GUÍAS (solo foráneo) ──────────────────────────────────────────────
if (!empty($guias)) {
    $nums  = implode(', ', array_column($guias, 'numero'));
    $L[]   = "^FO{$LM},{$y}^A0N,23,13^FDGUIAS (" . count($guias) . "): " . $nums . "^FS";
    $y    += 30;
    $sep();
}

// ── PRODUCTOS ─────────────────────────────────────────────────────────
$L[] = "^FO{$LM},{$y}^A0N,26,15^FDPRODUCTOS^FS";
$L[] = "^FO660,{$y}^A0N,23,13^FDTotal: {$total_pacas}^FS";
$y  += 32;

// Encabezado tabla
$L[] = "^FO{$LM},{$y}^GB" . ($W - $LM * 2) . ",1,1^FS";
$y  += 4;
$L[] = "^FO{$LM},{$y}^A0N,20,11^FDPRODUCTO^FS";
$L[] = "^FO570,{$y}^A0N,20,11^FDVEND.^FS";
$L[] = "^FO650,{$y}^A0N,20,11^FDENT.^FS";
$L[] = "^FO730,{$y}^A0N,20,11^FDEST.^FS";
$y  += 22;
$L[] = "^FO{$LM},{$y}^GB" . ($W - $LM * 2) . ",1,1^FS";
$y  += 4;

foreach ($productos as $p) {
    $nombre  = substr(zt($p['producto']), 0, 32);
    $ok      = $p['cantidad_entregada'] >= $p['cantidad'];
    $estado  = $ok ? 'OK' : '-' . ($p['cantidad'] - $p['cantidad_entregada']);

    $L[] = "^FO{$LM},{$y}^A0N,22,12^FD{$nombre}^FS";
    $L[] = "^FO570,{$y}^A0N,22,13^FD" . $p['cantidad'] . "^FS";
    $L[] = "^FO650,{$y}^A0N,22,13^FD" . $p['cantidad_entregada'] . "^FS";
    $L[] = "^FO730,{$y}^A0N,22,12^FD{$estado}^FS";
    $y  += 28;
}

// ── PIE ───────────────────────────────────────────────────────────────
$y  += 5;
$sep();
$L[] = "^FO{$LM},{$y}^A0N,19,11^FDGenerado: " . date('d/m/Y H:i') . "^FS";
$y  += 26;

// ── ARMAR ZPL ────────────────────────────────────────────────────────
$ll  = $y + 30;
$zpl = "^XA\n^PW{$W}\n^LL{$ll}\n^LS0\n"
     . implode("\n", $L)
     . "\n^XZ";

// ── INSERTAR EN COLA ─────────────────────────────────────────────────
try {
    $pdo->prepare("INSERT INTO print_queue (zpl, status, created_at) VALUES (?, 'pendiente', NOW())")
        ->execute([$zpl]);
    echo json_encode(['success' => true, 'message' => "Hoja de empaque #$id_venta enviada a la Zebra"]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
