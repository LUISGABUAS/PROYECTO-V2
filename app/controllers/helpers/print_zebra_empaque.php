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
           COALESCE(d.referencias, c.referencias)     AS referencias,
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

// ── Constructor ZPL VERTICAL (portrait 4"×dinámica = 839 dots ancho) ──
$W  = 839;   // ancho 4"
$LM = 20;    // margen izquierdo
$y  = 18;    // cursor Y
$L  = [];    // líneas ZPL

$sep = function() use (&$L, &$y, $W, $LM) {
    $L[] = "^FO{$LM},{$y}^GB" . ($W - $LM * 2) . ",2,2^FS";
    $y  += 10;
};
$sep_thin = function() use (&$L, &$y, $W, $LM) {
    $L[] = "^FO{$LM},{$y}^GB" . ($W - $LM * 2) . ",1,1^FS";
    $y  += 5;
};

// ── CABECERA ────────────────────────────────────────────────────────
$L[] = "^FO{$LM},{$y}^A0N,38,24^FDHOJA DE EMPAQUE^FS";
$L[] = "^FO640,{$y}^A0N,38,24^FD#" . $id_venta . "^FS";
$y  += 46;
$sep();

$L[] = "^FO{$LM},{$y}^A0N,26,15^FDFecha: " . zt($v['fecha']) . "^FS";
$L[] = "^FO440,{$y}^A0N,26,15^FDEnvio: " . strtoupper(zt($v['envio'])) . "^FS";
$y  += 32;
$L[] = "^FO{$LM},{$y}^A0N,26,15^FDVendedor: " . substr(zt($v['vendedor']), 0, 28) . "^FS";
if ($v['paqueteria']) {
    $L[] = "^FO440,{$y}^A0N,26,15^FD" . zt($v['paqueteria']) . "^FS";
}
$y  += 34;
$sep();

// ── CLIENTE ─────────────────────────────────────────────────────────
$L[] = "^FO{$LM},{$y}^A0N,24,14^FDCLIENTE^FS";
$y  += 28;
$L[] = "^FO{$LM},{$y}^A0N,30,18^FD" . substr(zt($v['cliente']), 0, 38) . "^FS";
$y  += 36;
$L[] = "^FO{$LM},{$y}^A0N,26,15^FDTel: " . zt($v['telefono']) . "^FS";
$y  += 32;

if (!empty($v['notas'])) {
    $sep_thin();
    $L[] = "^FO{$LM},{$y}^A0N,24,14^FDNOTAS:^FS";
    $y  += 28;
    $nota = str_replace(["\r\n", "\r", "\n"], ' ', zt($v['notas']));
    foreach (str_split($nota, 48) as $ln) {
        $L[] = "^FO{$LM},{$y}^A0N,26,15^FD{$ln}^FS";
        $y  += 30;
    }
}
$sep();

// ── UBICACIÓN A ENTREGAR ─────────────────────────────────────────────
$L[] = "^FO{$LM},{$y}^A0N,24,14^FDUBICACION A ENTREGAR^FS";
$y  += 28;
$L[] = "^FO{$LM},{$y}^A0N,30,18^FD" . substr(zt($v['destinatario']), 0, 38) . "^FS";
$y  += 36;
$L[] = "^FO{$LM},{$y}^A0N,26,15^FD" . substr(zt($v['calle']), 0, 48) . "^FS";
$y  += 30;
$L[] = "^FO{$LM},{$y}^A0N,26,15^FD" . substr(zt($v['colonia']), 0, 48) . "^FS";
$y  += 30;
$L[] = "^FO{$LM},{$y}^A0N,26,15^FD" . substr(zt($v['municipio'] . ', ' . $v['estado']), 0, 48) . "^FS";
$y  += 30;
$L[] = "^FO{$LM},{$y}^A0N,26,15^FDCP " . zt($v['cp']) . "^FS";
$y  += 32;

if (!empty($v['referencias'])) {
    $sep_thin();
    $L[] = "^FO{$LM},{$y}^A0N,24,14^FDREFERENCIAS:^FS";
    $y  += 28;
    $ref = str_replace(["\r\n", "\r", "\n"], ' ', zt($v['referencias']));
    foreach (str_split($ref, 48) as $ln) {
        $L[] = "^FO{$LM},{$y}^A0N,26,15^FD{$ln}^FS";
        $y  += 30;
    }
}
$sep();

// ── GUÍAS (solo foráneo) ─────────────────────────────────────────────
if (!empty($guias)) {
    $L[] = "^FO{$LM},{$y}^A0N,26,15^FDGUIAS (" . count($guias) . "):^FS";
    $y  += 30;
    foreach ($guias as $g) {
        $L[] = "^FO{$LM},{$y}^A0N,26,15^FD  Guia " . $g['numero'] . "^FS";
        $y  += 30;
    }
    $sep();
}

// ── PRODUCTOS ────────────────────────────────────────────────────────
$L[] = "^FO{$LM},{$y}^A0N,28,16^FDPRODUCTOS - Total: {$total_pacas}^FS";
$y  += 34;
$sep_thin();
$L[] = "^FO{$LM},{$y}^A0N,22,12^FDPRODUCTO^FS";
$L[] = "^FO530,{$y}^A0N,22,12^FDVEND.^FS";
$L[] = "^FO630,{$y}^A0N,22,12^FDENT.^FS";
$L[] = "^FO730,{$y}^A0N,22,12^FDEST.^FS";
$y  += 26;
$sep_thin();

foreach ($productos as $p) {
    $nombre = substr(zt($p['producto']), 0, 34);
    $ok     = $p['cantidad_entregada'] >= $p['cantidad'];
    $estado = $ok ? 'OK' : '-' . ($p['cantidad'] - $p['cantidad_entregada']);
    $L[] = "^FO{$LM},{$y}^A0N,26,14^FD{$nombre}^FS";
    $L[] = "^FO530,{$y}^A0N,26,15^FD" . $p['cantidad'] . "^FS";
    $L[] = "^FO630,{$y}^A0N,26,15^FD" . $p['cantidad_entregada'] . "^FS";
    $L[] = "^FO730,{$y}^A0N,26,14^FD{$estado}^FS";
    $y  += 32;
}

// ── PIE ──────────────────────────────────────────────────────────────
$y += 5;
$sep();
$L[] = "^FO{$LM},{$y}^A0N,20,11^FDGenerado: " . date('d/m/Y H:i') . "^FS";
$y  += 26;

// ── ARMAR ZPL ────────────────────────────────────────────────────────
$ll  = $y + 30;
$zpl = "^XA\n^PW{$W}\n^LL{$ll}\n^LS0\n"
     . implode("\n", $L)
     . "\n^XZ";

// ── MODO PREVIEW (Labelary → PDF) ────────────────────────────────────
if (isset($_GET['preview'])) {
    $ch = curl_init("https://api.labelary.com/v1/printers/8dpmm/labels/4x6/");
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $zpl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => ["Accept: application/pdf"],
    ]);
    $pdf  = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http === 200) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="empaque_preview_' . $id_venta . '.pdf"');
        echo $pdf;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => "Labelary error HTTP $http", 'zpl_size' => strlen($zpl)]);
    }
    exit;
}

// ── INSERTAR EN COLA ─────────────────────────────────────────────────
try {
    $pdo->prepare("INSERT INTO print_queue (zpl, status, created_at) VALUES (?, 'pendiente', NOW())")
        ->execute([$zpl]);
    echo json_encode(['success' => true, 'message' => "Hoja de empaque #$id_venta enviada a la Zebra"]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
