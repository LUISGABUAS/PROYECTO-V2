<?php
require_once(dirname(__DIR__, 2) . '/config.php');
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

if (empty($_SESSION['permisos'])) {
    echo json_encode(['success' => false, 'message' => 'Sin sesion']); exit;
}

$id_venta = intval($_GET['id_venta'] ?? 0);
if (!$id_venta) {
    echo json_encode(['success' => false, 'message' => 'Venta invalida']); exit;
}

function zt($str) {
    $out = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string)$str);
    return ($out !== false) ? $out : preg_replace('/[^\x20-\x7E]/', '?', (string)$str);
}

// ── Datos ─────────────────────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT v.id_venta, v.fecha, v.envio, v.paqueteria, v.notas,
           v.tipo_pago, v.monto_pendiente, v.metodo_pendiente, v.total,
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
if (!$v) { echo json_encode(['success' => false, 'message' => 'Venta no encontrada']); exit; }

// Pacas escaneadas (codigo_unico de cada paca de la venta)
$stmt2 = $pdo->prepare("
    SELECT s.codigo_unico, a.nombre AS producto
    FROM tb_ventas_stock vs
    JOIN stock s      ON s.id_stock    = vs.id_stock
    JOIN tb_almacen a ON a.id_producto = s.id_producto
    WHERE vs.id_venta = ?
    ORDER BY a.nombre ASC, s.codigo_unico ASC
");
$stmt2->execute([$id_venta]);
$pacas = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// Detalle para total de pacas vendidas
$stmt2b = $pdo->prepare("
    SELECT SUM(vd.cantidad) AS total_vendidas
    FROM tb_ventas_detalle vd WHERE vd.id_venta = ?
");
$stmt2b->execute([$id_venta]);
$total_pacas = (int)$stmt2b->fetchColumn();

$guias = [];
if ($v['envio'] === 'foraneo') {
    $stmt3 = $pdo->prepare("SELECT numero FROM tb_ventas_guias WHERE id_venta = ? ORDER BY numero ASC");
    $stmt3->execute([$id_venta]);
    $guias = $stmt3->fetchAll(PDO::FETCH_ASSOC);
}

$total_pacas = array_sum(array_column($productos, 'cantidad'));

// ── ZPL con rotacion B ────────────────────────────────────────────────
// Fisico: 839 dots ancho (4") × LL dots largo
// Para leer: girar 90°CW → LL wide × 839 tall
//
// Coordenadas en lectura:
//   visual_y (arriba-abajo)  = vy cursor  →  ZPL x = W - vy - h
//   visual_x (izq-derecha)   = ZPL y      →  columnas fijas
//
// Fuentes ^A0B,h,w:
//   h = altura del caracter (en ZPL x → visual_y)
//   w = ancho del caracter  (en ZPL y → visual_x, fluye derecha)

$W   = 839;   // ancho fisico = altura visual
$LL  = 1100;  // largo fisico = ancho visual (columnas hasta ~1080)
$LM  = 25;    // margen izq visual = ZPL y minimo
$FW  = $LL - $LM - 15;  // ancho maximo de campo full-width
$vy  = 12;    // cursor visual Y
$L   = [];    // comandos ZPL

// Helpers
$zx = fn($h) => $W - $vy - $h;  // ZPL x para font height h

// Texto completo (toda la anchura visual)
$full = function($h, $w, $text, $sp = 10, $just = 'L')
        use (&$L, &$vy, $W, $LM, $FW) {
    $x = $W - $vy - $h;
    if ($x >= 0)
        $L[] = "^FO{$x},{$LM}^A0B,{$h},{$w}^FB{$FW},1,0,{$just}^FD{$text}^FS";
    $vy += $h + $sp;
};

// Texto en columna especifica (mismo vy, distinto ZPL y)
$col = function($h, $w, $text, $zy) use (&$L, &$vy, $W) {
    $x = $W - $vy - $h;
    if ($x >= 0)
        $L[] = "^FO{$x},{$zy}^A0B,{$h},{$w}^FD{$text}^FS";
    // NO incrementa vy — llamar manualmente $vy +=
};

// Separador grueso
$sep = function($sp = 14) use (&$L, &$vy, $W, $LM, $LL) {
    $x = $W - $vy - 2;
    if ($x >= 0)
        $L[] = "^FO{$x},{$LM}^GB2," . ($LL - $LM - 15) . ",2^FS";
    $vy += 2 + $sp;
};

// Separador delgado
$thin = function($sp = 8) use (&$L, &$vy, $W, $LM, $LL) {
    $x = $W - $vy - 1;
    if ($x >= 0)
        $L[] = "^FO{$x},{$LM}^GB1," . ($LL - $LM - 15) . ",1^FS";
    $vy += 1 + $sp;
};

// ── CABECERA ──────────────────────────────────────────────────────────
// "HOJA DE EMPAQUE" izquierda  |  "#148" derecha
$col(55, 32, 'HOJA DE EMPAQUE', $LM);
$col(55, 32, '#' . $id_venta, 820);
$vy += 55 + 12;
$sep();

// Fecha + Envio en misma fila
$col(32, 18, 'Fecha: ' . zt($v['fecha']), $LM);
$col(32, 18, 'Envio: ' . strtoupper(zt($v['envio'])), 580);
if ($v['paqueteria']) $col(32, 18, zt($v['paqueteria']), 820);
$vy += 32 + 8;

// Vendedor
$full(32, 18, 'Vendedor: ' . substr(zt($v['vendedor']), 0, 24), 12);

// ── PAGO ─────────────────────────────────────────────────────────────
$tp          = $v['tipo_pago'] ?? '';
$total_fmt   = '$' . number_format((float)$v['total'], 2);
if ($tp !== 'contra_entrega') {
    $pago_label = match($tp) {
        'efectivo'   => 'PAGO COMPLETO - EFECTIVO',
        'comprobante'=> 'PAGO COMPLETO - COMPROBANTE',
        'ambos'      => 'PAGO COMPLETO - EFECTIVO + COMPROBANTE',
        default      => 'PAGO COMPLETO',
    };
    $col(32, 18, $pago_label, $LM);
    $col(32, 18, $total_fmt, 820);
    $vy += 32 + 12;
}
$sep();

// ── CONTRA ENTREGA (caja negra destacada) ─────────────────────────────
if ($tp === 'contra_entrega' && $v['monto_pendiente'] > 0) {
    $ce_h  = 52;
    $monto = '$' . number_format((float)$v['monto_pendiente'], 2);
    $met   = strtoupper(zt($v['metodo_pendiente'] ?? ''));
    $zx_ce = $W - $vy - $ce_h;
    if ($zx_ce >= 0) {
        $L[] = "^FO{$zx_ce},{$LM}^GB{$ce_h}," . ($LL - $LM - 15) . ",{$ce_h}^FS";
        $L[] = "^FO{$zx_ce},{$LM}^FR^A0B,{$ce_h},30^FB{$FW},1,0,L"
             . "^FD  COBRAR EN ENTREGA: {$monto}  ({$met})^FS";
    }
    $vy += $ce_h + 12;
    $sep();
}

// ── CLIENTE ───────────────────────────────────────────────────────────
$full(26, 14, 'CLIENTE:', 5);
$full(44, 27, substr(zt($v['cliente']), 0, 28), 6);
$full(32, 18, 'Tel: ' . zt($v['telefono']), 10);

if (!empty($v['notas'])) {
    $thin();
    $full(26, 14, 'NOTAS:', 4);
    $nota = str_replace(["\r\n","\r","\n"], ' ', zt($v['notas']));
    foreach (str_split($nota, 44) as $ln) $full(30, 17, $ln, 4);
}
$sep();

// ── UBICACION A ENTREGAR ──────────────────────────────────────────────
$full(26, 14, 'UBICACION A ENTREGAR:', 5);
$full(42, 26, substr(zt($v['destinatario']), 0, 28), 5);
$full(30, 17, substr(zt($v['calle']), 0, 43), 4);
$full(30, 17, substr(zt($v['colonia']), 0, 43), 4);
$full(30, 17, substr(zt($v['municipio'] . ', ' . $v['estado']), 0, 43), 4);
$full(30, 17, 'CP ' . zt($v['cp']), 10);

if (!empty($v['referencias'])) {
    $thin();
    $full(26, 14, 'REFERENCIAS:', 4);
    $ref = str_replace(["\r\n","\r","\n"], ' ', zt($v['referencias']));
    foreach (str_split($ref, 44) as $ln) $full(30, 17, $ln, 4);
}
$sep();

// ── GUIAS ─────────────────────────────────────────────────────────────
if (!empty($guias)) {
    $nums = implode('  ', array_map(fn($g) => 'Guia ' . $g['numero'], $guias));
    $full(32, 18, 'GUIAS (' . count($guias) . '): ' . $nums, 10);
    $sep();
}

// ── PACAS ─────────────────────────────────────────────────────────────
$n_escaneadas = count($pacas);
$full(36, 21, 'PACAS (' . $n_escaneadas . ' de ' . $total_pacas . '):', 8);
$thin();

$H_ROW = 42;
foreach ($pacas as $p) {
    if ($W - $vy - $H_ROW < 0) break;
    $codigo  = zt($p['codigo_unico']);
    $prod    = substr(zt($p['producto']), 0, 28);
    $zx_r    = $W - $vy - $H_ROW;
    $fw_cod  = 500;
    $fw_prod = $LL - $LM - 510;
    $L[] = "^FO{$zx_r},{$LM}^A0B,{$H_ROW},22^FB{$fw_cod},1,0,L^FD{$codigo}^FS";
    $L[] = "^FO{$zx_r},530^A0B,{$H_ROW},22^FB{$fw_prod},1,0,L^FD{$prod}^FS";
    $vy += $H_ROW + 6;
}

$sep();
$full(22, 12, 'Generado: ' . date('d/m/Y H:i'), 0);

// ── ARMAR ZPL ─────────────────────────────────────────────────────────
$zpl = "^XA\n^PW{$W}\n^LL{$LL}\n^LS0\n"
     . implode("\n", $L)
     . "\n^XZ";

// ── PREVIEW via Labelary ──────────────────────────────────────────────
if (isset($_GET['preview'])) {
    $h_in = round($LL / 203, 1);
    $ch   = curl_init("https://api.labelary.com/v1/printers/8dpmm/labels/4x{$h_in}/");
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
        header('Content-Disposition: inline; filename="empaque_' . $id_venta . '.pdf"');
        echo $pdf;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'http' => $http, 'zpl_len' => strlen($zpl)]);
    }
    exit;
}

// ── COLA ──────────────────────────────────────────────────────────────
try {
    $pdo->prepare("INSERT INTO print_queue (zpl, status, created_at) VALUES (?, 'pendiente', NOW())")
        ->execute([$zpl]);
    echo json_encode(['success' => true, 'message' => "Hoja de empaque #$id_venta enviada a la Zebra"]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
