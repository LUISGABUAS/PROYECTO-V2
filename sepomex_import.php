<?php
/**
 * Importar datos SEPOMEX a la base de datos local.
 * Ejecutar UNA SOLA VEZ desde el navegador:  http://localhost/PROYECTO/sepomex_import.php
 * Eliminar este archivo después de usarlo.
 */
set_time_limit(300);
ini_set('memory_limit', '256M');

require_once __DIR__ . '/app/config.php';

$zipUrl  = 'https://www.correosdemexico.gob.mx/datosabiertos/cp/cpdescarga.zip';
$zipFile = __DIR__ . '/cpdescarga.zip';
$txtFile = __DIR__ . '/CPdescarga.txt';

echo '<pre>';

// 1. Descargar ZIP
if (!file_exists($zipFile)) {
    echo "Descargando datos SEPOMEX...\n";
    $ctx = stream_context_create(['http' => ['timeout' => 120]]);
    $data = file_get_contents($zipUrl, false, $ctx);
    if (!$data) {
        die("ERROR: No se pudo descargar el archivo. Descárgalo manualmente de:\n$zipUrl\ny colócalo como cpdescarga.zip en la raíz del proyecto.\n");
    }
    file_put_contents($zipFile, $data);
    echo "Descarga completada.\n";
} else {
    echo "ZIP ya existe, usando archivo local.\n";
}

// 2. Extraer
if (!file_exists($txtFile)) {
    echo "Extrayendo...\n";
    $zip = new ZipArchive();
    if ($zip->open($zipFile) !== true) {
        die("ERROR: No se pudo abrir el ZIP.\n");
    }
    $zip->extractTo(__DIR__);
    $zip->close();
    echo "Extracción completada.\n";
} else {
    echo "TXT ya existe, usando archivo local.\n";
}

if (!file_exists($txtFile)) {
    die("ERROR: No se encontró CPdescarga.txt. Verifica el contenido del ZIP.\n");
}

// 3. Vaciar tabla e importar
echo "Vaciando tabla...\n";
$pdo->exec("TRUNCATE TABLE sepomex_colonias");

echo "Importando registros (puede tardar 30-60 segundos)...\n";
$handle = fopen($txtFile, 'r');

// Detectar encoding y saltar encabezados (primeras 2 líneas)
fgets($handle); // línea 1 (puede ser BOM o encabezado)
fgets($handle); // línea 2

$insertados = 0;
$stmt = $pdo->prepare(
    "INSERT INTO sepomex_colonias (cp, colonia, municipio, estado) VALUES (?, ?, ?, ?)"
);
$pdo->beginTransaction();

while (($line = fgets($handle)) !== false) {
    $line = mb_convert_encoding(trim($line), 'UTF-8', 'UTF-8,ISO-8859-1');
    $cols = explode('|', $line);

    if (count($cols) < 5) continue;

    $cp        = trim($cols[0]);
    $colonia   = trim($cols[1]);
    $municipio = trim($cols[3]);
    $estado    = trim($cols[4]);

    if (!preg_match('/^\d{5}$/', $cp) || $colonia === '') continue;

    $stmt->execute([$cp, $colonia, $municipio, $estado]);
    $insertados++;

    if ($insertados % 5000 === 0) {
        $pdo->commit();
        $pdo->beginTransaction();
        echo "$insertados registros...\n";
        ob_flush(); flush();
    }
}

$pdo->commit();
fclose($handle);

// 4. Limpiar archivos temporales
@unlink($zipFile);
@unlink($txtFile);

echo "\n✅ Importación completada: $insertados colonias.\n";
echo "Elimina este archivo (sepomex_import.php) por seguridad.\n";
echo '</pre>';
