<?php
require_once(dirname(__DIR__, 2) . '/config.php');
include(__DIR__ . '/../helpers/csrf.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$accion = $_POST['accion'] ?? $_GET['accion'] ?? null;

// Verificar CSRF solo para POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
}

// =====================
// CREAR DIRECCIÓN ADICIONAL
// =====================
if ($accion === 'crear') {
    $id_cliente = $_POST['id_cliente'] ?? null;
    $calle = trim($_POST['calle_numero'] ?? '');
    $colonia = trim($_POST['colonia'] ?? '');
    $municipio = trim($_POST['municipio'] ?? '');
    $estado = trim($_POST['estado'] ?? '');
    $cp = trim($_POST['cp'] ?? '');
    $referencias = trim($_POST['referencias'] ?? '');

    if (!$id_cliente || empty($calle) || empty($colonia)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios']);
        exit;
    }

    try {
        // Verificar si ya existe una dirección idéntica
        $sql_check = "SELECT id FROM clientes_direcciones 
                      WHERE id_cliente = ? 
                      AND calle_numero = ? 
                      AND colonia = ? 
                      AND municipio = ? 
                      AND estado = ? 
                      AND cp = ? 
                      AND activa = 1";
        
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$id_cliente, $calle, $colonia, $municipio, $estado, $cp]);
        
        if ($stmt_check->rowCount() > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Esta dirección ya existe para este cliente']);
            exit;
        }

        $sql = "INSERT INTO clientes_direcciones 
                (id_cliente, calle_numero, colonia, municipio, estado, cp, referencias) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_cliente, $calle, $colonia, $municipio, $estado, $cp, $referencias]);

        echo json_encode([
            'success' => true,
            'id' => $pdo->lastInsertId(),
            'direccion' => "$calle, $colonia, $municipio, $estado CP $cp"
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// =====================
// OBTENER DIRECCIONES DE UN CLIENTE
// =====================
if ($accion === 'listar') {
    $id_cliente = $_GET['id_cliente'] ?? null;

    if (!$id_cliente) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'id_cliente requerido']);
        exit;
    }

    try {
        $sql = "SELECT id, calle_numero, colonia, municipio, estado, cp, referencias, es_principal, activa
                FROM clientes_direcciones 
                WHERE id_cliente = ? AND activa = 1
                ORDER BY es_principal DESC, id DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_cliente]);
        $direcciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $direcciones]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// =====================
// ELIMINAR DIRECCIÓN
// =====================
if ($accion === 'eliminar') {
    $id_direccion = $_POST['id'] ?? null;
    $id_cliente = $_POST['id_cliente'] ?? null;

    if (!$id_direccion || !$id_cliente) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Parámetros faltantes']);
        exit;
    }

    try {
        // Verificar cuántas direcciones activas tiene este cliente
        $sql_count = "SELECT COUNT(*) as total FROM clientes_direcciones WHERE id_cliente = ? AND activa = 1";
        $stmt_count = $pdo->prepare($sql_count);
        $stmt_count->execute([$id_cliente]);
        $result = $stmt_count->fetch(PDO::FETCH_ASSOC);

        if ($result['total'] <= 1) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No puedes eliminar la última dirección del cliente']);
            exit;
        }

        // Verificar que la dirección pertenece a este cliente
        $sql_verify = "SELECT id, es_principal FROM clientes_direcciones WHERE id = ? AND id_cliente = ? AND activa = 1";
        $stmt_verify = $pdo->prepare($sql_verify);
        $stmt_verify->execute([$id_direccion, $id_cliente]);
        $direccion = $stmt_verify->fetch(PDO::FETCH_ASSOC);
        
        if (!$direccion) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Dirección no encontrada']);
            exit;
        }

        // No permitir eliminar la dirección principal
        if ($direccion['es_principal'] == 1) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No puedes eliminar la dirección principal']);
            exit;
        }

        // Eliminar completamente de la BD (hard delete)
        $sql = "DELETE FROM clientes_direcciones WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_direccion]);

        echo json_encode(['success' => true, 'message' => 'Dirección eliminada correctamente']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Acción no válida']);
?>
