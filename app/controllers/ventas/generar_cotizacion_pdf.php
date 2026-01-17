<?php
session_start();
include('../../config.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['generar_pdf'])) {
    http_response_code(400);
    exit('Solicitud no válida');
}

try {
    $total = $_POST['total'] ?? '0.00';
    $fecha = $_POST['fecha'] ?? date('d/m/Y');
    $productos = json_decode($_POST['productos'] ?? '[]', true);

    // HTML del PDF
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Cotización</title>
        <style>
            * { margin: 0; padding: 0; }
            body { font-family: Arial, sans-serif; background: white; color: #333; }
            .container { max-width: 900px; margin: 0 auto; padding: 20px; }
            .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px; }
            .header h2 { margin: 0 0 5px 0; color: #333; font-size: 28px; }
            .header p { margin: 5px 0; color: #666; font-size: 14px; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            thead { background-color: #f0f0f0; }
            th { padding: 12px; border: 1px solid #999; text-align: left; font-weight: bold; font-size: 13px; }
            td { padding: 12px; border: 1px solid #ddd; font-size: 13px; }
            tr:nth-child(even) { background-color: #f9f9f9; }
            .col-producto { width: 50%; text-align: left; }
            .col-cantidad { width: 15%; text-align: center; }
            .col-precio { width: 17%; text-align: right; }
            .col-subtotal { width: 18%; text-align: right; }
            .total-section { text-align: right; margin-top: 20px; padding-top: 15px; border-top: 2px solid #333; }
            .total-amount { font-size: 18px; font-weight: bold; color: #27ae60; }
            @media print {
                body { margin: 0; padding: 0; }
                .container { padding: 0; }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>COTIZACIÓN</h2>
                <p>Fecha: ' . htmlspecialchars($fecha) . '</p>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th class="col-producto">Producto</th>
                        <th class="col-cantidad">Cantidad</th>
                        <th class="col-precio">Precio Unit.</th>
                        <th class="col-subtotal">Subtotal</th>
                    </tr>
                </thead>
                <tbody>';

    $totalCalculado = 0;
    foreach ($productos as $prod) {
        $producto = htmlspecialchars($prod['producto'] ?? 'N/A');
        $cantidad = (int)($prod['cantidad'] ?? 0);
        $precio = (float)($prod['precio'] ?? 0);
        $subtotal = (float)($prod['subtotal'] ?? 0);
        $totalCalculado += $subtotal;

        $html .= '<tr>
            <td class="col-producto">' . $producto . '</td>
            <td class="col-cantidad">' . $cantidad . '</td>
            <td class="col-precio">$' . number_format($precio, 2) . '</td>
            <td class="col-subtotal">$' . number_format($subtotal, 2) . '</td>
        </tr>';
    }

    $html .= '
                </tbody>
            </table>
            
            <div class="total-section">
                <div class="total-amount">TOTAL: $' . number_format($total, 2) . '</div>
            </div>
        </div>
        
        <script>
            window.onload = function() {
                window.print();
            };
        </script>
    </body>
    </html>';

    // Mostrar como HTML imprimible
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: inline; filename="cotizacion_' . date('Y-m-d_H-i-s') . '.html"');
    echo $html;

} catch (Exception $e) {
    http_response_code(500);
    echo 'Error: ' . $e->getMessage();
}
