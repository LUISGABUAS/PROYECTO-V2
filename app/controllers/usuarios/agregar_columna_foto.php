<?php
// Script para verificar y agregar columna foto_perfil a tb_usuario
include('../../config.php');

try {
    // Verificar si la columna existe
    $sql = "SHOW COLUMNS FROM tb_usuario LIKE 'foto_perfil'";
    $query = $pdo->prepare($sql);
    $query->execute();
    $column_exists = $query->fetch(PDO::FETCH_ASSOC);
    
    if (!$column_exists) {
        // Agregar la columna si no existe
        $alter_sql = "ALTER TABLE tb_usuario ADD COLUMN foto_perfil VARCHAR(255) NULL DEFAULT NULL AFTER email";
        $alter_query = $pdo->prepare($alter_sql);
        $alter_query->execute();
        echo json_encode(['success' => true, 'mensaje' => 'Columna foto_perfil agregada exitosamente']);
    } else {
        echo json_encode(['success' => true, 'mensaje' => 'La columna foto_perfil ya existe']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'mensaje' => 'Error: ' . $e->getMessage()]);
}
?>
