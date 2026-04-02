<?php
session_start();
include('../../config.php');
include('../helpers/auditoria.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $URL . '/almacen/import.php');
    exit;
}

if (!in_array(9, $_SESSION['permisos'] ?? [])) {
    header('Location: ' . $URL);
    exit;
}

$id_usuario     = $id_usuario_sesion ?? null;
$nombre_usuario = $sesion_nombres ?? null;

// Validar archivo
if (!isset($_FILES['archivo_csv']) || $_FILES['archivo_csv']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['mensaje'] = 'Error al subir el archivo. Intente nuevamente.';
    header('Location: ' . $URL . '/almacen/import.php');
    exit;
}

$ext = strtolower(pathinfo($_FILES['archivo_csv']['name'], PATHINFO_EXTENSION));
if ($ext !== 'csv') {
    $_SESSION['mensaje'] = 'Solo se permiten archivos .csv';
    header('Location: ' . $URL . '/almacen/import.php');
    exit;
}

if ($_FILES['archivo_csv']['size'] > 2 * 1024 * 1024) {
    $_SESSION['mensaje'] = 'El archivo no puede superar 2MB';
    header('Location: ' . $URL . '/almacen/import.php');
    exit;
}

// Cargar todos los proveedores y categorías para búsqueda por nombre
$stmt = $pdo->query("SELECT id_proovedor, nombre_proveedor FROM tb_proveedores");
$proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT id_categoria, nombre_categoria FROM tb_categorias");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Función: busca la mejor coincidencia por nombre usando similar_text
function buscarMejorCoincidencia($texto, $lista, $campo_id, $campo_nombre) {
    $texto = strtolower(trim($texto));
    if (empty($texto)) return null;

    $mejor_id    = null;
    $mejor_score = 0;

    foreach ($lista as $item) {
        $nombre = strtolower(trim($item[$campo_nombre]));
        similar_text($texto, $nombre, $score);
        if ($score > $mejor_score) {
            $mejor_score = $score;
            $mejor_id    = $item[$campo_id];
        }
    }

    // Solo acepta si la similitud es mayor al 35%
    return $mejor_score >= 35 ? $mejor_id : null;
}

// Obtener el último código para auto-generar el siguiente
$stmt = $pdo->query("SELECT codigo FROM tb_almacen ORDER BY id_producto DESC LIMIT 1");
$ultimo = $stmt->fetch(PDO::FETCH_ASSOC);
$contador_codigo = 1;
if ($ultimo && preg_match('/(\d+)$/', $ultimo['codigo'], $m)) {
    $contador_codigo = (int)$m[1] + 1;
}

$handle = fopen($_FILES['archivo_csv']['tmp_name'], 'r');
if ($handle === false) {
    $_SESSION['mensaje'] = 'No se pudo leer el archivo CSV';
    header('Location: ' . $URL . '/almacen/import.php');
    exit;
}

$importados  = 0;
$fallidos    = 0;
$fila_num    = 0;
$errores     = [];
$fecha_ahora = date('Y-m-d H:i:s');
$hoy         = date('Y-m-d');

