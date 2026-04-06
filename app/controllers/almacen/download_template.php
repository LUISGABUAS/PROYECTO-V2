<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../../config.php');

if (!in_array(9, $_SESSION['permisos'] ?? [])) {
    header('Location: ' . $URL);
    exit;
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="plantilla_productos.csv"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// BOM para compatibilidad con Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

fputcsv($output, ['nombre', 'precio_venta', 'precio_compra', 'stock_minimo', 'proveedor', 'categoria']);
fputcsv($output, ['Camisa de mezclilla', '250.00', '120.00', '5', 'Proveedor Ejemplo', 'Ropa']);
fputcsv($output, ['Pantalon cargo', '350.00', '180.00', '3', 'Otro Proveedor', 'Pantalones']);
fputcsv($output, ['Playera basica', '150.00', '70.00', '', '', '']);

fclose($output);
exit;
