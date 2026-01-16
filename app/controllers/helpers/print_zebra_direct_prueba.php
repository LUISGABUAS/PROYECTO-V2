<?php
// ===========================
// print_zebra_direct.php
// ===========================

// Desactivar warnings y limpiar buffer
error_reporting(0);
ob_start();

// Configuración
include('../../config.php');

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

    $zpl .= "
^XA
^JUS
^MMT
^PW839
^LL1239
^LS0
^BY3,3,193^FT722,850^BCB,,Y,N
^FH\^FD{$codigo}^FS

^FO75,64^GB706,1115,8^FS


^FO300,350
^FB600,3,0,C
^A0B,100,100
^FD{$producto}^FS

^FO107,492^GFA,609,3050,10,:Z64:eJzt1bFxwzAMBVAoKtiFI2iTKCOlTGd22SrHbKIRWKrQCSFA4tO2GDc5X1zE1TvZlshPACLSzxutBfQOrUc56GTybGKTh9jkITZ508CmyVQuifxdtJh22a/qUzLQZb7IjkVkShCZFtNOVWM05b1FE2+mE8enkrPLF2vO+SJZQhshta9DkgOUUzINEM2dMxqhoXOWZ2pn3uqATM/QFKBo8guemy407FYRnkNd/cyLY93niZMvyt8U5d9sk2kvGrNmExe5a61FfKV65glaoAgF0yZnmcjtuvN8fuNaFCQ8U9KEqm52ynC32r2zgu3jTKgS92OV/Fbtzu1p3bX8fUKPmum8VzmpddWsha1ziGV4ZUkj5QbI0lZJpB2qC7UJLL0gf5XxqL1a/rxS2VHAfG6KpUNZu6ypdmPq6yyhJEuXyZU7j2r39FKDbqfxAGf+KFXy/2b/f7Nf1kGbtoQKI1QdWQ9eaYHwFE2oiCOUoA3iUFbgo04mne3zSrXa9T5a7fJalR3l9b3OlsuACnPQdKy68VB/rowuuTMq0X/cs/42vPcXvK2Caas716FbazJYQjuyWjuZdrJv59HOrXO+BH0DVBCjjA==:72E2
^FO118,907^GFA,373,1218,6,:Z64:eJzVkkFqxDAMRX8wVLvxEXyTybW6s0sXcy0fJUdIdykN+ZWiDMmQDIRCCxX4LRL56wt/AHjVg2ZaGXjMbY/fOlMZEL6xKt/Zq9SNA8IQOSJ0iROktsbSkhDkhREX5ElUgKMU5RAKGr2Ky8wY2IciN/ZShOwExqj6rGnmVdtZJmNQz3lKukU7GmOXeOw2ViCh7bW/Zh3DTn02/DC3/FSfd36ZT/KYNZWoW9gR3ePObT29e56msvDFHmm3C1eK7+tsZyZv2tB1ZHEqf+XzFyr3NogW1agh0JU1HLBw6Q81NVgq5jfl/JrGvee9W/+ybLTp3+q4sk9ZJvp0d+Ku3OG/93yuHvL0wxQFZzGFgJXf3HmPnw==:EDFC
^FO118,92^GFA,373,1218,6,:Z64:eJzVkkFqxDAMRX8wVLvxEXyTybW6s0sXcy0fJUdIdykN+ZWiDMmQDIRCCxX4LRL56wt/AHjVg2ZaGXjMbY/fOlMZEL6xKt/Zq9SNA8IQOSJ0iROktsbSkhDkhREX5ElUgKMU5RAKGr2Ky8wY2IciN/ZShOwExqj6rGnmVdtZJmNQz3lKukU7GmOXeOw2ViCh7bW/Zh3DTn02/DC3/FSfd36ZT/KYNZWoW9gR3ePObT29e56msvDFHmm3C1eK7+tsZyZv2tB1ZHEqf+XzFyr3NogW1agh0JU1HLBw6Q81NVgq5jfl/JrGvee9W/+ybLTp3+q4sk9ZJvp0d+Ku3OG/93yuHvL0wxQFZzGFgJXf3HmPnw==:EDFC
^PQ1,0,1,Y
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