$stmt_insert = $pdo->prepare("INSERT INTO tb_almacen
    (codigo, id_proovedor, nombre, descripcion, stock_minimo, stock_maximo,
     precio_compra, precio_venta, fecha_ingreso, imagen, id_categoria, id_usuario,
     fyh_creacion, fyh_actualizacion)
    VALUES
    (:codigo, :id_proovedor, :nombre, :descripcion, :stock_minimo, :stock_maximo,
     :precio_compra, :precio_venta, :fecha_ingreso, :imagen, :id_categoria, :id_usuario,
     :fyh_creacion, :fyh_actualizacion)
");

while (($fila = fgetcsv($handle, 1000, ',')) !== false) {
    $fila_num++;

    // Saltar encabezados
    if ($fila_num === 1) continue;

    // Limpiar BOM en primera celda si existe
    $fila[0] = ltrim($fila[0], "\xEF\xBB\xBF");

    if (count($fila) < 2) {
        $fallidos++;
        $errores[] = "Fila $fila_num: muy pocas columnas";
        continue;
    }

    $nombre        = trim($fila[0]);
    $precio_venta  = trim($fila[1]);
    $precio_compra = trim($fila[2] ?? '');
    $stock_minimo  = trim($fila[3] ?? '');
    $nombre_prov   = trim($fila[4] ?? '');
    $nombre_cat    = trim($fila[5] ?? '');

    // Validar campos requeridos
    if (empty($nombre)) {
        $fallidos++;
        $errores[] = "Fila $fila_num: nombre es obligatorio";
        continue;
    }

    if (!is_numeric($precio_venta) || (float)$precio_venta <= 0) {
        $fallidos++;
        $errores[] = "Fila $fila_num ($nombre): precio de venta inválido";
        continue;
    }

    // Precio compra: si está vacío o inválido, usar 1
    $precio_compra = is_numeric($precio_compra) && (float)$precio_compra > 0 ? (float)$precio_compra : 1;

    // Stock mínimo: obligatorio
    if (!is_numeric($stock_minimo) || (int)$stock_minimo <= 0) {
        $fallidos++;
        $errores[] = "Fila $fila_num ($nombre): stock_minimo es obligatorio y debe ser mayor a 0";
        continue;
    }
    $stock_minimo = (int)$stock_minimo;

    // Proveedor: obligatorio
    if (empty($nombre_prov)) {
        $fallidos++;
        $errores[] = "Fila $fila_num ($nombre): proveedor es obligatorio";
        continue;
    }
    $id_proovedor = buscarMejorCoincidencia($nombre_prov, $proveedores, 'id_proovedor', 'nombre_proveedor');
    if ($id_proovedor === null) {
        $fallidos++;
        $errores[] = "Fila $fila_num ($nombre): no se encontró proveedor similar a '$nombre_prov'";
        continue;
    }

    // Categoría: obligatoria
    if (empty($nombre_cat)) {
        $fallidos++;
        $errores[] = "Fila $fila_num ($nombre): categoria es obligatoria";
        continue;
    }
    $id_categoria = buscarMejorCoincidencia($nombre_cat, $categorias, 'id_categoria', 'nombre_categoria');
    if ($id_categoria === null) {
        $fallidos++;
        $errores[] = "Fila $fila_num ($nombre): no se encontró categoría similar a '$nombre_cat'";
        continue;
    }

    // Auto-generar código único
    $codigo = 'IMP-' . str_pad($contador_codigo, 5, '0', STR_PAD_LEFT);
    $contador_codigo++;

    try {
        $stmt_insert->execute([
            ':codigo'            => $codigo,
            ':id_proovedor'      => $id_proovedor,
            ':nombre'            => $nombre,
            ':descripcion'       => '',
            ':stock_minimo'      => $stock_minimo,
            ':stock_maximo'      => $stock_minimo * 10,
            ':precio_compra'     => $precio_compra,
            ':precio_venta'      => (float)$precio_venta,
            ':fecha_ingreso'     => $hoy,
            ':imagen'            => 'sin_imagen.png',
            ':id_categoria'      => $id_categoria,
            ':id_usuario'        => $id_usuario,
            ':fyh_creacion'      => $fecha_ahora,
            ':fyh_actualizacion' => $fecha_ahora,
        ]);

        $nuevo_id = $pdo->lastInsertId();
        registrarAuditoria($pdo, $id_usuario, $nombre_usuario, 'IMPORTAR CSV', 'tb_almacen', $nuevo_id, "Producto: $nombre (Código: $codigo)");
        $importados++;

    } catch (Exception $e) {
        $fallidos++;
        $errores[] = "Fila $fila_num ($nombre): " . $e->getMessage();
    }
}

fclose($handle);

$mensaje = "Importación completada: $importados producto(s) importado(s).";
if ($fallidos > 0) {
    $mensaje .= " $fallidos fila(s) con error.";
    if (!empty($errores)) {
        $mensaje .= ' — ' . implode(' | ', array_slice($errores, 0, 3));
        if (count($errores) > 3) {
            $mensaje .= ' ... y ' . (count($errores) - 3) . ' más.';
        }
    }
}

$_SESSION['mensaje'] = $mensaje;
header('Location: ' . $URL . '/almacen/import.php');
exit;
