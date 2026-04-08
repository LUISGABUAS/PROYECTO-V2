<?php
/**
 * Migración: Pasar direcciones principales de tabla clientes a clientes_direcciones
 * Se crean direcciones principales (es_principal = 1) para todos los clientes
 */

require_once(dirname(__DIR__, 2) . '/config.php');

try {
    echo "🔄 Iniciando migración de direcciones...\n";
    
    // 1. Obtener todos los clientes con sus direcciones
    $sql = "SELECT id_cliente, calle_numero, colonia, municipio, estado, cp, referencias 
            FROM clientes 
            WHERE id_cliente > 0";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📋 Total de clientes a procesar: " . count($clientes) . "\n";
    
    $pdo->beginTransaction();
    
    $insertados = 0;
    $saltados = 0;
    
    foreach ($clientes as $cliente) {
        $id_cliente = $cliente['id_cliente'];
        
        // 2. Verificar si ya existe una dirección principal para este cliente
        $check = $pdo->prepare(
            "SELECT id FROM clientes_direcciones 
             WHERE id_cliente = ? AND es_principal = 1 AND activa = 1"
        );
        $check->execute([$id_cliente]);
        
        if ($check->rowCount() > 0) {
            $saltados++;
            continue; // Ya existe dirección principal, saltar
        }
        
        // 3. Insertar la dirección principal
        $insert = $pdo->prepare(
            "INSERT INTO clientes_direcciones 
            (id_cliente, calle_numero, colonia, municipio, estado, cp, referencias, es_principal, activa, creada_en, actualizada_en)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, 1, NOW(), NOW())"
        );
        
        try {
            $insert->execute([
                $id_cliente,
                $cliente['calle_numero'],
                $cliente['colonia'],
                $cliente['municipio'],
                $cliente['estado'],
                $cliente['cp'],
                $cliente['referencias']
            ]);
            $insertados++;
        } catch (Exception $e) {
            echo "⚠️  Error al insertar cliente $id_cliente: " . $e->getMessage() . "\n";
        }
    }
    
    $pdo->commit();
    
    echo "\n✅ Migración completada:\n";
    echo "   - Insertados: $insertados\n";
    echo "   - Saltados (ya existían): $saltados\n";
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("❌ Error en la migración: " . $e->getMessage() . "\n");
}
?>
