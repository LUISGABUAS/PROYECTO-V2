<?php
// ===========================
// print_zebra_direct.php
// ===========================

// Desactivar warnings y limpiar buffer
error_reporting(0);
ob_start();

// Configuración
require_once(dirname(__DIR__, 2) . '/config.php');

// ===========================
// RECIBIR IDS
// ===========================
$ids = $_GET['ids'] ?? '';
if (!$ids) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'No se enviaron IDs']);
    exit;
}

$ids_array = array_map('intval', explode(',', $ids));
$in = str_repeat('?,', count($ids_array) - 1) . '?';

// ===========================
// CONSULTAR STOCK
// ===========================
try {
    $sql = "SELECT s.codigo_unico, p.nombre
            FROM stock s
            JOIN tb_almacen p ON p.id_producto = s.id_producto
            WHERE s.id_stock IN ($in)
            ORDER BY s.id_stock ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($ids_array);
    $stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$stocks) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'No hay datos para imprimir']);
        exit;
    }
} catch (PDOException $e) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
    exit;
}

// ===========================
// GENERAR ZPL
// ===========================
$zpl = "";
foreach ($stocks as $stock) {
    $codigo   = $stock['codigo_unico'];
    $producto = strtoupper($stock['nombre']);

    $zpl .= "^XA
^JUS
^PW1240
^LL840
^LH0,0

^FO700,40
^A0R,50,50
^FD100 LBS.^FS

^FO700,1000
^A0R,50,50
^FD100 LBS.^FS

^FO600,420
^A0R,100,100
^FDYADIRA^FS

^FO300,250
^A0R,150,150
^FD{$producto}^FS

^BY3
^FO70,40
^BCR,120,Y,N,N
^FD{$codigo}^FS

^BY3
^FO70,640
^BCR,120,Y,N,N
^FD{$codigo}^FS

^XZ
";
}

// ===========================
// ENVIAR ZPL A LA IMPRESORA
// ===========================
$printer_ip = "192.168.1.43"; // Cambiar según impresora
$printer_port = 9100;

$fp = @fsockopen($printer_ip, $printer_port, $errno, $errstr, 5);
if (!$fp) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => "No se pudo conectar a la impresora: $errstr ($errno)"]);
    exit;
}

fwrite($fp, $zpl);
fclose($fp);

// ===========================
// RESPUESTA EXITOSA
// ===========================
ob_clean();
echo json_encode(['status' => 'success', 'message' => 'Impresión enviada correctamente']);
exit;
