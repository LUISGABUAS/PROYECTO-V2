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
// PDF — redirige a print_zebra_seleccion.php con todos los IDs
// ══════════════════════════════════════════════════════════════════════════════
if ($tipo === 'pdf') {
    $ids = array_column($items, 'id_stock');

    if (empty($ids)) {
        ob_end_clean();
        echo '<p style="font-family:sans-serif;padding:2rem;">No hay piezas sin escanear.</p>';
        exit;
    }

    ob_end_clean();
    $url = rtrim($URL, '/') . '/app/controllers/helpers/print_zebra_seleccion.php?ids=' . implode(',', $ids);
    header('Location: ' . $url);
    exit;
}

ob_end_clean();
http_response_code(400);
echo 'Tipo no válido';
