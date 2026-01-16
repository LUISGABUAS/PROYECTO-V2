<?php
include('../../config.php');

header('Content-Type: application/json');

$id_stock = isset($_POST['id_stock']) ? (int)$_POST['id_stock'] : 0;

if ($id_stock <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Stock no válido'
    ]);
    exit;
}

try {
    $sql = "DELETE FROM stock WHERE id_stock = :id_stock";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id_stock', $id_stock, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Stock eliminado correctamente'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No se encontró el stock'
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error en la base de datos'
        // 'error' => $e->getMessage() // solo en desarrollo
    ]);
}

exit;
