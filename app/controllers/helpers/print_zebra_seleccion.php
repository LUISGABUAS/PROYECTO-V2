<?php
require_once(dirname(__DIR__, 2) . '/config.php');

/* ==========================
   RECIBIR IDS
========================== */
$ids = $_GET['ids'] ?? '';
if (!$ids) {
    die('No se seleccionaron códigos');
}

$ids_array = array_map('intval', explode(',', $ids));

// Labelary permite máx 50 etiquetas por petición — paginar con ?offset=N
$limit  = 50;
$offset = max(0, (int)($_GET['offset'] ?? 0));
$total  = count($ids_array);

// Si hay más de 50 y no se pidió offset, mostrar selector de lotes
if ($total > $limit && !isset($_GET['offset'])) {
    $base_url = strtok($_SERVER['REQUEST_URI'], '?');
    $lotes = ceil($total / $limit);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8">
    <title>Seleccionar lote</title>
    <style>body{font-family:sans-serif;padding:2rem;} .btn{display:inline-block;margin:.3rem;padding:.6rem 1.2rem;background:#007bff;color:#fff;border-radius:6px;text-decoration:none;font-size:.95rem;} .btn:hover{background:#0056b3;}</style>
    </head><body>';
    echo "<h3>📦 {$total} etiquetas — selecciona el lote a imprimir:</h3>";
    for ($i = 0; $i < $lotes; $i++) {
        $desde = $i * $limit + 1;
        $hasta = min(($i + 1) * $limit, $total);
        echo '<a class="btn" href="' . htmlspecialchars($base_url) . '?ids=' . urlencode($ids) . '&offset=' . ($i * $limit) . '" target="_blank">'
           . "Lote " . ($i + 1) . " (etiquetas $desde–$hasta)"
           . '</a>';
    }
    echo '</body></html>';
    exit;
}

$ids_array = array_slice($ids_array, $offset, $limit);
$in = str_repeat('?,', count($ids_array) - 1) . '?';

/* ==========================
   CONSULTAR STOCK
========================== */
$sql = "SELECT 
            s.codigo_unico,
            p.nombre AS nombre_producto
        FROM stock s
        INNER JOIN tb_almacen p ON p.id_producto = s.id_producto
        WHERE s.id_stock IN ($in)
        ORDER BY s.id_stock ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($ids_array);
$stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$stocks) {
    die('No se encontraron datos');
}

/* ==========================
   GENERAR ZPL
========================== */
$zpl = "";

foreach ($stocks as $stock) {

    $codigo   = $stock['codigo_unico'];
    $producto = strtoupper($stock['nombre_producto']);

    $zpl .= "^XA
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
^A0B,70,70
^FD{$producto}^FS

^FO107,492^GFA,609,3050,10,:Z64:eJzt1bFxwzAMBVAoKtiFI2iTKCOlTGd22SrHbKIRWKrQCSFA4tO2GDc5X1zE1TvZlshPACLSzxutBfQOrUc56GTybGKTh9jkITZ508CmyVQuifxdtJh22a/qUzLQZb7IjkVkShCZFtNOVWM05b1FE2+mE8enkrPLF2vO+SJZQhshta9DkgOUUzINEM2dMxqhoXOWZ2pn3uqATM/QFKBo8guemy407FYRnkNd/cyLY93niZMvyt8U5d9sk2kvGrNmExe5a61FfKV65glaoAgF0yZnmcjtuvN8fuNaFCQ8U9KEqm52ynC32r2zgu3jTKgS92OV/Fbtzu1p3bX8fUKPmum8VzmpddWsha1ziGV4ZUkj5QbI0lZJpB2qC7UJLL0gf5XxqL1a/rxS2VHAfG6KpUNZu6ypdmPq6yyhJEuXyZU7j2r39FKDbqfxAGf+KFXy/2b/f7Nf1kGbtoQKI1QdWQ9eaYHwFE2oiCOUoA3iUFbgo04mne3zSrXa9T5a7fJalR3l9b3OlsuACnPQdKy68VB/rowuuTMq0X/cs/42vPcXvK2Caas716FbazJYQjuyWjuZdrJv59HOrXO+BH0DVBCjjA==:72E2
^FO118,907^GFA,373,1218,6,:Z64:eJzVkkFqxDAMRX8wVLvxEXyTybW6s0sXcy0fJUdIdykN+ZWiDMmQDIRCCxX4LRL56wt/AHjVg2ZaGXjMbY/fOlMZEL6xKt/Zq9SNA8IQOSJ0iROktsbSkhDkhREX5ElUgKMU5RAKGr2Ky8wY2IciN/ZShOwExqj6rGnmVdtZJmNQz3lKukU7GmOXeOw2ViCh7bW/Zh3DTn02/DC3/FSfd36ZT/KYNZWoW9gR3ePObT29e56msvDFHmm3C1eK7+tsZyZv2tB1ZHEqf+XzFyr3NogW1agh0JU1HLBw6Q81NVgq5jfl/JrGvee9W/+ybLTp3+q4sk9ZJvp0d+Ku3OG/93yuHvL0wxQFZzGFgJXf3HmPnw==:EDFC
^FO118,92^GFA,373,1218,6,:Z64:eJzVkkFqxDAMRX8wVLvxEXyTybW6s0sXcy0fJUdIdykN+ZWiDMmQDIRCCxX4LRL56wt/AHjVg2ZaGXjMbY/fOlMZEL6xKt/Zq9SNA8IQOSJ0iROktsbSkhDkhREX5ElUgKMU5RAKGr2Ky8wY2IciN/ZShOwExqj6rGnmVdtZJmNQz3lKukU7GmOXeOw2ViCh7bW/Zh3DTn02/DC3/FSfd36ZT/KYNZWoW9gR3ePObT29e56msvDFHmm3C1eK7+tsZyZv2tB1ZHEqf+XzFyr3NogW1agh0JU1HLBw6Q81NVgq5jfl/JrGvee9W/+ybLTp3+q4sk9ZJvp0d+Ku3OG/93yuHvL0wxQFZzGFgJXf3HmPnw==:EDFC
^PQ1,0,1,Y
^XZ
";
}

/* ==========================
   LABELARY → PDF
========================== */
$ch = curl_init("https://api.labelary.com/v1/printers/8dpmm/labels/4x6/");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $zpl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Accept: application/pdf"
]);

$pdf      = curl_exec($ch);
$http     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_err = curl_error($ch);
curl_close($ch);

if ($http !== 200) {
    echo '<pre style="font-family:sans-serif;padding:2rem;color:red;">';
    echo "Error Labelary\n";
    echo "HTTP code: $http\n";
    echo "cURL error: " . ($curl_err ?: '(ninguno)') . "\n";
    echo "Total etiquetas: " . count($stocks) . "\n";
    echo "Tamaño ZPL: " . strlen($zpl) . " bytes\n";
    echo "Respuesta:\n" . htmlspecialchars(substr((string)$pdf, 0, 800));
    echo '</pre>';
    exit;
}

/* ==========================
   MOSTRAR PDF
========================== */
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="etiquetas_pacas_yadira.pdf"');
echo $pdf;
exit;
