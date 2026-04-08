<?php
require_once(dirname(__DIR__, 2) . '/config.php');
include(__DIR__ . '/../helpers/csrf.php');
include(__DIR__ . '/../helpers/validador.php');
csrf_verify();
include('../helpers/auditoria.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['mensaje'] = 'Acceso no permitido';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . '/clientes');
    exit;
}

/* =========================
   RECIBIR Y VALIDAR DATOS
========================= */
$errores = validarDatos(['id_cliente', 'tipo_cliente', 'nombre_completo', 'calle_numero', 'cp', 'colonia', 'municipio', 'estado']);
if (!empty($errores)) {
    error400('Faltan datos obligatorios', $errores);
    $_SESSION['mensaje'] = '❌ Faltan datos obligatorios';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . '/clientes/edit.php?id=' . ($_POST['id_cliente'] ?? ''));
    exit;
}

$id_cliente       = $_POST['id_cliente'] ?? null;
$tipo_cliente     = trim($_POST['tipo_cliente'] ?? '');
$nombre_completo  = trim($_POST['nombre_completo'] ?? '');
$calle_numero     = trim($_POST['calle_numero'] ?? '');
$cp               = trim($_POST['cp'] ?? '');
$colonia          = trim($_POST['colonia'] ?? '');
$municipio        = trim($_POST['municipio'] ?? '');
$estado           = trim($_POST['estado'] ?? '');
$telefono         = trim($_POST['telefono'] ?? '');
$referencias      = trim($_POST['referencias'] ?? '');
$id_usuario       = $_SESSION['id_usuario_sesion'] ?? $_SESSION['id_usuario'] ?? null;
$id_rol_sesion    = $_SESSION['id_rol_sesion'] ?? null;

// Determinar id_vendedor según el rol
if ($id_rol_sesion == 21) {
    // Si es vendedor, mantener su propio ID
    $id_vendedor = $id_usuario;
} else {
    // Si es admin, permitir cambiar el vendedor
    $id_vendedor = $_POST['id_vendedor'] ?? null;
    $id_vendedor = ($id_vendedor === '' || $id_vendedor === null) ? null : $id_vendedor;
}

/* =========================
   VERIFICAR CLIENTE EXISTE
========================= */
$check = $pdo->prepare("SELECT id_cliente FROM clientes WHERE id_cliente = ?");
$check->execute([$id_cliente]);

if ($check->rowCount() === 0) {
    error400('Cliente no encontrado');
    $_SESSION['mensaje'] = '❌ Cliente no encontrado';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . '/clientes');
    exit;
}

/* =========================
   ACTUALIZAR CLIENTE
========================= */
try {
    // Iniciar transacción
    $pdo->beginTransaction();

    // 1. Actualizar cliente
    $sql = "UPDATE clientes SET
                tipo_cliente     = :tipo_cliente,
                nombre_completo  = :nombre_completo,
                calle_numero     = :calle_numero,
                cp               = :cp,
                colonia          = :colonia,
                municipio        = :municipio,
                estado           = :estado,
                telefono         = :telefono,
                referencias      = :referencias,
                id_vendedor      = :id_vendedor,
                updated_at       = NOW()
            WHERE id_cliente = :id_cliente";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':tipo_cliente'    => $tipo_cliente,
        ':nombre_completo' => $nombre_completo,
        ':calle_numero'    => $calle_numero,
        ':cp'              => $cp,
        ':colonia'         => $colonia,
        ':municipio'       => $municipio,
        ':estado'          => $estado,
        ':telefono'        => $telefono,
        ':referencias'     => $referencias,
        ':id_vendedor'     => $id_vendedor,
        ':id_cliente'      => $id_cliente
    ]);

    // 2. Actualizar dirección principal en clientes_direcciones
    $sql_dir = "UPDATE clientes_direcciones SET
                    calle_numero = :calle_numero,
                    colonia = :colonia,
                    municipio = :municipio,
                    estado = :estado,
                    cp = :cp,
                    referencias = :referencias,
                    actualizada_en = NOW()
                WHERE id_cliente = :id_cliente AND es_principal = 1";

    $stmt_dir = $pdo->prepare($sql_dir);
    $stmt_dir->execute([
        ':calle_numero'  => $calle_numero,
        ':colonia'       => $colonia,
        ':municipio'     => $municipio,
        ':estado'        => $estado,
        ':cp'            => $cp,
        ':referencias'   => $referencias,
        ':id_cliente'    => $id_cliente
    ]);
    
    // Si no hay dirección principal, crearla
    if ($stmt_dir->rowCount() === 0) {
        $sql_insert = "INSERT INTO clientes_direcciones 
                      (id_cliente, calle_numero, colonia, municipio, estado, cp, referencias, es_principal, activa)
                      VALUES (:id_cliente, :calle_numero, :colonia, :municipio, :estado, :cp, :referencias, 1, 1)";
        $stmt_insert = $pdo->prepare($sql_insert);
        $stmt_insert->execute([
            ':id_cliente'    => $id_cliente,
            ':calle_numero'  => $calle_numero,
            ':colonia'       => $colonia,
            ':municipio'     => $municipio,
            ':estado'        => $estado,
            ':cp'            => $cp,
            ':referencias'   => $referencias
        ]);
    }

    // Confirmar transacción
    $pdo->commit();

    registrarAuditoria($pdo, $id_usuario, $_SESSION['sesion_nombres'] ?? $_SESSION['nombre_usuario'] ?? null, 'EDITAR CLIENTE', 'clientes', $id_cliente, $nombre_completo);

    $_SESSION['mensaje'] = '✅ Cliente actualizado correctamente';
    $_SESSION['icono'] = 'success';
    
    // Redirigir a la página anterior según el tipo de cliente
    $redirect = ($tipo_cliente === 'foraneo') 
        ? $URL . '/clientes/foraneos.php'
        : $URL . '/clientes/locales.php';
    
    header('Location: ' . $redirect);
    exit;

} catch (Exception $e) {
    // Revertir si hay error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error500('Error actualizando cliente', ['error' => $e->getMessage()]);
    $_SESSION['mensaje'] = '❌ Error al actualizar el cliente';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . '/clientes/edit.php?id=' . $id_cliente);
    exit;
}
