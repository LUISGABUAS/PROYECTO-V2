<?php
ob_start();
require_once(dirname(__DIR__, 2) . '/config.php');

if (session_status() === PHP_SESSION_NONE) session_start();

if (!in_array(11, $_SESSION['permisos'] ?? [])) {
    ob_end_clean();
    http_response_code(403); exit;
}

$tipo = $_GET['tipo'] ?? 'excel';

// ── CONSULTA BASE ──────────────────────────────────────────────────────────────
$stmt = $pdo->query("
    SELECT
        a.nombre  AS nombre_producto,
        a.codigo  AS codigo_producto,
        c.nombre_categoria,
        s.id_stock,
        s.codigo_unico,
        DATE(s.fecha_ingreso) AS fecha_ingreso
    FROM stock s
    INNER JOIN tb_almacen a ON a.id_producto = s.id_producto
    INNER JOIN tb_categorias c ON c.id_categoria = a.id_categoria
    WHERE s.estado = 'SIN ESCANEAR'
    ORDER BY a.nombre ASC, s.id_stock ASC
");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($items)) {
    ob_end_clean();
    echo '<p style="font-family:sans-serif;padding:2rem;">No hay piezas sin escanear.</p>';
    exit;
}

// ══════════════════════════════════════════════════════════════════════════════
// EXCEL (CSV)
// ══════════════════════════════════════════════════════════════════════════════
if ($tipo === 'excel') {
    ob_end_clean();
    $filename = 'etiquetas_faltantes_' . date('Ymd_His') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM para Excel

    fputcsv($out, ['Producto', 'Código Producto', 'Categoría', 'Código Etiqueta', 'Fecha Ingreso']);

    foreach ($items as $row) {
        fputcsv($out, [
            $row['nombre_producto'],
            $row['codigo_producto'],
            $row['nombre_categoria'],
            $row['codigo_unico'],
            $row['fecha_ingreso'],
        ]);
    }

    fclose($out);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════════
// PDF (etiquetas ZPL → Labelary)
// ══════════════════════════════════════════════════════════════════════════════
if ($tipo === 'pdf') {
    $zpl = '';

    foreach ($items as $item) {
        $codigo   = $item['codigo_unico'];
        $producto = strtoupper($item['nombre_producto']);

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

    $ch = curl_init('https://api.labelary.com/v1/printers/8dpmm/labels/4x6/');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $zpl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/pdf']);

    $pdf      = curl_exec($ch);
    $http     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err = curl_error($ch);
    curl_close($ch);

    if ($http !== 200) {
        ob_end_clean();
        $total = count($items);
        echo '<pre style="font-family:sans-serif;padding:2rem;color:red;">';
        echo "Error Labelary\n";
        echo "HTTP: $http\n";
        echo "cURL error: $curl_err\n";
        echo "Total etiquetas: $total\n";
        echo "Primeros 500 chars del ZPL:\n" . htmlspecialchars(substr($zpl, 0, 500)) . "\n";
        echo "Respuesta Labelary:\n" . htmlspecialchars(substr($pdf, 0, 500));
        echo '</pre>';
        exit;
    }

    ob_end_clean();
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="etiquetas_faltantes_' . date('Ymd_His') . '.pdf"');
    echo $pdf;
    exit;
}

ob_end_clean();
http_response_code(400);
echo 'Tipo no válido';
